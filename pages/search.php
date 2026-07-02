<?php
require_once __DIR__ . '/../config/config.php';
$q = sanitize($_GET['q'] ?? '');
$page_title = $q ? "Kërko: $q" : 'Kërkim';

$products = [];
$partners = [];

if (!empty($q)) {
    $search = "%$q%";

    $stmt = $conn->prepare("
        SELECT p.*, c.name as cat_name, pt.business_name, pt.is_certified, pt.id as partner_id
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN partners pt ON p.partner_id = pt.id
        WHERE p.status = 'active' AND pt.status = 'approved'
          AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR pt.business_name LIKE ?)
        ORDER BY p.rating DESC LIMIT 20
    ");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("
        SELECT pt.*, u.name
        FROM partners pt JOIN users u ON pt.user_id = u.id
        WHERE pt.status = 'approved'
          AND (pt.business_name LIKE ? OR pt.business_description LIKE ?)
        LIMIT 6
    ");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $partners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <?php if (empty($q)): ?>
    <div class="text-center py-5">
        <div class="fs-1 mb-3">🔍</div>
        <h4 class="text-muted">Shkruaj çfarë po kërkon...</h4>
        <form class="d-flex justify-content-center mt-4" action="search.php" method="GET">
            <div class="input-group w-50">
                <input class="form-control form-control-lg" type="search" name="q" placeholder="Kërko produkte, partnerë...">
                <button class="btn btn-success btn-lg" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
    <?php else: ?>

    <div class="mb-4">
        <h4 class="fw-bold">🔍 Rezultate për: <em class="text-success">"<?= $q ?>"</em></h4>
        <p class="text-muted"><?= count($products) ?> produkte dhe <?= count($partners) ?> partnerë u gjetën.</p>
    </div>

    <?php if (!empty($partners)): ?>
    <h5 class="fw-bold mb-3">🏪 Partnerët</h5>
    <div class="row g-3 mb-5">
        <?php foreach ($partners as $pt): ?>
        <div class="col-md-4 col-lg-2">
            <a href="partner_profile.php?id=<?= $pt['id'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center p-3 h-100 hover-lift">
                    <div class="fs-2 mb-2">🏪</div>
                    <div class="fw-bold small text-dark"><?= $pt['business_name'] ?></div>
                    <?php if ($pt['is_certified']): ?>
                    <span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem;">✅ Certifikuar</span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($products)): ?>
    <h5 class="fw-bold mb-3">📦 Produktet</h5>
    <div class="row g-4">
        <?php foreach ($products as $p): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm border-0 product-card">
                <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                     class="card-img-top" style="height:180px;object-fit:cover;" alt="<?= $p['name'] ?>">
                <div class="card-body p-3">
                    <p class="text-muted small mb-1"><?= $p['cat_name'] ?></p>
                    <h6 class="fw-bold"><?= $p['name'] ?></h6>
                    <p class="text-muted small mb-2">
                        <a href="partner_profile.php?id=<?= $p['partner_id'] ?>" class="text-muted text-decoration-none">
                            🏪 <?= $p['business_name'] ?>
                        </a>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success"><?= formatPrice($p['price']) ?></span>
                        <small class="text-muted">/<?= $p['unit'] ?></small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 p-3 pt-0">
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm w-100">Detaje</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($products) && empty($partners)): ?>
    <div class="text-center py-5">
        <div class="fs-1">😔</div>
        <h5 class="text-muted mt-3">Nuk u gjet asgjë për "<?= $q ?>"</h5>
        <a href="products.php" class="btn btn-success mt-3">Shiko të gjitha produktet ketu</a>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>