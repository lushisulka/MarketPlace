<?php
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$_partner = $stmt->get_result()->fetch_assoc();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body text-center py-4">
        <div class="fs-1 mb-2">🏪</div>
        <h6 class="fw-bold mb-1"><?= $_partner['business_name'] ?? '' ?></h6>
        <?php if ($_partner['is_certified'] ?? false): ?>
        <span class="badge bg-warning text-dark small">✅ Certifikuar</span>
        <?php else: ?>
        <span class="badge bg-secondary small">⏳ Në pritje</span>
        <?php endif; ?>
    </div>
    <ul class="list-group list-group-flush">
        <?php
        $menu = [
            'dashboard.php'   => ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard'],
            'products.php'    => ['icon'=>'fas fa-boxes','label'=>'Produktet'],
            'add_product.php' => ['icon'=>'fas fa-plus-circle','label'=>'Shto Produkt'],
            'orders.php'      => ['icon'=>'fas fa-shopping-bag','label'=>'Porositë'],
            'statistics.php'  => ['icon'=>'fas fa-chart-bar','label'=>'Statistika'],
        ];
        foreach ($menu as $file => $item):
            $active = ($current_page === $file) ? 'bg-success-subtle fw-bold text-success' : 'text-dark';
        ?>
        <li class="list-group-item border-0 p-0">
            <a href="<?= $file ?>" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none <?= $active ?>">
                <i class="<?= $item['icon'] ?> text-success" style="width:18px;"></i>
                <?= $item['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>