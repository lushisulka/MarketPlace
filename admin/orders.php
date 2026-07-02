<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Menaxhimi i Porosive';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid    = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    $pay    = sanitize($_POST['payment_status'] ?? '');
    $allowed = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $sql = "UPDATE orders SET order_status = '$status', updated_at = NOW()";
        if ($pay) $sql .= ", payment_status = '$pay'";
        $sql .= " WHERE id = $oid";
        $conn->query($sql);
        setFlash('success', 'Porosia u përditësua.');
    }
    header('Location: orders.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE o.order_status = '" . $conn->real_escape_string($filter) . "'" : "";

$orders = $conn->query("
    SELECT o.*, u.name as client_name, u.email as client_email,
           COUNT(oi.id) as item_count, SUM(oi.total_price) as items_total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $where
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
                <h4 class="fw-bold mb-0">🛒 Menaxhimi i Porosive <span class="badge bg-success"><?= count($orders) ?></span></h4>
            </div>

            <!-- Filters -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <a href="?status=all" class="btn btn-sm <?= $filter==='all'?'btn-secondary':'btn-outline-secondary' ?>">Të Gjitha</a>
                <a href="?status=pending" class="btn btn-sm <?= $filter==='pending'?'btn-warning':'btn-outline-warning' ?>">⏳ Pritëse</a>
                <a href="?status=confirmed" class="btn btn-sm <?= $filter==='confirmed'?'btn-info text-white':'btn-outline-info' ?>">✅ Konfirmuara</a>
                <a href="?status=processing" class="btn btn-sm <?= $filter==='processing'?'btn-primary':'btn-outline-primary' ?>">⚙️ Procesim</a>
                <a href="?status=shipped" class="btn btn-sm <?= $filter==='shipped'?'btn-dark':'btn-outline-dark' ?>">🚚 Dërguar</a>
                <a href="?status=delivered" class="btn btn-sm <?= $filter==='delivered'?'btn-success':'btn-outline-success' ?>">✔️ Dorëzuar</a>
                <a href="?status=cancelled" class="btn btn-sm <?= $filter==='cancelled'?'btn-danger':'btn-outline-danger' ?>">❌ Anuluar</a>
            </div>

            <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="fs-1">📭</div>
                <h5 class="text-muted mt-2">Nuk ka porosi</h5>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#Porosi</th><th>Klienti</th><th>Artikuj</th><th>Totali</th><th>Pagesa</th><th>Statusi</th><th>Data</th><th>Edito</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o):
                                $colors = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','shipped'=>'dark','delivered'=>'success','cancelled'=>'danger'];
                            ?>
                            <tr>
                                <td class="fw-semibold text-success small"><?= $o['order_number'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= $o['client_name'] ?></div>
                                    <div class="text-muted small"><?= $o['client_email'] ?></div>
                                </td>
                                <td><?= $o['item_count'] ?> artikuj</td>
                                <td class="fw-bold"><?= formatPrice($o['final_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $o['payment_status']==='paid'?'success':'warning text-dark' ?>">
                                        <?= $o['payment_status']==='paid'?'✅ Paguar':'⏳ Papaguar' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $colors[$o['order_status']]??'secondary' ?>">
                                        <?= ucfirst($o['order_status']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            data-id="<?= $o['id'] ?>"
                                            data-status="<?= $o['order_status'] ?>"
                                            data-payment="<?= $o['payment_status'] ?>">
                                        ✏️
                                    </button>
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

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">✏️ Ndrysho Porosinë</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modal-order-id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Statusi i Porosisë</label>
                        <select name="status" class="form-select" id="modal-status">
                            <option value="pending">⏳ Pritëse</option>
                            <option value="confirmed">✅ Konfirmuar</option>
                            <option value="processing">⚙️ Procesim</option>
                            <option value="shipped">🚚 Dërguar</option>
                            <option value="delivered">✔️ Dorëzuar</option>
                            <option value="cancelled">❌ Anuluar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Statusi i Pagesës</label>
                        <select name="payment_status" class="form-select" id="modal-payment">
                            <option value="pending">⏳ Papaguar</option>
                            <option value="paid">✅ Paguar</option>
                            <option value="failed">❌ Dështuar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Ruaj Ndryshimet</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(event) {
    const btn = event.relatedTarget;
    document.getElementById('modal-order-id').value = btn.dataset.id;
    document.getElementById('modal-status').value = btn.dataset.status;
    document.getElementById('modal-payment').value = btn.dataset.payment;
});
</script>
<h1>1</h1>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>