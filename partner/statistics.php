<?php
require_once __DIR__ . '/../config/config.php';
requireRole('partner');
$page_title = 'Statistika';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$pid = $partner['id'];

// Stats kryesore
$total_revenue  = $conn->query("SELECT COALESCE(SUM(oi.total_price),0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.partner_id = $pid AND o.order_status != 'cancelled'")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.partner_id = $pid")->fetch_row()[0];
$total_products = $conn->query("SELECT COUNT(*) FROM products WHERE partner_id = $pid")->fetch_row()[0];
$avg_rating     = $conn->query("SELECT COALESCE(AVG(r.rating),0) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.partner_id = $pid")->fetch_row()[0];

// Shitjet 7 ditët e fundit
$weekly = $conn->query("
    SELECT DATE(o.created_at) as date, COALESCE(SUM(oi.total_price),0) as total
    FROM orders o JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.partner_id = $pid AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND o.order_status != 'cancelled'
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
")->fetch_all(MYSQLI_ASSOC);

// Top produktet
$top_products = $conn->query("
    SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_revenue, COUNT(*) as orders_count
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    WHERE oi.partner_id = $pid
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Shpërndarja e statuseve
$status_dist = $conn->query("
    SELECT o.order_status, COUNT(DISTINCT oi.order_id) as cnt
    FROM order_items oi JOIN orders o ON oi.order_id = o.id
    WHERE oi.partner_id = $pid
    GROUP BY o.order_status
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
            <h4 class="fw-bold mb-4">📊 Statistikat e Biznesit</h4>

            <!-- KPI Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #198754 !important;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small mb-1">💰 Të Ardhura Totale</div>
                                <div class="fw-bold fs-4 text-success"><?= formatPrice($total_revenue) ?></div>
                            </div>
                            <div class="fs-1 opacity-25">💰</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0d6efd !important;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small mb-1">🛒 Porosi Totale</div>
                                <div class="fw-bold fs-4 text-primary"><?= $total_orders ?></div>
                            </div>
                            <div class="fs-1 opacity-25">🛒</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ffc107 !important;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small mb-1">📦 Produkte Aktive</div>
                                <div class="fw-bold fs-4 text-warning"><?= $total_products ?></div>
                            </div>
                            <div class="fs-1 opacity-25">📦</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #dc3545 !important;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small mb-1">⭐ Vlerësimi Mesatar</div>
                                <div class="fw-bold fs-4 text-danger"><?= number_format($avg_rating,1) ?>/5</div>
                            </div>
                            <div class="fs-1 opacity-25">⭐</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Grafiku i shitjeve javore -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold">📈 Shitjet — 7 Ditët e Fundit</div>
                        <div class="card-body">
                            <?php if (empty($weekly)): ?>
                            <p class="text-center text-muted py-4">Nuk ka të dhëna.</p>
                            <?php else: ?>
                            <canvas id="weeklyChart" height="80"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statuset -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent fw-bold">📊 Gjendja Porosive</div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top produktet -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold">🏆 Top 5 Produktet</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>#</th><th>Produkti</th><th>Sasia e shitur</th><th>Porosi</th><th>Të Ardhura</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $i => $tp): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?= $i === 0 ? 'warning text-dark' : ($i === 1 ? 'secondary' : 'light text-dark') ?> fs-6">
                                                <?= ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold"><?= $tp['name'] ?></td>
                                        <td><?= number_format($tp['total_qty'],1) ?></td>
                                        <td><?= $tp['orders_count'] ?></td>
                                        <td class="fw-bold text-success"><?= formatPrice($tp['total_revenue']) ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($weekly)): ?>
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($weekly, 'date')) ?>,
        datasets: [{
            label: 'Të Ardhura (ALL)',
            data: <?= json_encode(array_column($weekly, 'total')) ?>,
            backgroundColor: 'rgba(25,135,84,0.7)',
            borderColor: '#198754',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
<?php endif; ?>

<?php if (!empty($status_dist)): ?>
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusColors = {
    'pending':'#ffc107','confirmed':'#0dcaf0','processing':'#0d6efd',
    'shipped':'#6c757d','delivered':'#198754','cancelled':'#dc3545'
};
const statusLabels = {
    'pending':'Pritëse','confirmed':'Konfirmuara','processing':'Procesim',
    'shipped':'Dërguar','delivered':'Dorëzuar','cancelled':'Anuluar'
};
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($s) => $statusLabels[$s['order_status']] ?? $s['order_status'], $status_dist)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($status_dist, 'cnt')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($s) => $statusColors[$s['order_status']] ?? '#999', $status_dist)) ?>
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>