<?php
require_once __DIR__ . '/../config/config.php';
requireRole('b2b');
$page_title = 'Faturat';

$user_id = $_SESSION['user_id'];
$b2b_stmt = $conn->prepare("SELECT * FROM b2b_clients WHERE user_id = ?");
$b2b_stmt->bind_param("i", $user_id);
$b2b_stmt->execute();
$b2b = $b2b_stmt->get_result()->fetch_assoc();

// Fatura specifike
$order_id = (int)($_GET['order_id'] ?? 0);
$print_mode = isset($_GET['print']);

if ($order_id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.name as client_name, u.email as client_email, u.phone as client_phone
        FROM orders o JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) { header('Location: invoices.php'); exit; }

    $items = $conn->query("
        SELECT oi.*, p.name as product_name, p.unit, pt.business_name
        FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN partners pt ON oi.partner_id = pt.id
        WHERE oi.order_id = $order_id
    ")->fetch_all(MYSQLI_ASSOC);

    // Printo faturën
    if ($print_mode):
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Fatura #<?= $order['order_number'] ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size:14px; color:#333; }
        .invoice { max-width:800px; margin:30px auto; padding:40px; }
        .header { display:flex; justify-content:space-between; border-bottom:3px solid #198754; padding-bottom:20px; margin-bottom:30px; }
        .logo { font-size:2rem; font-weight:bold; color:#198754; }
        .invoice-info { text-align:right; }
        .invoice-number { font-size:1.5rem; font-weight:bold; color:#198754; }
        .section { display:flex; justify-content:space-between; margin-bottom:30px; }
        .box { width:48%; }
        .box h4 { border-bottom:1px solid #ddd; padding-bottom:8px; margin-bottom:10px; color:#198754; }
        table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        th { background:#198754; color:white; padding:10px; text-align:left; }
        td { padding:10px; border-bottom:1px solid #eee; }
        tr:nth-child(even) { background:#f9f9f9; }
        .totals { text-align:right; }
        .total-row { font-size:1.3rem; font-weight:bold; color:#198754; border-top:2px solid #198754; padding-top:10px; }
        .footer { text-align:center; margin-top:40px; color:#999; font-size:12px; border-top:1px solid #eee; padding-top:20px; }
        .badge { background:#198754; color:white; padding:3px 10px; border-radius:50px; font-size:12px; }
        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body>
<div class="invoice">
    <div class="header">
        <div>
            <div class="logo">🌿 FrutaMarket</div>
            <div style="color:#666;margin-top:5px;">Platformë Ushqimore e Certifikuar</div>
            <div style="color:#666;font-size:12px;">info@frutamarket.al | +355 69 XXX XXXX</div>
        </div>
        <div class="invoice-info">
            <div class="invoice-number">FATURA</div>
            <div style="font-size:1.2rem;">#<?= $order['order_number'] ?></div>
            <div style="color:#666;margin-top:5px;">Data: <?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
            <span class="badge"><?= strtoupper($order['payment_status']) ?></span>
        </div>
    </div>

    <div class="section">
        <div class="box">
            <h4>Lëshuar Nga</h4>
            <p><strong>FrutaMarket SH.P.K</strong></p>
            <p>Rruga e Durrësit, Tiranë</p>
            <p>NIPT: KXXXXXXX</p>
        </div>
        <div class="box">
            <h4>Fatura Për</h4>
            <p><strong><?= $b2b['business_name'] ?? $order['client_name'] ?></strong></p>
            <p><?= $order['delivery_address'] ?></p>
            <p><?= $order['delivery_city'] ?></p>
            <p><?= $order['client_email'] ?></p>
            <?php if ($b2b['nipt']): ?><p>NIPT: <?= $b2b['nipt'] ?></p><?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Produkti</th><th>Furnitori</th><th>Sasia</th><th>Çmimi/Njësi</th><th>Totali</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= $item['product_name'] ?></td>
                <td><?= $item['business_name'] ?></td>
                <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                <td><?= formatPrice($item['unit_price']) ?></td>
                <td><strong><?= formatPrice($item['total_price']) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table style="width:300px;margin-left:auto;">
            <tr><td>Nëntotali:</td><td><strong><?= formatPrice($order['total_amount']) ?></strong></td></tr>
            <?php if ($order['discount_amount'] > 0): ?>
            <tr style="color:#198754;"><td>Zbritja:</td><td><strong>-<?= formatPrice($order['discount_amount']) ?></strong></td></tr>
            <?php endif; ?>
            <tr><td>Dërgesa:</td><td><strong><?= $order['delivery_fee'] > 0 ? formatPrice($order['delivery_fee']) : 'Falas' ?></strong></td></tr>
            <tr class="total-row"><td><strong>TOTALI:</strong></td><td><strong><?= formatPrice($order['final_amount']) ?></strong></td></tr>
        </table>
    </div>

    <?php if ($order['notes']): ?>
    <div style="margin-top:20px;padding:15px;background:#f0fdf4;border-left:4px solid #198754;border-radius:4px;">
        <strong>📝 Shënime:</strong> <?= $order['notes'] ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Faleminderit që zgjodhët FrutaMarket! Për pyetje: info@frutamarket.al</p>
        <p>Kjo faturë u gjenerua automatikisht nga sistemi i FrutaMarket.</p>
    </div>
</div>

<div class="no-print" style="text-align:center;margin:20px;">
    <button onclick="window.print()" style="background:#198754;color:white;border:none;padding:12px 30px;border-radius:8px;cursor:pointer;font-size:16px;">🖨️ Printo Faturën</button>
    <a href="invoices.php" style="margin-left:15px;text-decoration:none;color:#666;">← Kthehu</a>
</div>
</body>
</html>
<?php
    exit;
    endif; // end print_mode
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Lista e faturave
$orders = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="list-group border-0 shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action border-0"><i class="fas fa-home me-2 text-success"></i>Dashboard</a>
                <a href="orders.php" class="list-group-item list-group-item-action border-0"><i class="fas fa-box me-2 text-success"></i>Porositë</a>
                <a href="invoices.php" class="list-group-item list-group-item-action active border-0"><i class="fas fa-file-invoice me-2"></i>Faturat</a>
            </div>
        </div>
        <div class="col-md-9">
            <h4 class="fw-bold mb-4">📄 Faturat e Mia</h4>

            <?php if (isset($order)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Fatura #<?= $order['order_number'] ?></h5>
                    <a href="?order_id=<?= $order['id'] ?>&print=1" target="_blank" class="btn btn-success">
                        🖨️ Printo / Shkarko PDF
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light"><tr><th>Produkti</th><th>Furnitori</th><th>Sasia</th><th>Çmimi</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $item['product_name'] ?></td>
                                    <td><?= $item['business_name'] ?></td>
                                    <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                                    <td><?= formatPrice($item['unit_price']) ?></td>
                                    <td class="fw-bold"><?= formatPrice($item['total_price']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr><td colspan="4" class="text-end fw-bold">TOTALI:</td><td class="fw-bold fs-5 text-success"><?= formatPrice($order['final_amount']) ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>#Fatura</th><th>Data</th><th>Artikuj</th><th>Totali</th><th>Pagesa</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="fw-semibold text-success"><?= $o['order_number'] ?></td>
                                <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                <td><?= $o['item_count'] ?> artikuj</td>
                                <td class="fw-bold"><?= formatPrice($o['final_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>">
                                        <?= $o['payment_status'] === 'paid' ? '✅ Paguar' : '⏳ Papaguar' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="?order_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">👁 Shiko</a>
                                        <a href="?order_id=<?= $o['id'] ?>&print=1" target="_blank" class="btn btn-sm btn-outline-success">🖨️ Printo</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<h1>1</h1>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>