<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Menaxhimi i Përdoruesve';

// Veprime
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'ban') {
        $conn->query("UPDATE users SET status = 'banned' WHERE id = $uid AND role != 'admin'");
        setFlash('info', 'Përdoruesi u bllokua.');
    } elseif ($action === 'unban') {
        $conn->query("UPDATE users SET status = 'active' WHERE id = $uid");
        setFlash('success', 'Bllokimi u hoq.');
    }
    header('Location: users.php');
    exit;
}

$role_filter = $_GET['role'] ?? 'all';
$where = $role_filter !== 'all' ? "WHERE role = '" . $conn->real_escape_string($role_filter) . "'" : "WHERE role != 'admin'";

$users = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

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
                <h4 class="fw-bold mb-0">👥 Menaxhimi i Përdoruesve <span class="badge bg-success"><?= count($users) ?></span></h4>
            </div>

            <!-- Filter -->
            <div class="d-flex gap-2 mb-4">
                <a href="?role=all" class="btn btn-sm <?= $role_filter==='all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Të Gjithë</a>
                <a href="?role=client" class="btn btn-sm <?= $role_filter==='client' ? 'btn-primary' : 'btn-outline-primary' ?>">👤 Klientë</a>
                <a href="?role=partner" class="btn btn-sm <?= $role_filter==='partner' ? 'btn-success' : 'btn-outline-success' ?>">🏪 Partnerë</a>
                <a href="?role=b2b" class="btn btn-sm <?= $role_filter==='b2b' ? 'btn-info text-white' : 'btn-outline-info' ?>">🏨 B2B</a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Emri</th><th>Email</th><th>Roli</th><th>Statusi</th><th>Regjistruar</th><th>Veprime</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u):
                                $role_colors = ['client'=>'primary','partner'=>'success','b2b'=>'info'];
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-<?= $role_colors[$u['role']] ?? 'secondary' ?> text-white fw-bold d-flex align-items-center justify-content-center"
                                             style="width:38px;height:38px;font-size:0.9rem;">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= $u['name'] ?></div>
                                            <?php if ($u['phone']): ?><div class="text-muted small"><?= $u['phone'] ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small"><?= $u['email'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $role_colors[$u['role']] ?? 'secondary' ?>">
                                        <?= ['client'=>'👤 Klient','partner'=>'🏪 Partner','b2b'=>'🏨 B2B'][$u['role']] ?? $u['role'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : ($u['status'] === 'banned' ? 'danger' : 'warning text-dark') ?>">
                                        <?= ['active'=>'✅ Aktiv','banned'=>'🚫 Bllokuar','pending'=>'⏳ Pritëse'][$u['status']] ?? $u['status'] ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($u['status'] === 'banned'): ?>
                                        <input type="hidden" name="action" value="unban">
                                        <button class="btn btn-sm btn-outline-success">↩️ Zhblloko</button>
                                        <?php else: ?>
                                        <input type="hidden" name="action" value="ban">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Blloko këtë përdorues?')">🚫 Blloko</button>
                                        <?php endif; ?>
                                    </form>
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


<?php require_once __DIR__ . '/../includes/footer.php'; ?>