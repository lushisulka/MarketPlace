<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Menaxhimi i Klientëve B2B';

// Veprime
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid    = (int)$_POST['b2b_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $conn->query("UPDATE b2b_clients SET status = 'approved' WHERE id = $bid");
        // Aktivizo userin nëse është pending
        $conn->query("UPDATE users u JOIN b2b_clients b ON u.id = b.user_id SET u.status = 'active' WHERE b.id = $bid");
        setFlash('success', '✅ Klienti B2B u aprovua! Tani mund të blejë me çmime shumice.');
    } elseif ($action === 'reject') {
        $conn->query("UPDATE b2b_clients SET status = 'rejected' WHERE id = $bid");
        setFlash('error', '❌ Klienti B2B u refuzua.');
    } elseif ($action === 'set_discount') {
        $discount = (float)$_POST['discount'];
        $credit   = (float)$_POST['credit_limit'];
        $conn->query("UPDATE b2b_clients SET discount_percentage = $discount, credit_limit = $credit WHERE id = $bid");
        setFlash('success', '💰 Zbritja dhe limiti i kreditit u vendosën.');
    }

    header('Location: b2b.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE b.status = '" . $conn->real_escape_string($filter) . "'" : "";

$b2b_clients = $conn->query("
    SELECT b.*, u.name, u.email, u.phone, u.status as user_status, u.created_at as registered_at
    FROM b2b_clients b
    JOIN users u ON b.user_id = u.id
    $where
    ORDER BY b.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Numëro pending
$pending_count = $conn->query("SELECT COUNT(*) FROM b2b_clients WHERE status = 'pending'")->fetch_row()[0];

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
                <h4 class="fw-bold mb-0">
                    🏨 Klientët B2B
                    <?php if ($pending_count > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $pending_count ?> në pritje</span>
                    <?php endif; ?>
                </h4>
            </div>

            <!-- Filter tabs -->
            <div class="d-flex gap-2 mb-4">
                <a href="?status=all"      class="btn btn-sm <?= $filter==='all'      ? 'btn-secondary'              : 'btn-outline-secondary' ?>">Të Gjithë (<?= count($b2b_clients) ?>)</a>
                <a href="?status=pending"  class="btn btn-sm <?= $filter==='pending'  ? 'btn-warning'                : 'btn-outline-warning' ?>">⏳ Pritëse</a>
                <a href="?status=approved" class="btn btn-sm <?= $filter==='approved' ? 'btn-success'                : 'btn-outline-success' ?>">✅ Aprovuar</a>
                <a href="?status=rejected" class="btn btn-sm <?= $filter==='rejected' ? 'btn-danger'                 : 'btn-outline-danger' ?>">❌ Refuzuar</a>
            </div>

            <?php if (empty($b2b_clients)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="fs-1">🏨</div>
                <h5 class="text-muted mt-2">Nuk ka klientë B2B</h5>
            </div>

            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($b2b_clients as $b):
                    $status_cfg = [
                        'pending'  => ['color'=>'warning', 'label'=>'⏳ Në Pritje',  'text'=>'dark'],
                        'approved' => ['color'=>'success', 'label'=>'✅ Aprovuar',   'text'=>'white'],
                        'rejected' => ['color'=>'danger',  'label'=>'❌ Refuzuar',   'text'=>'white'],
                    ];
                    $sc = $status_cfg[$b['status']] ?? ['color'=>'secondary','label'=>$b['status'],'text'=>'white'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info text-white fw-bold d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;font-size:1.3rem;">
                                <?= ['hotel'=>'🏨','restaurant'=>'🍽️','other'=>'🏢'][$b['business_type']] ?? '🏢' ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0"><?= $b['business_name'] ?></h5>
                                <span class="badge bg-info text-dark"><?= ucfirst($b['business_type']) ?></span>
                                <?php if ($b['nipt']): ?>
                                <span class="text-muted small ms-2">NIPT: <?= $b['nipt'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge bg-<?= $sc['color'] ?> text-<?= $sc['text'] ?> px-3 py-2 fs-6">
                            <?= $sc['label'] ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Info kontakti -->
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="small text-muted mb-2 fw-semibold">👤 Kontakti</div>
                                    <div class="fw-semibold"><?= $b['name'] ?></div>
                                    <div class="small text-muted">✉️ <?= $b['email'] ?></div>
                                    <?php if ($b['phone']): ?>
                                    <div class="small text-muted">📞 <?= $b['phone'] ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-2">
                                        📅 Regjistruar: <?= date('d/m/Y', strtotime($b['registered_at'])) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Statusi aktual -->
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="small text-muted mb-2 fw-semibold">💰 Kushtet Aktuale</div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small">Zbritja B2B:</span>
                                        <span class="fw-bold text-success"><?= $b['discount_percentage'] ?>%</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small">Limiti i Kreditit:</span>
                                        <span class="fw-bold"><?= formatPrice($b['credit_limit']) ?></span>
                                    </div>
                                    <div class="mt-2">
                                        <span class="small text-muted">Statusi llogarie:</span>
                                        <span class="badge bg-<?= $b['user_status'] === 'active' ? 'success' : 'warning text-dark' ?> ms-1">
                                            <?= $b['user_status'] === 'active' ? 'Aktive' : ucfirst($b['user_status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Veprimet -->
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="small text-muted mb-2 fw-semibold">⚙️ Veprime</div>

                                    <?php if ($b['status'] === 'pending'): ?>
                                    <!-- Aprovo / Refuzo -->
                                    <form method="POST" class="d-grid gap-2">
                                        <input type="hidden" name="b2b_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm fw-bold">✅ Aprovo B2B</button>
                                    </form>
                                    <form method="POST" class="d-grid mt-2">
                                        <input type="hidden" name="b2b_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('Refuzo këtë B2B?')">❌ Refuzo</button>
                                    </form>

                                    <?php elseif ($b['status'] === 'approved'): ?>
                                    <!-- Set discount & credit -->
                                    <form method="POST">
                                        <input type="hidden" name="b2b_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="set_discount">
                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Zbritja (%)</label>
                                            <input type="number" name="discount" class="form-control form-control-sm"
                                                   value="<?= $b['discount_percentage'] ?>" min="0" max="50" step="0.5">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Limit Krediti (ALL)</label>
                                            <input type="number" name="credit_limit" class="form-control form-control-sm"
                                                   value="<?= $b['credit_limit'] ?>" min="0" step="100">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">💾 Ruaj Kushtet</button>
                                    </form>

                                    <?php elseif ($b['status'] === 'rejected'): ?>
                                    <form method="POST" class="d-grid">
                                        <input type="hidden" name="b2b_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm">↩️ Ri-aprovo</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
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

<h1>1</h1>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>