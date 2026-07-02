<?php
require_once __DIR__ . '/../config/config.php';
$page_title = 'Produktet';

$where = ["p.status = 'active'", "pt.status = 'approved'"];
$params = [];
$types  = "";

$category = sanitize($_GET['category'] ?? '');
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 9999);
$certified = $_GET['certified'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
    $types .= "s";
}
if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price < 9999) {
    $where[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}
if ($certified) {
    $where[] = "pt.is_certified = 1";
}

$order = match($sort) {
    'price_asc'  => "p.price ASC",
    'price_desc' => "p.price DESC",
    'rating'     => "p.rating DESC",
    default      => "p.created_at DESC"
};

$sql = "SELECT p.*, c.name as cat_name, pt.business_name, pt.is_certified, pt.id as partner_id
        FROM products p 
        JOIN categories c ON p.category_id = c.id
        JOIN partners pt ON p.partner_id = pt.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY $order";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- SIDEBAR FILTRA -->
        <div class="col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">🔍 Filtra</h5>
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kategoria</label>
                            <?php foreach ($categories as $cat): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" 
                                       value="<?= $cat['slug'] ?>" id="cat_<?= $cat['id'] ?>"
                                       <?= $category === $cat['slug'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cat_<?= $cat['id'] ?>">
                                    <?= $cat['icon'] ?> <?= $cat['name'] ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Çmimi (ALL)</label>
                            <div class="row g-2">
                                <div class="col"><input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= $min_price ?: '' ?>"></div>
                                <div class="col"><input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= $max_price < 9999 ? $max_price : '' ?>"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="certified" value="1" id="cert" <?= $certified ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cert">✅ Vetëm të certifikuar</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rendit sipas</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Më të rejat</option>
                                <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Çmimi ↑</option>
                                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Çmimi ↓</option>
                                <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Vlerësimi</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">Apliko</button>
                        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Pastro</a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- LISTA E PRODUKTEVE -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Produktet <span class="badge bg-success"><?= count($products) ?></span></h4>
            </div>
            
            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <div class="fs-1">🔍</div>
                <h5 class="text-muted">Nuk u gjet asnjë produkt</h5>
                <a href="products.php" class="btn btn-success mt-3">Shiko të gjitha</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $p): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 product-card">
                        <div class="position-relative">
                            <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>" 
                                 class="card-img-top" alt="<?= $p['name'] ?>" style="height:180px; object-fit:cover;">
                            <?php if ($p['is_certified']): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark small">✅ Cert.</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3">
                            <p class="text-muted small mb-1"><?= $p['cat_name'] ?></p>
                            <h6 class="fw-bold"><?= $p['name'] ?></h6>
                            <p class="text-muted small mb-2">
                                <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $p['partner_id'] ?>" class="text-decoration-none text-muted">
                                    🏪 <?= $p['business_name'] ?>
                                </a>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success"><?= formatPrice($p['price']) ?></span>
                                <small class="text-muted">/<?= $p['unit'] ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent p-3 pt-0">
                            <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm w-100">Detaje</a>
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