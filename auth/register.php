<?php
require_once __DIR__ . '/../config/config.php';
$page_title = 'Regjistrohu';
$type = $_GET['type'] ?? 'client';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name']);
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = in_array($_POST['role'], ['client', 'partner', 'b2b']) ? $_POST['role'] : 'client';

    if (empty($name)) $errors[] = "Emri është i detyrueshëm.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email-i nuk është valid.";
    if (strlen($password) < 6) $errors[] = "Fjalëkalimi duhet të ketë minimumi 6 karaktere.";
    if ($password !== $confirm) $errors[] = "Fjalëkalimet nuk përputhen.";

    // Check email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Ky email ekziston tashmë.";
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $status = ($role === 'client') ? 'active' : 'active';
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed, $role, $status);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Nëse partner, krijo rekord në partners
            if ($role === 'partner') {
                $biz_name = sanitize($_POST['business_name'] ?? $name);
                $stmt2 = $conn->prepare("INSERT INTO partners (user_id, business_name, status) VALUES (?, ?, 'pending')");
                $stmt2->bind_param("is", $user_id, $biz_name);
                $stmt2->execute();
            }
            
            // Nëse b2b
            if ($role === 'b2b') {
                $biz_name = sanitize($_POST['business_name'] ?? $name);
                $biz_type = sanitize($_POST['business_type'] ?? 'other');
                $stmt2 = $conn->prepare("INSERT INTO b2b_clients (user_id, business_name, business_type, status) VALUES (?, ?, ?, 'pending')");
                $stmt2->bind_param("iss", $user_id, $biz_name, $biz_type);
                $stmt2->execute();
            }
            
            setFlash('success', 'Regjistrimi u krye! Hyr tani.');
            header('Location: ' . SITE_URL . '/auth/login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-success text-white text-center py-4 rounded-top-4">
                    <h3 class="mb-0">🌿 Regjistrohu</h3>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Role Selector -->
                    <div class="btn-group w-100 mb-4" role="group">
                        <a href="?type=client" class="btn <?= $type === 'client' ? 'btn-success' : 'btn-outline-success' ?>">👤 Klient</a>
                        <a href="?type=partner" class="btn <?= $type === 'partner' ? 'btn-success' : 'btn-outline-success' ?>">🏪 Partner</a>
                        <a href="?type=b2b" class="btn <?= $type === 'b2b' ? 'btn-success' : 'btn-outline-success' ?>">🏨 B2B</a>
                    </div>

                    <?php foreach ($errors as $e): ?>
                        <div class="alert alert-danger py-2"><?= $e ?></div>
                    <?php endforeach; ?>

                    <form method="POST">
                        <input type="hidden" name="role" value="<?= $type ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Emri i plotë</label>
                            <input type="text" name="name" class="form-control" value="<?= $_POST['name'] ?? '' ?>" required>
                        </div>
                        
                        <?php if (in_array($type, ['partner', 'b2b'])): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Emri i Biznesit</label>
                            <input type="text" name="business_name" class="form-control" required>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($type === 'b2b'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lloji i Biznesit</label>
                            <select name="business_type" class="form-select">
                                <option value="hotel">Hotel</option>
                                <option value="restaurant">Restorant</option>
                                <option value="other">Tjetër</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $_POST['email'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fjalëkalimi</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmo Fjalëkalimin</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                            Regjistrohu si <?= ucfirst($type) ?>
                        </button>
                    </form>
                    
                    <p class="text-center mt-3 mb-0 text-muted">
                        Ke llogari? <a href="<?= SITE_URL ?>/auth/login.php" class="text-success fw-bold">Hyr këtu</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<h1>1</h1>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>