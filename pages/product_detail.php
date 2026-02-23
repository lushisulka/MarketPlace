<?php
require_once __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/products.php'); exit; }

// Merr produktin
$stmt = $conn->prepare("
    SELECT p.*, c.name as cat_name, c.slug as cat_slug,
           pt.id as partner_id, pt.business_name, pt.is_certified,
           pt.certification_date, pt.inspection_date, pt.rating as partner_rating,
           pt.total_reviews as partner_reviews, pt.business_description,
           u.name as owner_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN partners pt ON p.partner_id = pt.id
    JOIN users u ON pt.user_id = u.id
    WHERE p.id = ? AND p.status = 'active' AND pt.status = 'approved'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { header('Location: ' . SITE_URL . '/pages/products.php'); exit; }

// Merr reviews
$stmt = $conn->prepare("
    SELECT r.*, u.name as reviewer_name
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC LIMIT 10
");
$stmt->bind_param("i", $id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Produkte të ngjashme
$cat_id = $product['category_id'];
$similar = $conn->query("
    SELECT p.*, pt.business_name, pt.is_certified
    FROM products p JOIN partners pt ON p.partner_id = pt.id
    WHERE p.category_id = $cat_id AND p.id != $id AND p.status = 'active' AND pt.status = 'approved'
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// ── Roli i userit aktual ──────────────────────────────────────────
$user_role   = $_SESSION['user_role'] ?? '';
$can_buy     = isLoggedIn() && in_array($user_role, ['client', 'b2b']); // ✅ RREGULLIM 1
$can_review  = isLoggedIn() && in_array($user_role, ['client', 'b2b']); // ✅ RREGULLIM 2
$is_b2b      = ($user_role === 'b2b');

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$can_review) {
        setFlash('error', 'Duhet të hysh për të lënë vlerësim.');
    } else {
        $rating  = (int)$_POST['rating'];
        $comment = sanitize($_POST['comment']);
        $uid     = $_SESSION['user_id'];
        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?,?,?,?)");
            $stmt->bind_param("iiis", $uid, $id, $rating, $comment);
            $stmt->execute();
            $conn->query("UPDATE products SET
                rating = (SELECT AVG(rating) FROM reviews WHERE product_id = $id AND status='approved'),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE product_id = $id AND status='approved')
                WHERE id = $id");
            setFlash('success', 'Faleminderit për vlerësimin!');
            header("Location: product_detail.php?id=$id");
            exit;
        }
    }
}

