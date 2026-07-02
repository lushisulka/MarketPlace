<?php
require_once __DIR__ . '/../config/config.php';
requireRole('b2b');
$page_title = 'B2B Dashboard';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM b2b_clients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$b2b = $stmt->get_result()->fetch_assoc();

$total_orders   = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND is_b2b = 1")->fetch_row()[0];
$total_spent    = $conn->query("SELECT COALESCE(SUM(final_amount),0) FROM orders WHERE user_id = $user_id AND order_status != 'cancelled'")->fetch_row()[0];
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status = 'pending'")->fetch_row()[0];

$recent_orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Partners me produktet
$partners = $conn->query("
    SELECT pt.*, u.name, COUNT(p.id) as product_count
    FROM partners pt JOIN users u ON pt.user_id = u.id
    LEFT JOIN products p ON pt.id = p.partner_id AND p.status = 'active' AND p.b2b_price IS NOT NULL
    WHERE pt.status = 'approved'
    GROUP BY pt.id
    ORDER BY pt.is_certified DESC, pt.rating DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <!-- Status banner -->
    <?php if ($b2b && $b2b['status'] === 'pending'): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
        <div class="fs-3">⏳</div>
        <div>
            <strong>Llogaria juaj B2B është në pritje të aprovimit.</strong>
            <br><span class="text-muted small">Admini do të rishikojë aplikimin tuaj brenda 24 orësh.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body text-center py-4">
                    <div class="fs-1 mb-2">🏨</div>
                    <h5 class="fw-bold mb-1"><?= $b2b['business_name'] ?? $_SESSION['user_name'] ?></h5>
                    <p class="text-muted small mb-2"><?= ucfirst($b2b['business_type'] ?? '') ?></p>
                    <?php if ($b2b['discount_percentage'] > 0): ?>
                    <span class="badge bg-success">🎁 <?= $b2b['discount_percentage'] ?>% Zbritje B2B</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="list-group border-0 shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action active border-0">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-box me-2"></i>Porositë
                </a>
                <a href="invoices.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-file-invoice me-2"></i>Faturat
                </a>
                <a href="<?= SITE_URL ?>/pages/products.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-shopping-bag me-2"></i>Produktet
                </a>
            </div>
        </div>

        <!-- Kryesor -->
        <div class="col-md-9">
            <h4 class="fw-bold mb-4">💼 Dashboard B2B</h4>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center h-100 p-3" style="border-top:3px solid #198754;">
                        <div class="fs-2 mb-1">🛒</div>
                        <h2 class="fw-bold text-success"><?= $total_orders ?></h2>
                        <div class="text-muted small">Porosi B2B</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center h-100 p-3" style="border-top:3px solid #ffc107;">
                        <div class="fs-2 mb-1">⏳</div>
                        <h2 class="fw-bold text-warning"><?= $pending_orders ?></h2>
                        <div class="text-muted small">Pritëse</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center h-100 p-3" style="border-top:3px solid #0d6efd;">
                        <div class="fs-2 mb-1">💰</div>
                        <div class="fw-bold text-primary" style="font-size:1.4rem;"><?= formatPrice($total_spent) ?></div>
                        <div class="text-muted small">Total shpenzuar</div>
                    </div>
                </div>
            </div>

            <!-- Furnitorët B2B -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">🏪 Furnitorë B2B të Disponueshëm</h6>
                    <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-sm btn-outline-success">Shiko të Gjitha</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($partners as $pt): ?>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center hover-shadow">
                                <div>
                                    <h6 class="fw-bold mb-1"><?= $pt['business_name'] ?></h6>
                                    <?php if ($pt['is_certified']): ?>
                                    <span class="badge" style="background:linear-gradient(135deg,#ffc107,#ff8f00);color:#000;font-size:0.7rem;">✅ Certifikuar</span>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">
                                        <span style="color:#ffc107;">★</span> <?= number_format($pt['rating'],1) ?>
                                        &nbsp;|&nbsp; <?= $pt['product_count'] ?> produkte B2B
                                    </div>
                                </div>
                                <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $pt['id'] ?>" class="btn btn-outline-success btn-sm">Shiko</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Porosi të fundit -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">📦 Porositë e Fundit</h6>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">Të gjitha</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>#</th><th>Data</th><th>Totali</th><th>Statusi</th></tr></thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Nuk ka porosi</td></tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $o):
                                $colors = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','shipped'=>'dark','delivered'=>'success','cancelled'=>'danger'];
                            ?>
                            <tr>
                                <td class="text-success fw-semibold small"><?= $o['order_number'] ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                <td class="fw-bold"><?= formatPrice($o['final_amount']) ?></td>
                                <td><span class="badge bg-<?= $colors[$o['order_status']] ?? 'secondary' ?>"><?= ucfirst($o['order_status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<h1>1</h1>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>