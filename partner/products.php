<?php
require_once __DIR__ . '/../config/config.php';
requireRole('partner');
$page_title = 'Produktet e Mia';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$pid = $partner['id'];

$products = $conn->query("
    SELECT p.*, c.name as cat_name
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.partner_id = $pid
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

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
                <h4 class="fw-bold mb-0">📦 Produktet e Mia <span class="badge bg-success"><?= count($products) ?></span></h4>
                <a href="add_product.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Shto Produkt</a>
            </div>

            <?php if (empty($products)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="fs-1 mb-3">📦</div>
                    <h5 class="text-muted">Nuk ke produkte ende</h5>
                    <a href="add_product.php" class="btn btn-success mt-3">Shto produktin e parë</a>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produkti</th>
                                <th>Kategoria</th>
                                <th>Çmimi</th>
                                <th>Stoku</th>
                                <th>Vlerësimi</th>
                                <th>Statusi</th>
                                <th>Veprime</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                                             style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                        <div>
                                            <div class="fw-semibold"><?= $p['name'] ?></div>
                                            <?php if ($p['is_organic']): ?>
                                            <span class="badge bg-success-subtle text-success small">🌱 Organik</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?= $p['cat_name'] ?></span></td>
                                <td class="fw-bold text-success"><?= formatPrice($p['price']) ?></td>
                                <td>
                                    <span class="<?= $p['stock'] <= 5 ? 'text-danger fw-bold' : '' ?>">
                                        <?= $p['stock'] ?> <?= $p['unit'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color:#ffc107;">★</span>
                                    <?= number_format($p['rating'], 1) ?>
                                    <small class="text-muted">(<?= $p['total_reviews'] ?>)</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $p['status'] === 'active' ? '✅ Aktiv' : '⏸ Joaktiv' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="add_product.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">✏️</a>
                                        <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" target="_blank">👁</a>
                                        <a href="products.php?delete=<?= $p['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Jeni i sigurt? Ky veprim nuk mund të kthehet!')">🗑</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>