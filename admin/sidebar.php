<?php
$current_page     = basename($_SERVER['PHP_SELF']);
$pending_count    = $conn->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'")->fetch_row()[0];
$pending_b2b      = $conn->query("SELECT COUNT(*) FROM b2b_clients WHERE status = 'pending'")->fetch_row()[0];
$menu = [
    'dashboard.php'  => ['icon'=>'fas fa-tachometer-alt',     'label'=>'Dashboard'],
    'users.php'      => ['icon'=>'fas fa-users',              'label'=>'Përdoruesit'],
    'partners.php'   => ['icon'=>'fas fa-store',              'label'=>'Partnerët',   'badge'=>$pending_count],
    'b2b.php'        => ['icon'=>'fas fa-building',           'label'=>'Klientët B2B','badge'=>$pending_b2b],
    'products.php'   => ['icon'=>'fas fa-boxes',              'label'=>'Produktet'],
    'orders.php'     => ['icon'=>'fas fa-shopping-cart',      'label'=>'Porositë'],
    'complaints.php' => ['icon'=>'fas fa-exclamation-circle', 'label'=>'Ankesat'],
];
?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-3">
        <div class="fs-2 mb-1">🛡️</div>
        <div class="fw-bold small text-muted">ADMIN PANEL</div>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($menu as $file => $item):
            $active = ($current_page === $file);
        ?>
        <li class="list-group-item border-0 p-0">
            <a href="<?= $file ?>" class="d-flex align-items-center justify-content-between px-3 py-2 text-decoration-none <?= $active ? 'bg-success-subtle text-success fw-bold' : 'text-dark' ?>">
                <span><i class="<?= $item['icon'] ?> me-2 text-success"></i><?= $item['label'] ?></span>
                <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                <span class="badge bg-danger"><?= $item['badge'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
