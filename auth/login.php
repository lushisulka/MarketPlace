<?php
require_once __DIR__ . '/../config/config.php';
$page_title = 'Hyr';

if (isLoggedIn()) { header('Location: ' . SITE_URL); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status != 'banned'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        
        setFlash('success', 'Mirësevini, ' . $user['name'] . '!');
        
        $redirect = match($user['role']) {
            'partner' => '/partner/dashboard.php',
            'b2b'     => '/b2b/dashboard.php',
            'admin'   => '/admin/dashboard.php',
            default   => '/client/dashboard.php'
        };
        header('Location: ' . SITE_URL . $redirect);
        exit;
    } else {
        $error = 'Email ose fjalëkalim i gabuar.';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-success text-white text-center py-4 rounded-top-4">
                    <h3 class="mb-0">🔑 Hyr në llogari</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fjalëkalimi</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold fs-5">Hyr</button>
                    </form>
                    
                    <p class="text-center mt-3 mb-0 text-muted">
                        Nuk ke llogari? <a href="<?= SITE_URL ?>/auth/register.php" class="text-success fw-bold">Regjistrohu</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<h1>1</h1>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>