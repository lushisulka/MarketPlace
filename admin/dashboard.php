<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Admin Panel';

$total_users    = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetch_row()[0];
$total_partners = $conn->query("SELECT COUNT(*) FROM partners")->fetch_row()[0];
$pending_partners = $conn->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT SUM(final_amount) FROM orders WHERE payment_status = 'paid'")->fetch_row()[0] ?? 0;
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];

// Partnerë në pritje
$pending = $conn->query("SELECT pt.*, u.name, u.email FROM partners pt JOIN users u ON pt.user_id = u.id WHERE pt.status = 'pending' LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Porosi të fundit
$recent_orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Aprovim/Refuzim i shpejtë
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partner_action'])) {
    $pid = (int)$_POST['partner_id'];
    $action = $_POST['partner_action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $certified = ($action === 'approve') ? 1 : 0;
    $conn->query("UPDATE partners SET status = '$status', is_certified = $certified WHERE id = $pid");
    setFlash('success', 'Partneri u ' . ($action === 'approve' ? 'aprovua' : 'refuzua') . ' me sukses!');
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-2">🛡️</div>
                    <strong class="small">Admin Panel</strong>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item"><a href="dashboard.php" class="text-decoration-none"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                    <li class="list-group-item"><a href="users.php" class="text-decoration-none"><i class="fas fa-users me-2"></i>Përdoruesit</a></li>
                    <li class="list-group-item"><a href="partners.php" class="text-decoration-none"><i class="fas fa-store me-2"></i>Partnerët <span class="badge bg-danger"><?= $pending_partners ?></span></a></li>
                    <li class="list-group-item"><a href="products.php" class="text-decoration-none"><i class="fas fa-boxes me-2"></i>Produktet</a></li>
                    <li class="list-group-item"><a href="orders.php" class="text-decoration-none"><i class="fas fa-shopping-cart me-2"></i>Porositë</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main -->
        <div class="col-md-10">
            <h4 class="fw-bold mb-4">📊 Admin Dashboard</h4>
            
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-primary text-white">
                        <div class="card-body py-3">
                            <div class="fs-3">👥</div>
                            <h4 class="fw-bold mb-0"><?= $total_users ?></h4>
                            <small>Përdorues</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-success text-white">
                        <div class="card-body py-3">
                            <div class="fs-3">🏪</div>
                            <h4 class="fw-bold mb-0"><?= $total_partners ?></h4>
                            <small>Partnerë</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-warning text-dark">
                        <div class="card-body py-3">
                            <div class="fs-3">⏳</div>
                            <h4 class="fw-bold mb-0"><?= $pending_partners ?></h4>
                            <small>Në Pritje</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-info text-white">
                        <div class="card-body py-3">
                            <div class="fs-3">📦</div>
                            <h4 class="fw-bold mb-0"><?= $total_products ?></h4>
                            <small>Produkte</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-secondary text-white">
                        <div class="card-body py-3">
                            <div class="fs-3">🛒</div>
                            <h4 class="fw-bold mb-0"><?= $total_orders ?></h4>
                            <small>Porosi</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xl-2">
                    <div class="card border-0 shadow-sm text-center bg-dark text-white">
                        <div class="card-body py-3">
                            <div class="fs-3">💰</div>
                            <h5 class="fw-bold mb-0"><?= number_format($total_revenue, 0) ?></h5>
                            <small>ALL të ardhura</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Partnerë në pritje -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark fw-bold">
                            ⏳ Partnerë Në Pritje (<?= $pending_partners ?>)
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending)): ?>
                            <p class="text-center text-muted py-4">Asnjë partner në pritje</p>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending as $p): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= $p['business_name'] ?></strong>
                                            <br><small class="text-muted"><?= $p['email'] ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="partner_action" value="approve">
                                                <button class="btn btn-success btn-sm">✅ Aprovo</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="partner_action" value="reject">
                                                <button class="btn btn-danger btn-sm">❌ Refuzo</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Porosi të fundit -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold">🛒 Porositë e Fundit</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light"><tr><th>#</th><th>Klienti</th><th>Totali</th><th>Statusi</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td><small class="text-muted"><?= $o['order_number'] ?></small></td>
                                        <td><?= $o['user_name'] ?></td>
                                        <td class="fw-bold"><?= formatPrice($o['final_amount']) ?></td>
                                        <td>
                                            <?php $colors = ['pending'=>'warning','confirmed'=>'info','delivered'=>'success','cancelled'=>'danger']; ?>
                                            <span class="badge bg-<?= $colors[$o['order_status']] ?? 'secondary' ?>">
                                                <?= ucfirst($o['order_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>