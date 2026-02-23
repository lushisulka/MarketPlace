<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Lejo client DHE b2b
if (!in_array($_SESSION['user_role'], ['client', 'b2b'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$page_title = 'Checkout';
$user_id    = $_SESSION['user_id'];
$is_b2b     = ($_SESSION['user_role'] === 'b2b');

// Merr zbritjen B2B nëse aplikohet
$b2b_discount = 0;
if ($is_b2b) {
    $stmt = $conn->prepare("SELECT discount_percentage FROM b2b_clients WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $b2b_row = $stmt->get_result()->fetch_assoc();
    $b2b_discount = $b2b_row['discount_percentage'] ?? 0;
}

// Merr shportën
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.b2b_price, p.image, p.unit, p.id as product_id, p.partner_id
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($items)) {
    header('Location: ' . SITE_URL . '/client/cart.php');
    exit;
}

// Llogarit çmimet e sakta
foreach ($items as &$item) {
    if ($is_b2b && $item['b2b_price']) {
        $item['effective_price'] = $item['b2b_price'];
    } elseif ($is_b2b && $b2b_discount > 0) {
        $item['effective_price'] = $item['price'] * (1 - $b2b_discount / 100);
    } else {
        $item['effective_price'] = $item['price'];
    }
}
unset($item);

$subtotal        = array_sum(array_map(fn($i) => $i['effective_price'] * $i['quantity'], $items));
$original_total  = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$discount_amount = $original_total - $subtotal;
$total           = $subtotal;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address        = sanitize($_POST['address']);
    $city           = sanitize($_POST['city']);
    $payment_method = in_array($_POST['payment_method'], ['cash','card','bank_transfer']) ? $_POST['payment_method'] : 'cash';
    $notes          = sanitize($_POST['notes'] ?? '');

    $order_number = generateOrderNumber();

    $stmt = $conn->prepare("
        INSERT INTO orders (order_number, user_id, total_amount, discount_amount, final_amount, payment_method, delivery_address, delivery_city, notes, is_b2b)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $is_b2b_int = $is_b2b ? 1 : 0;
    $stmt->bind_param("sidddssssi", $order_number, $user_id, $original_total, $discount_amount, $total, $payment_method, $address, $city, $notes, $is_b2b_int);
    $stmt->execute();
    $order_id = $conn->insert_id;

    foreach ($items as $item) {
        $item_total = $item['effective_price'] * $item['quantity'];
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, partner_id, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iiiddd", $order_id, $item['product_id'], $item['partner_id'], $item['quantity'], $item['effective_price'], $item_total);
        $stmt->execute();
    }

    // Pastro shportën
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    setFlash('success', "✅ Porosia <strong>#{$order_number}</strong> u vendos me sukses!");
    header('Location: ' . SITE_URL . '/client/orders.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4">💳 Checkout
        <?php if ($is_b2b): ?>
        <span class="badge bg-info text-dark fs-6 ms-2">💼 B2B</span>
        <?php endif; ?>
    </h2>

    <?php if ($is_b2b && $discount_amount > 0): ?>
    <div class="alert alert-success mb-4">
        🎁 <strong>Zbritja juaj B2B (<?= $b2b_discount ?>%)</strong> — kurseni <strong><?= formatPrice($discount_amount) ?></strong>!
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Forma -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">📦 Adresa e Dërgimit</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresa e plotë *</label>
                            <input type="text" name="address" class="form-control" required
                                   placeholder="Rruga, nr. shtëpisë / ndërtesës...">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Qyteti *</label>
                            <select name="city" class="form-select" required>
                                <option value="">Zgjidh qytetin...</option>
                                <?php
                                $cities = ['Tiranë','Durrës','Vlorë','Shkodër','Elbasan','Fier','Korçë','Berat','Gjirokastër','Lushnjë'];
                                foreach ($cities as $c) echo "<option value='$c'>$c</option>";
                                ?>
                            </select>
                        </div>

                        <h5 class="fw-bold mb-3">💳 Mënyra e Pagesës</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" value="cash" id="pay_cash" checked>
                                <label class="btn btn-outline-success w-100 py-3" for="pay_cash">
                                    <div class="fs-4">💵</div>Cash
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" value="card" id="pay_card">
                                <label class="btn btn-outline-success w-100 py-3" for="pay_card">
                                    <div class="fs-4">💳</div>Kartë
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" value="bank_transfer" id="pay_bank">
                                <label class="btn btn-outline-success w-100 py-3" for="pay_bank">
                                    <div class="fs-4">🏦</div>Bankë
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Shënime shtesë</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Instruksione të veçanta për dërgim..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">
                            ✅ Konfirmo Porosinë — <?= formatPrice($total) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rezymeja -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                <div class="card-header bg-transparent fw-bold">
                    📋 Porosia (<?= count($items) ?> artikuj)
                </div>
                <div class="card-body p-0">
                    <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                        <div>
                            <div class="fw-semibold"><?= $item['name'] ?></div>
                            <small class="text-muted">
                                <?= $item['quantity'] ?> <?= $item['unit'] ?> ×
                                <?= formatPrice($item['effective_price']) ?>
                                <?php if ($item['effective_price'] < $item['price']): ?>
                                <span class="text-muted text-decoration-line-through ms-1"><?= formatPrice($item['price']) ?></span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <span class="fw-bold"><?= formatPrice($item['effective_price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-body">
                    <?php if ($discount_amount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Çmimi origjinal</span>
                        <span><?= formatPrice($original_total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>🎁 Zbritja B2B</span>
                        <span>-<?= formatPrice($discount_amount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Dërgesa</span>
                        <span class="text-success">Falas</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-4">
                        <span>TOTAL</span>
                        <span class="text-success"><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>