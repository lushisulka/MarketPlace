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

// Merr partneret e certifikuar — pa limit
$partners = $conn->query("
    SELECT pt.*, u.name, u.profile_image 
    FROM partners pt JOIN users u ON pt.user_id = u.id
    WHERE pt.status = 'approved' AND pt.is_certified = 1
    ORDER BY pt.rating DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ═══════════════════════════════════════════════
     HERO SECTION — Foto background, tekst sipër
════════════════════════════════════════════════ -->
<div class="hero-section position-relative overflow-hidden" style="height:520px;">

    <!-- Foto background (Unsplash - fruta/perime) -->
        <img src="<?= SITE_URL ?>/assets/wallpaper.jpg"
         alt="Fruta dhe Perime të Freskëta"
         style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0;">

    <!-- Overlay i errët për kontrast -->
    <div style="position:absolute;top:0;left:0;width:100%;height:100%;
                background:linear-gradient(135deg,rgba(0,0,0,0.55),rgba(21,87,36,0.6));
                z-index:1;"></div>

    <!-- Teksti dhe Search Bar -->
    <div class="container text-center text-white position-relative h-100
                d-flex flex-column justify-content-center align-items-center"
         style="z-index:2;">

        <span class="badge bg-warning text-dark mb-3 px-3 py-2 fs-6 shadow">
            ✅ Platformë e Certifikuar
        </span>

        <h1 class="display-3 fw-bold mb-3"
            style="text-shadow:0 3px 12px rgba(0,0,0,0.7); letter-spacing:-0.5px;">
             Produkte të Freskëta
        </h1>

        <p class="lead fs-4 mb-4"
           style="text-shadow:0 1px 6px rgba(0,0,0,0.6); opacity:0.95; max-width:600px;">
            Drejtpërdrejt nga fermerët e certifikuar tek tryeza juaj
        </p>

        <!-- Search bar -->
        <form class="d-flex justify-content-center w-100 mb-4"
              action="<?= SITE_URL ?>/pages/search.php" method="GET">
            <div class="input-group shadow-lg" style="max-width:580px;">
                <input class="form-control form-control-lg border-0"
                       type="search" name="q"
                       placeholder="Kërko fruta, perime, partnerë...">
                <button class="btn btn-warning btn-lg fw-bold px-4" type="submit">
                    <i class="fas fa-search me-2"></i>Kërko
                </button>
            </div>
        </form>

        <!-- Butonët CTA -->
        <div class="d-flex gap-3 flex-wrap justify-content-center">
            <a href="<?= SITE_URL ?>/pages/products.php"
               class="btn btn-success btn-lg px-4 fw-bold shadow-lg">
                Bli Tani
            </a>
            <a href="<?= SITE_URL ?>/auth/register.php?type=partner"
               class="btn btn-outline-light btn-lg px-4 shadow-lg">
                Bëhu Partner
            </a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     KATEGORITE
════════════════════════════════════════════════ -->
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

<!-- ═══════════════════════════════════════════════
     PRODUKTET E FUNDIT
════════════════════════════════════════════════ -->
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
                             class="card-img-top" alt="<?= $p['name'] ?>"
                             style="height:200px; object-fit:cover;">
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

<!-- ═══════════════════════════════════════════════
     PARTNERËT E CERTIFIKUAR
════════════════════════════════════════════════ -->
<?php if (!empty($partners)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-4"> Partnerë të Certifikuar</h2>
        <div class="row g-4 justify-content-center">
            <?php foreach ($partners as $pt): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-4">
                        <!-- Avatar me inicialen -->
                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center
                                    justify-content-center mx-auto mb-3"
                             style="width:65px;height:65px;font-size:1.8rem;">
                            <?= strtoupper(substr($pt['business_name'], 0, 1)) ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= $pt['business_name'] ?></h5>
                        <span class="badge bg-warning text-dark mb-2">✅ Partner i Certifikuar</span>
                        <div class="mb-3"><?= renderStars($pt['rating']) ?></div>
                        <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $pt['id'] ?>"
                           class="btn btn-outline-success btn-sm px-3">Shiko Profilin</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════
     PERSE NA ZGJIDHNI
════════════════════════════════════════════════ -->
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