<?php
require_once __DIR__ . '/../config/config.php';
requireRole('client');
$page_title = 'Dashboard';

$user_id = $_SESSION['user_id'];

$total_orders     = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetch_row()[0];
$total_spent      = $conn->query("SELECT COALESCE(SUM(final_amount),0) FROM orders WHERE user_id = $user_id AND order_status != 'cancelled'")->fetch_row()[0];
$pending_orders   = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status = 'pending'")->fetch_row()[0];
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status = 'delivered'")->fetch_row()[0];

// Porositë e fundit
$recent_orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Info user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar profil -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:80px;height:80px;font-size:2rem;color:white;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= $user['name'] ?></h5>
                    <p class="text-muted small mb-2"><?= $user['email'] ?></p>
                    <span class="badge bg-success">👤 Klient</span>
                </div>
            </div>
            <div class="list-group border-0 shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action active border-0">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-box me-2"></i>Porositë e Mia
                </a>
                <a href="cart.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-shopping-cart me-2"></i>Shporta
                </a>
                <a href="<?= SITE_URL ?>/pages/products.php" class="list-group-item list-group-item-action border-0">
                    <i class="fas fa-store me-2"></i>Shfleto Produktet
                </a>
            </div>
        </div>

        <!-- Kryesor -->
        <div class="col-md-9">
            <h4 class="fw-bold mb-4">👋 Mirësevini, <?= explode(' ', $user['name'])[0] ?>!</h4>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid #198754;">
                        <div class="card-body">
                            <div class="fs-2 mb-1">🛒</div>
                            <h3 class="fw-bold mb-0 text-success"><?= $total_orders ?></h3>
                            <div class="small text-muted">Porosi Totale</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid #ffc107;">
                        <div class="card-body">
                            <div class="fs-2 mb-1">⏳</div>
                            <h3 class="fw-bold mb-0 text-warning"><?= $pending_orders ?></h3>
                            <div class="small text-muted">Pritëse</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid #0d6efd;">
                        <div class="card-body">
                            <div class="fs-2 mb-1">✔️</div>
                            <h3 class="fw-bold mb-0 text-primary"><?= $delivered_orders ?></h3>
                            <div class="small text-muted">Dorëzuar</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid #dc3545;">
                        <div class="card-body">
                            <div class="fs-2 mb-1">💰</div>
                            <div class="fw-bold text-danger" style="font-size:1.3rem;"><?= formatPrice($total_spent) ?></div>
                            <div class="small text-muted">Total shpenzuar</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Porositë e fundit -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">📦 Porositë e Fundit</h6>
                    <a href="orders.php" class="btn btn-sm btn-outline-success">Shiko të gjitha</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#Porosi</th><th>Data</th><th>Totali</th><th>Pagesa</th><th>Statusi</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Nuk ka porosi</td></tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $o):
                                $colors = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','shipped'=>'dark','delivered'=>'success','cancelled'=>'danger'];
                            ?>
                            <tr>
                                <td class="fw-semibold text-success"><?= $o['order_number'] ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                <td class="fw-bold"><?= formatPrice($o['final_amount']) ?></td>
                                <td><span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>"><?= $o['payment_status'] === 'paid' ? '✅ Paguar' : '⏳ Papaguar' ?></span></td>
                                <td><span class="badge bg-<?= $colors[$o['order_status']] ?? 'secondary' ?>"><?= ucfirst($o['order_status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent"><h6 class="fw-bold mb-0">⚡ Veprime të Shpejta</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-outline-success w-100 py-3">
                                <div class="fs-3 mb-1">🛍️</div>
                                <div class="small">Bli Tani</div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="cart.php" class="btn btn-outline-warning w-100 py-3">
                                <div class="fs-3 mb-1">🛒</div>
                                <div class="small">Shporta</div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="orders.php" class="btn btn-outline-primary w-100 py-3">
                                <div class="fs-3 mb-1">📦</div>
                                <div class="small">Porositë</div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= SITE_URL ?>/pages/products.php?certified=1" class="btn btn-outline-dark w-100 py-3">
                                <div class="fs-3 mb-1">✅</div>
                                <div class="small">Certifikuara</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<h6>-</h6>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>