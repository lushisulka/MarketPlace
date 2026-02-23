<?php
require_once __DIR__ . '/../config/config.php';
requireRole('b2b');
$page_title = 'Porositë B2B';

$user_id = $_SESSION['user_id'];

$orders = $conn->query("
    SELECT o.*,
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(p.name SEPARATOR ', ') as products_list
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="list-group border-0 shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action border-0"><i class="fas fa-home me-2 text-success"></i>Dashboard</a>
                <a href="orders.php" class="list-group-item list-group-item-action active border-0"><i class="fas fa-box me-2"></i>Porositë</a>
                <a href="invoices.php" class="list-group-item list-group-item-action border-0"><i class="fas fa-file-invoice me-2 text-success"></i>Faturat</a>
            </div>
        </div>
        <div class="col-md-9">
            <h4 class="fw-bold mb-4">🛒 Porositë e Mia B2B</h4>

            <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="fs-1">📭</div>
                <h5 class="text-muted">Nuk ka porosi</h5>
                <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-success mt-3">Bëj Porosi</a>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($orders as $o):
                    $colors = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','shipped'=>'dark','delivered'=>'success','cancelled'=>'danger'];
                    $labels = ['pending'=>'⏳ Pritëse','confirmed'=>'✅ Konfirmuar','processing'=>'⚙️ Procesim','shipped'=>'🚚 Dërguar','delivered'=>'✔️ Dorëzuar','cancelled'=>'❌ Anuluar'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold text-success">#<?= $o['order_number'] ?></span>
                            <span class="ms-3 text-muted small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <a href="invoices.php?order_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                📄 Fatura
                            </a>
                            <span class="badge bg-<?= $colors[$o['order_status']] ?? 'secondary' ?> px-3 py-2">
                                <?= $labels[$o['order_status']] ?? $o['order_status'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">📦 Produktet (<?= $o['item_count'] ?> artikuj)</div>
                                <div class="fw-semibold"><?= $o['products_list'] ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1">📍 Adresa</div>
                                <div class="small"><?= $o['delivery_address'] ?></div>
                                <div class="small text-muted"><?= $o['delivery_city'] ?></div>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="small text-muted mb-1">💰 Total</div>
                                <div class="fw-bold fs-4 text-success"><?= formatPrice($o['final_amount']) ?></div>
                                <span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>">
                                    <?= $o['payment_status'] === 'paid' ? '✅ Paguar' : '⏳ Papaguar' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>