$page_title = $product['name'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>" class="text-success text-decoration-none">🏠 Kryefaqja</a></li>
            <li class="breadcrumb-item"><a href="products.php" class="text-success text-decoration-none">Produktet</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?= $product['cat_slug'] ?>" class="text-success text-decoration-none"><?= $product['cat_name'] ?></a></li>
            <li class="breadcrumb-item active"><?= $product['name'] ?></li>
        </ol>
    </nav>

    <!-- PRODUKT KRYESOR -->
    <div class="row g-5 mb-5">

        <!-- Imazhi -->
        <div class="col-lg-5">
            <div class="position-relative rounded-4 overflow-hidden shadow-lg" style="height:420px; background:#f0fdf4;">
                <img src="<?= $product['image'] ? SITE_URL . '/uploads/products/' . $product['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                     class="w-100 h-100" style="object-fit:cover;" alt="<?= $product['name'] ?>">
                <?php if ($product['is_certified']): ?>
                <div class="position-absolute top-0 start-0 m-3">
                    <span class="badge fs-6 px-3 py-2" style="background:linear-gradient(135deg,#ffc107,#ff8f00);color:#000;border-radius:50px;">
                        ✅ Partner i Certifikuar
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($product['is_organic']): ?>
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="badge bg-success fs-6 px-3 py-2" style="border-radius:50px;">🌱 Organik</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detajet -->
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-light text-success border border-success"><?= $product['cat_name'] ?></span>
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success">✓ Në stok</span>
                <?php else: ?>
                    <span class="badge bg-danger">✗ Pa stok</span>
                <?php endif; ?>
                <?php if ($is_b2b): ?>
                    <span class="badge bg-info text-dark">💼 B2B</span>
                <?php endif; ?>
            </div>

            <h1 class="fw-bold mb-2" style="font-size:2.2rem;"><?= $product['name'] ?></h1>

            <!-- Rating -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="d-flex align-items-center gap-1">
                    <?php
                    $r = round($product['rating']);
                    for ($i=1;$i<=5;$i++) echo $i<=$r ? '<span style="color:#ffc107;font-size:1.3rem;">★</span>' : '<span style="color:#ddd;font-size:1.3rem;">★</span>';
                    ?>
                    <span class="fw-bold ms-1"><?= number_format($product['rating'],1) ?></span>
                </div>
                <span class="text-muted">(<?= $product['total_reviews'] ?> vlerësime)</span>
            </div>

            <!-- Çmimi -->
            <div class="p-4 rounded-3 mb-4" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                <?php if ($is_b2b && $product['b2b_price']): ?>
                    <!-- ✅ RREGULLIM 3: Shfaq çmimin B2B si kryesor -->
                    <div class="d-flex align-items-end gap-3">
                        <span class="fw-bold text-primary" style="font-size:2.5rem;"><?= formatPrice($product['b2b_price']) ?></span>
                        <span class="text-muted mb-2 fs-5">/ <?= $product['unit'] ?></span>
                    </div>
                    <div class="mt-1">
                        <small class="text-muted text-decoration-line-through">Çmimi normal: <?= formatPrice($product['price']) ?></small>
                        <span class="badge bg-primary ms-2">💼 Çmim B2B</span>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-end gap-3">
                        <span class="fw-bold text-success" style="font-size:2.5rem;"><?= formatPrice($product['price']) ?></span>
                        <span class="text-muted mb-2 fs-5">/ <?= $product['unit'] ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ✅ RREGULLIM 1: Butoni "Shto në Shportë" për client DHE b2b -->
            <?php if ($can_buy): ?>
            <div class="d-flex gap-3 mb-4">
                <div class="input-group" style="width:160px;">
                    <button class="btn btn-outline-success" type="button" id="qty-minus">−</button>
                    <input type="number" class="form-control text-center fw-bold" id="qty-input" value="1" min="0.5" step="0.5">
                    <button class="btn btn-outline-success" type="button" id="qty-plus">+</button>
                </div>
                <button class="btn btn-success px-4 fw-bold flex-grow-1"
                        onclick="addToCartDetail(<?= $product['id'] ?>)"
                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-cart-plus me-2"></i>Shto në Shportë
                </button>
            </div>

            <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <a href="<?= SITE_URL ?>/auth/login.php" class="alert-link fw-bold">Hyr</a>
                për të blerë këtë produkt.
            </div>
            <?php endif; ?>

            <!-- Info shtesë -->
            <div class="row g-3">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <div class="fs-4 mb-1">📦</div>
                        <div class="small text-muted">Stoku</div>
                        <div class="fw-bold"><?= $product['stock'] ?> <?= $product['unit'] ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <div class="fs-4 mb-1">🚚</div>
                        <div class="small text-muted">Dërgesa</div>
                        <div class="fw-bold text-success">Falas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-4 border-0" id="productTabs">
        <li class="nav-item">
            <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-desc">📋 Përshkrimi</button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-partner">🏪 Partneri</button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-reviews">⭐ Vlerësimet (<?= count($reviews) ?>)</button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- Tab: Përshkrimi -->
        <div class="tab-pane fade show active" id="tab-desc">
            <div class="card border-0 shadow-sm p-4">
                <p class="lead text-muted"><?= nl2br($product['description'] ?: 'Nuk ka përshkrim të disponueshëm.') ?></p>
            </div>
        </div>

        <!-- Tab: Partneri -->
        <div class="tab-pane fade" id="tab-partner">
            <div class="card border-0 shadow-sm p-4">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <div style="width:80px;height:80px;background:linear-gradient(135deg,#198754,#0d6efd);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:auto;">
                            🏪
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h4 class="fw-bold mb-1"><?= $product['business_name'] ?></h4>
                        <?php if ($product['is_certified']): ?>
                        <span class="badge" style="background:linear-gradient(135deg,#ffc107,#ff8f00);color:#000;">✅ Partner i Certifikuar</span>
                        <?php endif; ?>
                        <p class="text-muted mt-2 mb-1"><?= $product['business_description'] ?: 'Furnizues i verifikuar i platformës.' ?></p>
                        <?php if ($product['certification_date']): ?>
                        <small class="text-muted">📅 Certifikuar: <?= date('d/m/Y', strtotime($product['certification_date'])) ?></small>
                        <?php endif; ?>
                        <?php if ($product['inspection_date']): ?>
                        <small class="text-muted ms-3">🔍 Inspektimi: <?= date('d/m/Y', strtotime($product['inspection_date'])) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="mb-2">
                            <?php
                            $pr = round($product['partner_rating']);
                            for ($i=1;$i<=5;$i++) echo $i<=$pr ? '<span style="color:#ffc107;">★</span>' : '<span style="color:#ddd;">★</span>';
                            ?>
                        </div>
                        <div class="fw-bold fs-4"><?= number_format($product['partner_rating'],1) ?>/5</div>
                        <small class="text-muted"><?= $product['partner_reviews'] ?> vlerësime</small>
                        <br>
                        <a href="<?= SITE_URL ?>/pages/partner_profile.php?id=<?= $product['partner_id'] ?>"
                           class="btn btn-outline-success btn-sm mt-2">Shiko Profilin</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Vlerësimet -->
        <div class="tab-pane fade" id="tab-reviews">
            <div class="card border-0 shadow-sm p-4 mb-4">

                <!-- ✅ RREGULLIM 2: Forma vlerësimit për client DHE b2b -->
                <?php if ($can_review): ?>
                <h5 class="fw-bold mb-3">✍️ Lër vlerësimin tënd</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vlerësimi</label>
                        <div class="star-rating d-flex gap-2 fs-2" id="star-container">
                            <?php for ($i=1;$i<=5;$i++): ?>
                            <span class="star-btn" data-value="<?= $i ?>" style="cursor:pointer;color:#ddd;">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Komenti</label>
                        <textarea name="comment" class="form-control" rows="3"
                                  placeholder="Shkruaj mendimin tënd..." required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Dërgo Vlerësimin
                    </button>
                </form>
                <hr class="my-4">
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                <p class="text-muted text-center py-3">Ky produkt nuk ka vlerësime ende. Bëhu i pari!</p>
                <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= $rev['reviewer_name'] ?></strong>
                            <div class="my-1">
                                <?php for ($i=1;$i<=5;$i++) echo $i<=$rev['rating'] ? '<span style="color:#ffc107;">★</span>' : '<span style="color:#ddd;">★</span>'; ?>
                            </div>
                            <p class="mb-0 text-muted"><?= nl2br(sanitize($rev['comment'])) ?></p>
                        </div>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Produkte të ngjashme -->
    <?php if (!empty($similar)): ?>
    <div class="mt-5">
        <h4 class="fw-bold mb-4">🔗 Produkte të ngjashme</h4>
        <div class="row g-4">
            <?php foreach ($similar as $sp): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm product-card h-100">
                    <img src="<?= $sp['image'] ? SITE_URL . '/uploads/products/' . $sp['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                         class="card-img-top" style="height:160px;object-fit:cover;" alt="<?= $sp['name'] ?>">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1"><?= $sp['name'] ?></h6>
                        <p class="text-muted small mb-2">🏪 <?= $sp['business_name'] ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold"><?= formatPrice($sp['price']) ?></span>
                            <a href="product_detail.php?id=<?= $sp['id'] ?>" class="btn btn-sm btn-outline-success">Shiko</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';

// Qty buttons
document.getElementById('qty-minus')?.addEventListener('click', () => {
    const inp = document.getElementById('qty-input');
    const v = parseFloat(inp.value) - 0.5;
    if (v >= 0.5) inp.value = v;
});
document.getElementById('qty-plus')?.addEventListener('click', () => {
    const inp = document.getElementById('qty-input');
    inp.value = (parseFloat(inp.value) + 0.5).toFixed(1);
});

function addToCartDetail(productId) {
    const qty = document.getElementById('qty-input')?.value || 1;
    fetch(SITE_URL + '/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&product_id=${productId}&quantity=${qty}`
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            // Përditëso numrin e shportës në navbar
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = data.cart_count;
        } else {
            showToast('❌ ' + (data.error || 'Gabim!'), 'danger');
        }
    });
}

// Star rating
document.querySelectorAll('.star-btn').forEach(star => {
    star.addEventListener('click', function() {
        const val = this.dataset.value;
        document.getElementById('rating-input').value = val;
        document.querySelectorAll('.star-btn').forEach((s, i) => {
            s.style.color = i < val ? '#ffc107' : '#ddd';
        });
    });
    star.addEventListener('mouseover', function() {
        const val = this.dataset.value;
        document.querySelectorAll('.star-btn').forEach((s, i) => {
            s.style.color = i < val ? '#ffc107' : '#ddd';
        });
    });
});
document.getElementById('star-container')?.addEventListener('mouseleave', () => {
    const val = document.getElementById('rating-input')?.value || 0;
    document.querySelectorAll('.star-btn').forEach((s, i) => {
        s.style.color = i < val ? '#ffc107' : '#ddd';
    });
});

function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `alert alert-${type} position-fixed shadow-lg`;
    t.style.cssText = 'bottom:20px;right:20px;z-index:9999;min-width:300px;';
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>