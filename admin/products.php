<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$page_title = 'Menaxhimi i Produkteve';

// Toggle status
if (isset($_GET['toggle'])) {
    $pid = (int)$_GET['toggle'];
    $conn->query("UPDATE products SET status = IF(status='active','inactive','active') WHERE id = $pid");
    setFlash('success', 'Statusi u ndryshua.');
    header('Location: products.php');
    exit;
}
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $pid");
    setFlash('info', 'Produkti u fshi.');
    header('Location: products.php');
    exit;
}

$products = $conn->query("
    SELECT p.*, c.name as cat_name, pt.business_name, pt.is_certified
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN partners pt ON p.partner_id = pt.id
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
                <h4 class="fw-bold mb-0">📦 Menaxhimi i Produkteve <span class="badge bg-success"><?= count($products) ?></span></h4>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Produkti</th><th>Kategoria</th><th>Partneri</th><th>Çmimi</th><th>Stoku</th><th>Vlerësimi</th><th>Statusi</th><th>Veprime</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= $p['image'] ? SITE_URL . '/uploads/products/' . $p['image'] : SITE_URL . '/assets/images/no-image.png' ?>"
                                             style="width:45px;height:45px;object-fit:cover;border-radius:8px;">
                                        <span class="fw-semibold"><?= $p['name'] ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?= $p['cat_name'] ?></span></td>
                                <td>
                                    <?= $p['business_name'] ?>
                                    <?php if ($p['is_certified']): ?><br><span class="badge bg-warning text-dark" style="font-size:0.65rem;">✅ Cert.</span><?php endif; ?>
                                </td>
                                <td class="fw-bold text-success"><?= formatPrice($p['price']) ?></td>
                                <td class="<?= $p['stock'] <= 5 ? 'text-danger fw-bold' : '' ?>"><?= $p['stock'] ?></td>
                                <td><span style="color:#ffc107;">★</span> <?= number_format($p['rating'],1) ?></td>
                                <td>
                                    <span class="badge bg-<?= $p['status']==='active'?'success':'secondary' ?>">
                                        <?= $p['status']==='active'?'✅ Aktiv':'⏸ Joaktiv' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">👁</a>
                                        <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning"
                                           onclick="return confirm('Ndrysho statusin?')">
                                            <?= $p['status']==='active'?'⏸':'▶️' ?>
                                        </a>
                                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Fshi produktin? Ky veprim nuk kthehet!')">🗑</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>