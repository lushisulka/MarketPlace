<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Ankesat';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid    = (int)$_POST['complaint_id'];
    $status = sanitize($_POST['status']);
    $resp   = sanitize($_POST['admin_response']);
    $conn->prepare("UPDATE complaints SET status=?, admin_response=? WHERE id=?")->bind_param("ssi",$status,$resp,$cid) && $conn->prepare("UPDATE complaints SET status=?, admin_response=? WHERE id=?")->bind_param("ssi",$status,$resp,$cid);
    // Simpler:
    $stmt = $conn->prepare("UPDATE complaints SET status=?, admin_response=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $resp, $cid);
    $stmt->execute();
    setFlash('success', 'Ankesa u përditësua.');
    header('Location: complaints.php');
    exit;
}

$complaints = $conn->query("
    SELECT c.*, u.name as client_name, u.email as client_email,
           o.order_number
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN orders o ON c.order_id = o.id
    ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-2"><?php include __DIR__ . '/sidebar.php'; ?></div>
        <div class="col-md-10">
            <h4 class="fw-bold mb-4">📣 Ankesat <span class="badge bg-danger"><?= count($complaints) ?></span></h4>

            <?php if (empty($complaints)): ?>
            <div class="card border-0 shadow-sm text-center py-5"><div class="fs-1">😊</div><h5 class="text-muted">Nuk ka ankesa</h5></div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($complaints as $c):
                    $colors = ['open'=>'danger','in_progress'=>'warning','resolved'=>'success','closed'=>'secondary'];
                    $labels = ['open'=>'🔴 E hapur','in_progress'=>'🟡 Në Procesim','resolved'=>'🟢 Zgjidhur','closed'=>'⚫ Mbyllur'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <div>
                            <strong><?= $c['client_name'] ?></strong>
                            <span class="text-muted small ms-2"><?= $c['client_email'] ?></span>
                            <?php if ($c['order_number']): ?>
                            <span class="badge bg-light text-dark ms-2">#<?= $c['order_number'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-<?= $colors[$c['status']] ?? 'secondary' ?> px-3 py-2"><?= $labels[$c['status']] ?? $c['status'] ?></span>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold"><?= $c['subject'] ?></h6>
                        <p class="text-muted mb-3"><?= nl2br($c['message']) ?></p>

                        <?php if ($c['admin_response']): ?>
                        <div class="alert alert-success">
                            <strong>📩 Përgjigja e Adminit:</strong><br><?= nl2br($c['admin_response']) ?>
                        </div>
                        <?php endif; ?>

                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#reply-<?= $c['id'] ?>">
                            ✏️ Përgjigju
                        </button>

                        <div class="collapse mt-3" id="reply-<?= $c['id'] ?>">
                            <form method="POST" class="p-3 bg-light rounded-3">
                                <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Statusi</label>
                                    <select name="status" class="form-select">
                                        <option value="open" <?= $c['status']==='open'?'selected':'' ?>>🔴 E hapur</option>
                                        <option value="in_progress" <?= $c['status']==='in_progress'?'selected':'' ?>>🟡 Në Procesim</option>
                                        <option value="resolved" <?= $c['status']==='resolved'?'selected':'' ?>>🟢 Zgjidhur</option>
                                        <option value="closed" <?= $c['status']==='closed'?'selected':'' ?>>⚫ Mbyllur</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Përgjigja</label>
                                    <textarea name="admin_response" class="form-control" rows="3" placeholder="Shkruaj përgjigjen..."><?= $c['admin_response'] ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">Dërgo Përgjigjen</button>
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


<?php require_once __DIR__ . '/../includes/footer.php'; ?>