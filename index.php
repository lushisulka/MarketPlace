<?php
require_once __DIR__ . '/config/config.php';
$page_title = 'Fruta & Perime të Certifikuara';

// Merr kategorite
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL")->fetch_all(MYSQLI_ASSOC);

// Merr produktet e fundit
$products = $conn->query("
    SELECT p.*, c.name as cat_name, pt.business_name, pt.is_certified 
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    JOIN partners pt ON p.partner_id = pt.id
    WHERE p.status = 'active' AND pt.status = 'approved'
    ORDER BY p.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Merr partneret e certifikuar
$partners = $conn->query("
    SELECT pt.*, u.name, u.profile_image 
    FROM partners pt JOIN users u ON pt.user_id = u.id
    WHERE pt.status = 'approved' AND pt.is_certified = 1
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- HERO SECTION -->
<div class="hero-section bg-success text-white py-5">
    <div class="container text-center py-4">
        <h1 class="display-4 fw-bold mb-3">Fruta & Perime të Freskëta</h1>
        <p class="lead mb-4 opacity-75">Drejtpërdrejt nga fermerët e certifikuar tek tryeza juaj</p>
        <form class="d-flex justify-content-center" action="<?= SITE_URL ?>/pages/search.php" method="GET">
            <div class="input-group w-50 shadow-lg">
                <input class="form-control form-control-lg" type="search" name="q" placeholder="Kërko produkte...">
                <button class="btn btn-warning btn-lg fw-bold" type="submit">
                    <i class="fas fa-search me-2"></i>Kërko
                </button>
            </div>
        </form>
    </div>
</div>

<!-- KATEGORITE -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-4">Kategoritë</h2>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= SITE_URL ?>/pages/products.php?category=<?= $cat['slug'] ?>" 
                   class="card text-center text-decoration-none border-0 shadow-sm h-100 category-card">
                    <div class="card-body py-4">
                        <div class="fs-1 mb-2"><?= $cat['icon'] ?></div>
                        <h6 class="text-dark fw-semibold mb-0"><?= $cat['name'] ?></h6>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PRODUKTET E FUNDIT -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Produkte të Freskëta</h2>
            <a href="<?= SITE_URL ?>/pages/products.php" class="btn btn-outline-success">Shiko të gjitha</a>
        </div>
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm border-0 product-card">
                    <div class="position-relative">
                        <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>" 
                             class="card-img-top" alt="<?= $p['name'] ?>" style="height:200px; object-fit:cover;">
                        <?php if ($p['is_certified']): ?>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">
                            ✅ Certifikuar
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <small class="text-muted"><?= $p['cat_name'] ?></small>
                        <h6 class="fw-bold mt-1"><?= $p['name'] ?></h6>
                        <p class="text-muted small mb-2">🏪 <?= $p['business_name'] ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-5 fw-bold text-success"><?= formatPrice($p['price']) ?></span>
                            <span class="text-muted small">/ <?= $p['unit'] ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= $p['id'] ?>" 
                           class="btn btn-success w-100">Shiko Detaje</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PARTNERËT E CERTIFIKUAR -->
<?php if (!empty($partners)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-4">Partnerë të Certifikuar</h2>
        <div class="row g-4 justify-content-center">
            <?php foreach ($partners as $pt): ?>
            <div class="col-md-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-4">
                        <div class="fs-1 mb-3">🏪</div>
                        <h5 class="fw-bold"><?= $pt['business_name'] ?></h5>
                        <span class="badge bg-warning text-dark mb-2">✅ Partner i Certifikuar</span>
                        <div class="mb-2"><?= renderStars($pt['rating']) ?></div>
                        <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $pt['id'] ?>" 
                           class="btn btn-outline-success btn-sm">Shiko Profilin</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- PERSE NE -->
<section class="py-5 bg-success text-white">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Pse të na zgjidhni?</h2>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <h5 class="fw-bold">100% Organike</h5>
                <p class="opacity-75">Produkte nga fermerë të verifikuar me certifikata të vlefshme</p>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold">Dorëzim i Shpejtë</h5>
                <p class="opacity-75">Dërgesa në kohë direkt tek porta juaj</p>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold">Pagesa e Sigurt</h5>
                <p class="opacity-75">Online ose cash, sipas preferencës tuaj</p>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold">Vlerësime Reale</h5>
                <p class="opacity-75">Komente dhe vlerësime nga blerës të vërtetë</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>