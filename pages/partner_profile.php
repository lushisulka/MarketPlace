<?php
require_once __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/products.php'); exit; }

$stmt = $conn->prepare("
    SELECT pt.*, u.name as owner_name, u.email, u.created_at as member_since
    FROM partners pt JOIN users u ON pt.user_id = u.id
    WHERE pt.id = ? AND pt.status = 'approved'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();

if (!$partner) {
    setFlash('error', 'Ky partner nuk u gjet.');
    header('Location: ' . SITE_URL . '/pages/products.php');
    exit;
}

$products = $conn->query("
    SELECT p.*, c.name as cat_name
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.partner_id = $id AND p.status = 'active'
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$reviews = $conn->query("
    SELECT r.*, u.name as reviewer_name
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.partner_id = $id AND r.status = 'approved'
    ORDER BY r.created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$total_orders = $conn->query("SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.partner_id = $id")->fetch_row()[0];

// Vlerëso partnerin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_partner_review']) && isLoggedIn()) {
    $rating  = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment']);
    $uid     = $_SESSION['user_id'];
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, partner_id, rating, comment) VALUES (?,?,?,?)");
        $stmt->bind_param("iiis", $uid, $id, $rating, $comment);
        $stmt->execute();
        $conn->query("UPDATE partners SET
            rating = (SELECT AVG(rating) FROM reviews WHERE partner_id = $id AND status='approved'),
            total_reviews = (SELECT COUNT(*) FROM reviews WHERE partner_id = $id AND status='approved')
            WHERE id = $id");
        setFlash('success', 'Faleminderit për vlerësimin!');
        header("Location: partner_profile.php?id=$id");
        exit;
    }
}

$page_title = $partner['business_name'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">

    <!-- HEADER -->
    <div class="card border-0 shadow-sm mb-5 overflow-hidden">
        <div style="background:linear-gradient(135deg,#155724,#198754,#20c997);height:160px;position:relative;">
            <?php if ($partner['is_certified']): ?>
            <div class="position-absolute top-0 end-0 m-3">
                <span class="badge px-3 py-2 fs-6" style="background:rgba(255,255,255,0.2);backdrop-filter:blur(8px);color:white;border:1px solid rgba(255,255,255,0.3);">
                    🏅 Partner i Certifikuar
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body pt-0">
            <div class="row align-items-end g-3" style="margin-top:-55px;">
                <div class="col-auto">
                    <div class="rounded-4 shadow-lg d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:110px;height:110px;font-size:3rem;background:linear-gradient(135deg,#0d6efd,#198754);border:4px solid white;">
                        <?= strtoupper(substr($partner['business_name'], 0, 1)) ?>
                    </div>
                </div>
                <div class="col pb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="fw-bold mb-0"><?= $partner['business_name'] ?></h2>
                        <?php if ($partner['is_certified']): ?>
                        <span class="badge fs-6 px-3 py-1" style="background:linear-gradient(135deg,#ffc107,#ff8f00);color:#000;border-radius:50px;">✅ Certifikuar</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php $r = round($partner['rating']); for ($i=1;$i<=5;$i++) echo '<span style="color:' . ($i<=$r?'#ffc107':'#ddd') . ';font-size:1.1rem;">★</span>'; ?>
                        <span class="fw-bold"><?= number_format($partner['rating'],1) ?></span>
                        <span class="text-muted small">(<?= $partner['total_reviews'] ?> vlerësime)</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted small">📦 <?= count($products) ?> produkte</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted small">🛒 <?= $total_orders ?> porosi</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted small">📅 <?= date('M Y', strtotime($partner['member_since'])) ?></span>
                    </div>
                    <?php if ($partner['business_description']): ?>
                    <p class="text-muted mt-2 mb-0 small"><?= $partner['business_description'] ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($partner['is_certified'] && $partner['certification_date']): ?>
                <div class="col-auto pb-2">
                    <div class="p-3 rounded-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="fs-2">🏅</div>
                        <div class="small text-muted">Certifikuar</div>
                        <div class="fw-bold text-success small"><?= date('d/m/Y', strtotime($partner['certification_date'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DOKUMENTET -->
    <?php if ($partner['license_document'] || $partner['certificate_document']): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">📋 Dokumentet Zyrtare</h5>
            <div class="d-flex gap-3 flex-wrap">
                <?php if ($partner['license_document']): ?>
                <a href="<?= SITE_URL ?>/uploads/documents/<?= $partner['license_document'] ?>" target="_blank" class="btn btn-outline-primary">📄 Licenca</a>
                <?php endif; ?>
                <?php if ($partner['certificate_document']): ?>
                <a href="<?= SITE_URL ?>/uploads/documents/<?= $partner['certificate_document'] ?>" target="_blank" class="btn btn-outline-success">📜 Certifikata</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUKTET -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">🛒 Produktet</h4>
        <span class="badge bg-success"><?= count($products) ?></span>
    </div>

    <?php if (empty($products)): ?>
    <div class="card border-0 shadow-sm text-center py-5 mb-5">
        <div class="fs-1">📦</div>
        <p class="text-muted mt-2">Nuk ka produkte aktive.</p>
    </div>
    <?php else: ?>
    <div class="row g-4 mb-5">
        <?php foreach ($products as $p): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm product-card h-100">
                <div class="position-relative">
                    <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                         class="card-img-top" style="height:180px;object-fit:cover;" alt="<?= $p['name'] ?>">
                    <?php if ($p['is_organic']): ?><span class="position-absolute top-0 start-0 m-2 badge bg-success small">🌱</span><?php endif; ?>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-1"><?= $p['cat_name'] ?></p>
                    <h6 class="fw-bold mb-1"><?= $p['name'] ?></h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success"><?= formatPrice($p['price']) ?></span>
                        <span class="text-muted small">/<?= $p['unit'] ?></span>
                    </div>
                    <?php if ($p['b2b_price'] && isLoggedIn() && $_SESSION['user_role'] === 'b2b'): ?>
                    <div class="mt-1"><span class="badge bg-info text-dark small">💼 B2B: <?= formatPrice($p['b2b_price']) ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent border-0 p-3 pt-0">
                    <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm w-100">Shiko & Porosit</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- VLERËSIMET -->
    <div class="row g-4">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-4">⭐ Vlerësimet</h4>
            <?php if (empty($reviews)): ?>
            <div class="card border-0 shadow-sm text-center py-4">
                <p class="text-muted mb-0">Nuk ka vlerësime ende.</p>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($reviews as $rev): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center"
                                     style="width:42px;height:42px;"><?= strtoupper(substr($rev['reviewer_name'],0,1)) ?></div>
                                <div>
                                    <div class="fw-semibold"><?= $rev['reviewer_name'] ?></div>
                                    <div><?php for ($i=1;$i<=5;$i++) echo '<span style="color:' . ($i<=$rev['rating']?'#ffc107':'#ddd') . ';">★</span>'; ?></div>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></small>
                        </div>
                        <p class="text-muted mt-3 mb-0"><?= nl2br(sanitize($rev['comment'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Forma vlerësimit -->
        <div class="col-lg-4">
            <?php if (isLoggedIn() && in_array($_SESSION['user_role'], ['client','b2b'])): ?>
            <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">✍️ Vlerëso Partnerin</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <div class="d-flex gap-2 fs-2" id="star-container">
                                <?php for ($i=1;$i<=5;$i++): ?>
                                <span class="star-btn" data-value="<?= $i ?>" style="cursor:pointer;color:#ddd;">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="4"
                                      placeholder="Shkruaj mendimin tënd..." required></textarea>
                        </div>
                        <button type="submit" name="submit_partner_review" class="btn btn-success w-100 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Dërgo
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif (!isLoggedIn()): ?>
            <div class="card border-0 shadow-sm text-center py-4">
                <div class="fs-2 mb-2">🔑</div>
                <p class="text-muted small">Hyr për të lënë vlerësim.</p>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-success btn-sm">Hyr</a>
            </div>
            <?php endif; ?>
        </div>
        <h1>1</h1>
    </div>
</div>

<script>
document.querySelectorAll('.star-btn').forEach(star => {
    star.addEventListener('click', function() {
        const val = this.dataset.value;
        document.getElementById('rating-input').value = val;
        document.querySelectorAll('.star-btn').forEach((s,i) => s.style.color = i < val ? '#ffc107' : '#ddd');
    });
    star.addEventListener('mouseover', function() {
        const val = this.dataset.value;
        document.querySelectorAll('.star-btn').forEach((s,i) => s.style.color = i < val ? '#ffc107' : '#ddd');
    });
});
document.getElementById('star-container')?.addEventListener('mouseleave', () => {
    const val = document.getElementById('rating-input')?.value || 0;
    document.querySelectorAll('.star-btn').forEach((s,i) => s.style.color = i < val ? '#ffc107' : '#ddd');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>