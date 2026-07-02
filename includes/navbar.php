<?php
// Shporta shfaqet për client DHE b2b
$show_cart = isLoggedIn() && in_array($_SESSION['user_role'], ['client', 'b2b']);
$cart_count = $show_cart ? getCartCount($conn, $_SESSION['user_id']) : 0;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="<?= SITE_URL ?>">
            🌿 <?= SITE_NAME ?>
        </a>

        <!-- Search Bar -->
        <form class="d-flex mx-auto w-50" action="<?= SITE_URL ?>/pages/search.php" method="GET">
            <div class="input-group">
                <input class="form-control" type="search" name="q"
                       placeholder="Kërko fruta, perime..."
                       value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button class="btn btn-warning" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <div class="navbar-nav ms-3 flex-row gap-2 align-items-center">
            <?php if (isLoggedIn()): ?>

                <!-- Ikona e shportës — client dhe b2b -->
                <?php if ($show_cart): ?>
                <a href="<?= SITE_URL ?>/client/cart.php" class="btn btn-warning position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $cart_count ?>
                    </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <!-- Dropdown llogarie -->
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= $_SESSION['user_name'] ?>
                        <span class="badge bg-warning text-dark ms-1"><?= ucfirst($_SESSION['user_role']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <?php
                        $dash = match($_SESSION['user_role']) {
                            'partner' => '/partner/dashboard.php',
                            'b2b'     => '/b2b/dashboard.php',
                            'admin'   => '/admin/dashboard.php',
                            default   => '/client/dashboard.php'
                        };
                        ?>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL . $dash ?>">
                                <i class="fas fa-tachometer-alt me-2 text-success"></i>Dashboard
                            </a>
                        </li>
                        <?php if ($show_cart): ?>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/client/cart.php">
                                <i class="fas fa-shopping-cart me-2 text-success"></i>Shporta
                                <?php if ($cart_count > 0): ?>
                                <span class="badge bg-danger ms-1"><?= $cart_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= SITE_URL ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Dil
                            </a>
                        </li>
                    </ul>
                </div>

            <?php else: ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline-light">Hyr</a>
                <a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-warning fw-bold">Regjistrohu</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible m-0 rounded-0" role="alert">
    <div class="container"><?= $flash['message'] ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>