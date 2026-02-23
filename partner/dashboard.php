<?php
require_once __DIR__ . '/../config/config.php';
requireRole('partner');
$page_title = 'Dashboard Partner';
$user_id = $_SESSION['user_id'];

// Merr info partnerit
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();

if (!$partner) {
    setFlash('error', 'Profili i partnerit nuk u gjet.');
    header('Location: ' . SITE_URL);
    exit;
}

$pid = $partner['id'];

// Statistika
$total_products = $conn->query("SELECT COUNT(*) FROM products WHERE partner_id = $pid")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE partner_id = $pid")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT SUM(total_price) FROM order_items WHERE partner_id = $pid")->fetch_row()[0] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.partner_id = $pid AND o.order_status = 'pending'")->fetch_row()[0];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="fs-1 mb-2">🏪</div>
                    <h6 class="fw-bold"><?= $partner['business_name'] ?></h6>
                    <?php if ($partner['is_certified']): ?>
                    <span class="badge bg-warning text-dark">✅ Partner i Certifikuar</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">⏳ Në pritje</span>
                    <?php endif; ?>
                    <?php if ($partner['status'] === 'pending'): ?>
                    <div class="alert alert-warning mt-3 small">
                        Llogaria juaj është në pritje të aprovimit nga admini.
                    </div>
                    <?php endif; ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="dashboard.php" class="text-decoration-none"><i class="fas fa-tachometer-alt me-2 text-success"></i>Dashboard</a></li>
                    <li class="list-group-item"><a href="products.php" class="text-decoration-none"><i class="fas fa-boxes me-2 text-success"></i>Produktet</a></li>
                    <li class="list-group-item"><a href="add_product.php" class="text-decoration-none"><i class="fas fa-plus me-2 text-success"></i>Shto Produkt</a></li>
                    <li class="list-group-item"><a href="orders.php" class="text-decoration-none"><i class="fas fa-shopping-bag me-2 text-success"></i>Porositë</a></li>
                    <li class="list-group-item"><a href="statistics.php" class="text-decoration-none"><i class="fas fa-chart-bar me-2 text-success"></i>Statistika</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h4 class="fw-bold mb-4">📊 Pasqyra e Biznesit</h4>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Produktet</p>
                                    <h3 class="fw-bold mb-0 text-success"><?= $total_products ?></h3>
                                </div>
                                <div class="fs-2">📦</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Porositë Totale</p>
                                    <h3 class="fw-bold mb-0 text-primary"><?= $total_orders ?></h3>
                                </div>
                                <div class="fs-2">🛒</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Të ardhurat</p>
                                    <h3 class="fw-bold mb-0 text-warning"><?= formatPrice($total_revenue) ?></h3>
                                </div>
                                <div class="fs-2">💰</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Porosi Pritëse</p>
                                    <h3 class="fw-bold mb-0 text-danger"><?= $pending_orders ?></h3>
                                </div>
                                <div class="fs-2">⏳</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Produkte te fundit -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">📦 Produktet e mia</h6>
                    <a href="add_product.php" class="btn btn-success btn-sm">+ Shto produkt</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php
                        $prods = $conn->query("SELECT p.*, c.name as cat FROM products p JOIN categories c ON p.category_id = c.id WHERE p.partner_id = $pid ORDER BY p.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Produkti</th><th>Kategoria</th><th>Çmimi</th><th>Stoku</th><th>Statusi</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prods as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $p['name'] ?></td>
                                    <td><span class="badge bg-light text-dark"><?= $p['cat'] ?></span></td>
                                    <td><?= formatPrice($p['price']) ?></td>
                                    <td><?= $p['stock'] ?> <?= $p['unit'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td><a href="add_product.php?edit=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm">Edit</a></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>