<?php
require_once __DIR__ . '/../config/config.php';
requireRole('partner');
$page_title = 'Porositë - Partner';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$pid = $partner['id'];

// Update status porosie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id  = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['new_status']);
    $allowed = ['confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($new_status, $allowed)) {
        $conn->query("UPDATE orders SET order_status = '$new_status', updated_at = NOW() WHERE id = $order_id");
        setFlash('success', 'Statusi i porosisë u ndryshua.');
    }
    header('Location: orders.php');
    exit;
}

// Filter
$filter = $_GET['status'] ?? 'all';
$where = "oi.partner_id = $pid";
if ($filter !== 'all') $where .= " AND o.order_status = '" . $conn->real_escape_string($filter) . "'";

$orders = $conn->query("
    SELECT o.*, u.name as client_name, u.phone as client_phone,
           GROUP_CONCAT(p.name SEPARATOR ', ') as products_list,
           SUM(oi.total_price) as partner_total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-2">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">🛒 Porositë e Mia <span class="badge bg-success"><?= count($orders) ?></span></h4>
            </div>

            <!-- Filter tabs -->
            <div class="nav nav-pills mb-4 gap-2">
                <?php
                $statuses = [
                    'all'        => ['label'=>'Të Gjitha', 'color'=>'secondary'],
                    'pending'    => ['label'=>'Pritëse',   'color'=>'warning'],
                    'confirmed'  => ['label'=>'Konfirmuara','color'=>'info'],
                    'processing' => ['label'=>'Në Procesim','color'=>'primary'],
                    'shipped'    => ['label'=>'Dërguar',   'color'=>'dark'],
                    'delivered'  => ['label'=>'Dorëzuar',  'color'=>'success'],
                    'cancelled'  => ['label'=>'Anuluar',   'color'=>'danger'],
                ];
                foreach ($statuses as $key => $s):
                    $active = ($filter === $key) ? "bg-{$s['color']} text-white" : "btn-outline-{$s['color']}";
                ?>
                <a href="?status=<?= $key ?>" class="btn btn-sm <?= $active ?>"><?= $s['label'] ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="fs-1">🛒</div>
                    <h5 class="text-muted mt-2">Nuk ka porosi</h5>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($orders as $order): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold text-success">#<?= $order['order_number'] ?></span>
                            <span class="text-muted ms-3 small"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <?php
                        $colors = [
                            'pending'=>'warning','confirmed'=>'info',
                            'processing'=>'primary','shipped'=>'dark',
                            'delivered'=>'success','cancelled'=>'danger'
                        ];
                        $labels = [
                            'pending'=>'⏳ Pritëse','confirmed'=>'✅ Konfirmuar',
                            'processing'=>'⚙️ Procesim','shipped'=>'🚚 Dërguar',
                            'delivered'=>'✔️ Dorëzuar','cancelled'=>'❌ Anuluar'
                        ];
                        ?>
                        <span class="badge bg-<?= $colors[$order['order_status']] ?? 'secondary' ?> fs-6 px-3">
                            <?= $labels[$order['order_status']] ?? $order['order_status'] ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="small text-muted mb-1">👤 Klienti</div>
                                    <div class="fw-bold"><?= $order['client_name'] ?></div>
                                    <?php if ($order['client_phone']): ?>
                                    <div class="small"><i class="fas fa-phone me-1 text-success"></i><?= $order['client_phone'] ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-map-marker-alt me-1"></i><?= $order['delivery_address'] ?>, <?= $order['delivery_city'] ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="small text-muted mb-1">📦 Produktet</div>
                                    <div class="fw-semibold small"><?= $order['products_list'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100 text-center">
                                    <div class="small text-muted mb-1">💰 Totali</div>
                                    <div class="fw-bold fs-4 text-success"><?= formatPrice($order['partner_total']) ?></div>
                                    <div class="small text-muted"><?= ucfirst($order['payment_method']) ?></div>
                                    <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>">
                                        <?= $order['payment_status'] === 'paid' ? '✅ Paguar' : '⏳ Papaguar' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($order['notes']): ?>
                        <div class="alert alert-info mt-3 py-2 mb-0">
                            <small><strong>📝 Shënime:</strong> <?= $order['notes'] ?></small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!in_array($order['order_status'], ['delivered','cancelled'])): ?>
                    <div class="card-footer bg-transparent d-flex gap-2">
                        <form method="POST" class="d-flex gap-2 w-100">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="new_status" class="form-select form-select-sm" style="max-width:200px;">
                                <option value="">Ndrysho statusin...</option>
                                <?php foreach ($statuses as $key => $s): ?>
                                    <?php if ($key !== 'all' && $key !== $order['order_status']): ?>
                                    <option value="<?= $key ?>"><?= $s['label'] ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-success btn-sm px-3">Ndrysho</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>