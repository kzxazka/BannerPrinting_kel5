<?php
session_start();
require_once '../config.php';
?>

<header>
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-primary" href="<?= $base_url ?>/user/index-update.php">Danis Printing</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link active" aria-current="page" href="<?= $base_url ?>/user/index-update.php">Beranda</a>
                    <a class="nav-link" href="<?= $base_url ?>/user/product-update.php">Pesan</a>
                    <a class="nav-link" href="<?= $base_url ?>/user/cek-pesanan.php">Cek Pesanan</a>
                    <a class="nav-link" href="<?= $base_url ?>/user/pesanan-customer.php">Pesanan Saya</a>
                </div>
                
                <div class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['ses_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fs-5 me-2"></i>
                                <span class="me-1"><?php echo $_SESSION['ses_nama']; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="../user/my-account.php">
                                    <i class="fas fa-user me-2"></i>Akun Saya
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../user/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Keluar
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="../user/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
    </html>
