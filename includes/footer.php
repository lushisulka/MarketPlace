<footer class="bg-dark text-white mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="text-success fw-bold">🌿 <?= SITE_NAME ?></h5>
                <p class="text-muted">Platforma juaj e besuar për fruta dhe perime të freskëta dhe të certifikuara.</p>
            </div>
            <div class="col-md-2">
                <h6 class="fw-bold">Navigim</h6>
                <ul class="list-unstyled text-muted">
                    <li><a href="<?= SITE_URL ?>/pages/products.php" class="text-muted text-decoration-none">Produkte</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php?type=partner" class="text-muted text-decoration-none">Bëhu Partner</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php?type=b2b" class="text-muted text-decoration-none">B2B</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold">Kontakt</h6>
                <p class="text-muted mb-1"><i class="fas fa-envelope me-2"></i>info@frutamarket.al</p>
                <p class="text-muted mb-1"><i class="fas fa-phone me-2"></i>+355 69 XXX XXXX</p>
            </div>
        </div>
        <hr class="border-secondary mt-4">
        <p class="text-center text-muted mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Të gjitha të drejtat e rezervuara.</p>
    </div>
    <h1>1</h1>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>