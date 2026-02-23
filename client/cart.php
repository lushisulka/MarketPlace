<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Lejo si client ashtu edhe b2b
if (!in_array($_SESSION['user_role'], ['client', 'b2b'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$page_title = 'Shporta';
$is_b2b     = ($_SESSION['user_role'] === 'b2b');
$user_id    = $_SESSION['user_id'];

// Nëse është B2B, merr zbritjen e tij
$b2b_discount = 0;
if ($is_b2b) {
    $stmt = $conn->prepare("SELECT discount_percentage FROM b2b_clients WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $b2b_row = $stmt->get_result()->fetch_assoc();
    $b2b_discount = $b2b_row['discount_percentage'] ?? 0;
}

// Merr produktet e shportës
$stmt = $conn->prepare("
    SELECT c.*, 
           p.name, p.price, p.b2b_price, p.image, p.unit, p.stock, p.id as product_id,
           pt.business_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN partners pt ON p.partner_id = pt.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Llogarit çmimin e saktë për secilin produkt
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
$discount_amount = ($is_b2b && $b2b_discount > 0 && !($items[0]['b2b_price'] ?? false))
                    ? array_sum(array_map(fn($i) => ($i['price'] - $i['effective_price']) * $i['quantity'], $items))
                    : 0;
$total = $subtotal;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4">🛒 Shporta ime
        <?php if ($is_b2b): ?>
        <span class="badge bg-info text-dark fs-6 ms-2">💼 B2B</span>
        <?php endif; ?>
    </h2>

    <?php if ($is_b2b && $b2b_discount > 0): ?>
    <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
        <div class="fs-3">🎁</div>
        <div>
            <strong>Zbritja juaj B2B: <?= $b2b_discount ?>%</strong> është aplikuar automatikisht në çmimet.
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <div class="text-center py-5">
        <div class="fs-1">🛒</div>
        <h4 class="text-muted">Shporta është bosh</h4>
        <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-success mt-3">Vazhdo blerjen</a>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <!-- Lista e produkteve -->
        <div class="col-lg-8">
            <?php foreach ($items as $item): ?>
            <div class="card border-0 shadow-sm mb-3" id="cart-item-<?= $item['product_id'] ?>">
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-2">
                            <img src="<?= $item['image'] ? SITE_URL . '/uploads/products/' . $item['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                                 class="img-fluid rounded" style="height:70px; object-fit:cover;">
                        </div>
                        <div class="col-4">
                            <h6 class="fw-bold mb-1"><?= $item['name'] ?></h6>
                            <small class="text-muted">🏪 <?= $item['business_name'] ?></small>
                        </div>
                        <div class="col-2">
                            <?php if ($item['effective_price'] < $item['price']): ?>
                                <small class="text-muted text-decoration-line-through"><?= formatPrice($item['price']) ?></small><br>
                                <span class="fw-bold text-success"><?= formatPrice($item['effective_price']) ?></span>
                            <?php else: ?>
                                <span class="fw-bold text-success"><?= formatPrice($item['effective_price']) ?></span>
                            <?php endif; ?>
                            <br><small class="text-muted">/<?= $item['unit'] ?></small>
                        </div>
                        <div class="col-2">
                            <input type="number" class="form-control form-control-sm qty-input"
                                   value="<?= $item['quantity'] ?>" min="0.5" step="0.5"
                                   data-product-id="<?= $item['product_id'] ?>">
                        </div>
                        <div class="col-1">
                            <span class="fw-bold small"><?= formatPrice($item['effective_price'] * $item['quantity']) ?></span>
                        </div>
                        <div class="col-1 text-end">
                            <button class="btn btn-sm btn-outline-danger remove-item"
                                    data-product-id="<?= $item['product_id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Përmbledhja -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">📋 Përmbledhja</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Nëntotali</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>🎁 Zbritja B2B (<?= $b2b_discount ?>%)</span>
                        <span>-<?= formatPrice($discount_amount) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Dërgesa</span>
                        <span class="text-success fw-semibold">Falas</span>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                        <span>Totali</span>
                        <span class="text-success"><?= formatPrice($total) ?></span>
                    </div>

                    <a href="<?= SITE_URL ?>/client/checkout.php" class="btn btn-success w-100 fw-bold py-2 fs-5">
                        <i class="fas fa-credit-card me-2"></i>Vazhdo Pagesën
                    </a>
                    <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-outline-secondary w-100 mt-2">
                        ← Vazhdo Blerjen
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';

document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function () {
        const productId = this.dataset.productId;
        fetch(SITE_URL + '/ajax/cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=remove&product_id=${productId}`
        }).then(r => r.json()).then(data => {
            if (data.success) {
                document.getElementById('cart-item-' + productId)?.remove();
                location.reload();
            }
        });
    });
});

document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function () {
        const productId = this.dataset.productId;
        const qty = this.value;
        fetch(SITE_URL + '/ajax/cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&product_id=${productId}&quantity=${qty}`
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>