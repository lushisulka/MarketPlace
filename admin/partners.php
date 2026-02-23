<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Menaxhimi i Partnerëve';

// Veprime
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)$_POST['partner_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $cert_date = date('Y-m-d');
        $conn->query("UPDATE partners SET status='approved', is_certified=1, certification_date='$cert_date' WHERE id=$pid");
        setFlash('success', '✅ Partneri u aprovua dhe u certifikua!');
    } elseif ($action === 'reject') {
        $conn->query("UPDATE partners SET status='rejected', is_certified=0 WHERE id=$pid");
        setFlash('error', '❌ Partneri u refuzua.');
    } elseif ($action === 'revoke') {
        $conn->query("UPDATE partners SET is_certified=0, certification_date=NULL WHERE id=$pid");
        setFlash('info', 'Certifikimi u hoq.');
    } elseif ($action === 'certify') {
        $cert_date = date('Y-m-d');
        $conn->query("UPDATE partners SET is_certified=1, certification_date='$cert_date' WHERE id=$pid");
        setFlash('success', '✅ Certifikimi u dha!');
    }

    header('Location: partners.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$where = $filter !== 'all' ? "WHERE pt.status = '" . $conn->real_escape_string($filter) . "'" : "";

$partners = $conn->query("
    SELECT pt.*, u.name, u.email, u.phone, u.created_at as user_created,
           COUNT(p.id) as product_count
    FROM partners pt
    JOIN users u ON pt.user_id = u.id
    LEFT JOIN products p ON pt.id = p.partner_id
    $where
    GROUP BY pt.id
    ORDER BY pt.created_at DESC
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
                <h4 class="fw-bold mb-0">🏪 Menaxhimi i Partnerëve</h4>
            </div>

            <!-- Filter pills -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <a href="?status=all" class="btn btn-sm <?= $filter==='all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Të Gjithë</a>
                <a href="?status=pending" class="btn btn-sm <?= $filter==='pending' ? 'btn-warning' : 'btn-outline-warning' ?>">⏳ Pritëse</a>
                <a href="?status=approved" class="btn btn-sm <?= $filter==='approved' ? 'btn-success' : 'btn-outline-success' ?>">✅ Aprovuar</a>
                <a href="?status=rejected" class="btn btn-sm <?= $filter==='rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">❌ Refuzuar</a>
            </div>

            <?php if (empty($partners)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="fs-1">🏪</div>
                    <h5 class="text-muted mt-2">Nuk ka partnerë</h5>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($partners as $p): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-1 text-center">
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mx-auto text-white fw-bold fs-4"
                                     style="width:55px;height:55px;">
                                    <?= strtoupper(substr($p['business_name'], 0, 1)) ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-1"><?= $p['business_name'] ?></h5>
                                <div class="text-muted small">👤 <?= $p['name'] ?></div>
                                <div class="text-muted small">✉️ <?= $p['email'] ?></div>
                                <?php if ($p['phone']): ?>
                                <div class="text-muted small">📞 <?= $p['phone'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-<?= $p['status'] === 'approved' ? 'success' : ($p['status'] === 'pending' ? 'warning text-dark' : 'danger') ?> px-3 py-2" style="width:fit-content;">
                                        <?= $p['status'] === 'approved' ? '✅ Aprovuar' : ($p['status'] === 'pending' ? '⏳ Pritëse' : '❌ Refuzuar') ?>
                                    </span>
                                    <?php if ($p['is_certified']): ?>
                                    <span class="badge px-3 py-2" style="background:linear-gradient(135deg,#ffc107,#ff8f00);color:#000;width:fit-content;">
                                        🏅 Certifikuar — <?= $p['certification_date'] ? date('d/m/Y', strtotime($p['certification_date'])) : '' ?>
                                    </span>
                                    <?php endif; ?>
                                    <small class="text-muted">📦 <?= $p['product_count'] ?> produkte</small>
                                    <?php if ($p['license_number']): ?>
                                    <small class="text-muted">📋 Licenca: <?= $p['license_number'] ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="fw-bold fs-5">⭐ <?= number_format($p['rating'],1) ?>/5</div>
                                    <div class="small text-muted"><?= $p['total_reviews'] ?> vlerësime</div>
                                </div>
                                <?php if ($p['license_document']): ?>
                                <div class="text-center mt-2">
                                    <a href="<?= SITE_URL ?>/uploads/documents/<?= $p['license_document'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        📄 Licenca
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex flex-column gap-2">
                                    <?php if ($p['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm w-100">✅ Aprovo & Certifo</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-danger btn-sm w-100">❌ Refuzo</button>
                                    </form>
                                    <?php elseif ($p['status'] === 'approved'): ?>
                                        <?php if (!$p['is_certified']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action" value="certify">
                                            <button class="btn btn-warning btn-sm w-100">🏅 Çertifiko</button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <button class="btn btn-outline-warning btn-sm w-100" onclick="return confirm('Hiq certifikimin?')">🚫 Hiq Cert.</button>
                                        </form>
                                        <?php endif; ?>
                                    <?php elseif ($p['status'] === 'rejected'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm w-100">↩️ Ri-aprovo</button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm">👁 Profili</a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>