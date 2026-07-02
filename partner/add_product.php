<?php
require_once __DIR__ . '/../config/config.php';
requireRole('partner');
$page_title = 'Menaxho Produktet';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();

if (!$partner) {
    setFlash('error', 'Llogaria juaj nuk është aprovuar ende nga admini.');
    header('Location: ' . SITE_URL . '/partner/dashboard.php');
    exit;
}

$pid = $partner['id'];
$errors = [];
$edit_product = null;
$edit_id = (int)($_GET['edit'] ?? 0);

// Nëse jemi duke edituar
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND partner_id = ?");
    $stmt->bind_param("ii", $edit_id, $pid);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    if (!$edit_product) { header('Location: products.php'); exit; }
}

// Handle DELETE
if ($_GET['delete'] ?? false) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $del_id AND partner_id = $pid");
    setFlash('success', 'Produkti u fshi me sukses.');
    header('Location: products.php');
    exit;
}

// Handle FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price       = (float)$_POST['price'];
    $b2b_price   = !empty($_POST['b2b_price']) ? (float)$_POST['b2b_price'] : null;
    $stock       = (float)$_POST['stock'];
    $unit        = sanitize($_POST['unit']);
    $category_id = (int)$_POST['category_id'];
    $is_organic  = isset($_POST['is_organic']) ? 1 : 0;
    $status      = sanitize($_POST['status']);

    if (empty($name)) $errors[] = "Emri i produktit është i detyrueshëm.";
    if ($price <= 0)  $errors[] = "Çmimi duhet të jetë pozitiv.";
    if ($stock < 0)   $errors[] = "Stoku nuk mund të jetë negativ.";

    $image_name = $edit_product['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $uploaded = uploadFile($_FILES['image'], 'products');
        if ($uploaded) {
            // Fshi imazhin e vjetër
            if ($image_name && file_exists(UPLOAD_PATH . 'products/' . $image_name)) {
                unlink(UPLOAD_PATH . 'products/' . $image_name);
            }
            $image_name = $uploaded;
        } else {
            $errors[] = "Formati i imazhit nuk pranohet. (jpg, png, webp)";
        }
    }

    if (empty($errors)) {
        if ($edit_id && $edit_product) {
            $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, b2b_price=?, stock=?, unit=?, category_id=?, is_organic=?, status=?, image=? WHERE id=? AND partner_id=?");
            $stmt->bind_param("ssdddsiissii", $name, $description, $price, $b2b_price, $stock, $unit, $category_id, $is_organic, $status, $image_name, $edit_id, $pid);
        } else {
            $slug = createSlug($name) . '-' . uniqid();
            $stmt = $conn->prepare("INSERT INTO products (partner_id, category_id, name, slug, description, price, b2b_price, stock, unit, is_organic, image, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iisssdddsiss", $pid, $category_id, $name, $slug, $description, $price, $b2b_price, $stock, $unit, $is_organic, $image_name, $status);
        }
        $stmt->execute();
        setFlash('success', $edit_id ? 'Produkti u përditësua!' : 'Produkti u shtua me sukses!');
        header('Location: products.php');
        exit;
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">
            <?= $edit_product ? '✏️ Edito Produktin' : '➕ Shto Produkt të Ri' ?>
        </h3>
        <a href="products.php" class="btn btn-outline-secondary">← Kthehu</a>
    </div>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= $e ?></div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Emri i Produktit *</label>
                                <input type="text" name="name" class="form-control form-control-lg"
                                       value="<?= $edit_product['name'] ?? ($_POST['name'] ?? '') ?>"
                                       placeholder="p.sh. Mollë Golden Delicious" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kategoria *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Zgjidh kategorinë...</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= ($edit_product['category_id'] ?? $_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= $cat['icon'] ?> <?= $cat['name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Njësia e matjes</label>
                                <select name="unit" class="form-select">
                                    <?php
                                    $units = ['kg','g','copë','litër','pako','tufë','kosh'];
                                    $cur_unit = $edit_product['unit'] ?? 'kg';
                                    foreach ($units as $u) echo "<option value='$u'" . ($cur_unit===$u?' selected':'') . ">$u</option>";
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Çmimi (ALL) *</label>
                                <div class="input-group">
                                    <input type="number" name="price" class="form-control" step="0.01" min="0"
                                           value="<?= $edit_product['price'] ?? ($_POST['price'] ?? '') ?>" required>
                                    <span class="input-group-text">ALL</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Çmimi B2B (ALL)</label>
                                <div class="input-group">
                                    <input type="number" name="b2b_price" class="form-control" step="0.01" min="0"
                                           value="<?= $edit_product['b2b_price'] ?? ($_POST['b2b_price'] ?? '') ?>"
                                           placeholder="Opsionale">
                                    <span class="input-group-text">ALL</span>
                                </div>
                                <small class="text-muted">Çmim i veçantë për hotele/restorante</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stoku</label>
                                <input type="number" name="stock" class="form-control" step="0.1" min="0"
                                       value="<?= $edit_product['stock'] ?? ($_POST['stock'] ?? '0') ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Përshkrimi</label>
                                <textarea name="description" class="form-control" rows="4"
                                          placeholder="Përshkruaj produktin tënd..."><?= $edit_product['description'] ?? ($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Imazhi i Produktit</label>
                                <input type="file" name="image" class="form-control" accept="image/*" id="img-input">
                                <?php if ($edit_product['image'] ?? false): ?>
                                <div class="mt-2">
                                    <img src="<?= SITE_URL ?>/uploads/products/<?= $edit_product['image'] ?>"
                                         style="height:80px;border-radius:8px;" class="img-thumbnail">
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Statusi</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($edit_product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>✅ Aktiv</option>
                                    <option value="inactive" <?= ($edit_product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>⏸️ Joaktiv</option>
                                </select>

                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_organic" id="organic"
                                           <?= ($edit_product['is_organic'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="organic">
                                        🌱 Produkt Organik
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Preview imazhi -->
                        <div id="img-preview" class="mt-3 d-none">
                            <img id="preview-img" src="" style="height:120px;border-radius:10px;" class="img-thumbnail">
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-success px-5 fw-bold">
                                <i class="fas fa-save me-2"></i><?= $edit_product ? 'Ruaj Ndryshimet' : 'Shto Produktin' ?>
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary px-4">Anulo</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar tips -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">💡 Këshilla</h6>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2">✅ Shto imazh të qartë dhe tërheqës</li>
                        <li class="mb-2">✅ Shkruaj përshkrim të detajuar</li>
                        <li class="mb-2">✅ Vendos çmim konkurrues</li>
                        <li class="mb-2">✅ Mbaj stokun të përditësuar</li>
                        <li class="mb-2">✅ Vendos çmim B2B për shumicë</li>
                    </ul>
                </div>
            </div>
            <div class="card border-0 shadow-sm border-start border-4 border-success">
                <div class="card-body">
                    <h6 class="fw-bold text-success">📊 Statusi i Llogarisë</h6>
                    <p class="mb-1 small">Partner: <strong><?= $partner['business_name'] ?></strong></p>
                    <p class="mb-0 small">
                        Certifikimi: 
                        <?php if ($partner['is_certified']): ?>
                            <span class="text-success fw-bold">✅ I Certifikuar</span>
                        <?php else: ?>
                            <span class="text-muted">⏳ Në pritje</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('img-input')?.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('img-preview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>