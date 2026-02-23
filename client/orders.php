<?php
require_once __DIR__ . '/../config/config.php';
requireRole('client');
$page_title = 'Porositë e Mia';

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
    <h3 class="fw-bold mb-4">📦 Porositë e Mia</h3>

    <?php if (empty($orders)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="text-muted">Nuk ke bërë asnjë porosi ende</h5>
            <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-success mt-3">
                <i class="fas fa-shopping-bag me-2"></i>Fillo blerjen
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($orders as $order):
            $status_config = [
                'pending'    => ['color'=>'warning', 'label'=>'⏳ Pritëse',     'text'=>'dark'],
                'confirmed'  => ['color'=>'info',    'label'=>'✅ Konfirmuar',  'text'=>'dark'],
                'processing' => ['color'=>'primary',  'label'=>'⚙️ Procesim',  'text'=>'white'],
                'shipped'    => ['color'=>'dark',     'label'=>'🚚 Dërguar',   'text'=>'white'],
                'delivered'  => ['color'=>'success',  'label'=>'✔️ Dorëzuar',  'text'=>'white'],
                'cancelled'  => ['color'=>'danger',   'label'=>'❌ Anuluar',   'text'=>'white'],
            ];
            $sc = $status_config[$order['order_status']] ?? ['color'=>'secondary','label'=>$order['order_status'],'text'=>'white'];
        ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div>
                    <span class="fw-bold text-success fs-5">#<?= $order['order_number'] ?></span>
                    <span class="text-muted ms-3 small">
                        <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </span>
                </div>
                <span class="badge bg-<?= $sc['color'] ?> text-<?= $sc['text'] ?> fs-6 px-3 py-2">
                    <?= $sc['label'] ?>
                </span>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="small text-muted mb-1">📦 Produktet</div>
                        <div class="fw-semibold"><?= $order['products_list'] ?></div>
                        <div class="small text-muted mt-1"><?= $order['item_count'] ?> artikuj</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted mb-1">📍 Adresa e dërgimit</div>
                        <div class="fw-semibold small"><?= $order['delivery_address'] ?></div>
                        <div class="small text-muted"><?= $order['delivery_city'] ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted mb-1">💳 Pagesa</div>
                        <div class="fw-semibold"><?= ucfirst($order['payment_method']) ?></div>
                        <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>">
                            <?= $order['payment_status'] === 'paid' ? '✅ Paguar' : '⏳ Papaguar' ?>
                        </span>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="small text-muted mb-1">💰 Totali</div>
                        <div class="fw-bold fs-4 text-success"><?= formatPrice($order['final_amount']) ?></div>
                    </div>
                </div>

                <!-- Progress bar gjurmimt -->
                <?php
                $steps = ['pending','confirmed','processing','shipped','delivered'];
                $current_step = array_search($order['order_status'], $steps);
                if ($order['order_status'] !== 'cancelled' && $current_step !== false):
                ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="small text-muted mb-2 fw-semibold">🚚 Gjurmimi i Porosisë</div>
                    <div class="d-flex justify-content-between position-relative" style="padding: 0 5%;">
                        <div class="position-absolute" style="top:14px;left:10%;right:10%;height:3px;background:#dee2e6;z-index:0;">
                            <div style="width:<?= $current_step > 0 ? ($current_step / (count($steps)-1)) * 100 : 0 ?>%;height:100%;background:#198754;transition:width 0.5s;"></div>
                        </div>
                        <?php
                        $step_labels = ['Pritëse','Konfirmuar','Procesim','Dërguar','Dorëzuar'];
                        $step_icons  = ['📋','✅','⚙️','🚚','🏠'];
                        foreach ($steps as $i => $step):
                            $done = $i <= $current_step;
                        ?>
                        <div class="text-center position-relative" style="z-index:1;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 fw-bold"
                                 style="width:30px;height:30px;font-size:0.8rem;background:<?= $done ? '#198754' : '#dee2e6' ?>;color:<?= $done ? '#fff' : '#999' ?>;">
                                <?= $done ? '✓' : ($i + 1) ?>
                            </div>
                            <div class="small <?= $done ? 'text-success fw-semibold' : 'text-muted' ?>" style="font-size:0.75rem;">
                                <?= $step_labels[$i] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($order['notes']): ?>
            <div class="card-footer bg-transparent">
                <small class="text-muted"><strong>📝 Shënimet tuaja:</strong> <?= $order['notes'] ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>