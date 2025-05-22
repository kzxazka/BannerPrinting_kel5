<?php
//Mulai Session
session_start();
require_once '../config.php';

// Cek status login
$is_logged_in = false;
if (isset($_SESSION["ses_username"])) {
    $is_logged_in = true;
    $data_id = $_SESSION["ses_id"];
    $data_nama = $_SESSION["ses_nama"];
    $data_user = $_SESSION["ses_username"];
}

// Ambil produk unggulan
$featured_products = [];
$query_produk = "SELECT * FROM products WHERE is_featured = 1 LIMIT 6";
try {
    $result = mysqli_query($conn, $query_produk);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $featured_products[] = $row;
        }
    }
} catch (Exception $e) {
    // Log error jika diperlukan
    error_log("Error fetching featured products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danis Printing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-ORZOctTLqAW56siD"></script>
</head>

<body>
    <?php include ('../view/header.php'); ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 text-center text-lg-start mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-3">Jasa Percetakan Banner Berkualitas</h1>
                    <p class="lead mb-4">Danis Printing menyediakan layanan percetakan banner, spanduk, dan berbagai kebutuhan promosi bisnis Anda dengan kualitas terbaik dan harga terjangkau.</p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                        <a href="<?= $base_url ?>/user/product-update.php" class="btn btn-light btn-lg">
                            <i class="bi bi-cart-plus me-2"></i>Pesan Sekarang
                        </a>
                        <a href="<?= $base_url ?>/user/cek-pesanan.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-search me-2"></i>Cek Pesanan
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <img src="../assets/img/danisprinting.png" alt="Digital Printing Services" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Counter Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="counter-box">
                        <div class="counter-number">500+</div>
                        <h5>Pelanggan Puas</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="counter-box">
                        <div class="counter-number">1000+</div>
                        <h5>Proyek Selesai</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="counter-box">
                        <div class="counter-number">5+</div>
                        <h5>Tahun Pengalaman</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="counter-box">
                        <div class="counter-number">10+</div>
                        <h5>Jenis Produk</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center section-title">Kenapa Memilih Kami?</h2>
            <div class="row mt-5">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card h-100 text-center p-4">
                        <div class="card-icon">
                            <i class="bi bi-printer-fill" style="font-size: 3rem;"></i>
                        </div>
                        <div class="card-body px-0 pb-0">
                            <h5 class="card-title">Kualitas Terbaik</h5>
                            <p class="card-text">Kami menggunakan mesin dan bahan berkualitas tinggi untuk hasil terbaik, dengan ketajaman warna dan ketahanan luar biasa.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card h-100 text-center p-4">
                        <div class="card-icon">
                            <i class="bi bi-clock-fill" style="font-size: 3rem;"></i>
                        </div>
                        <div class="card-body px-0 pb-0">
                            <h5 class="card-title">Proses Cepat</h5>
                            <p class="card-text">Pengerjaan cepat dan tepat waktu sesuai dengan kebutuhan Anda, bahkan untuk pesanan mendesak.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card h-100 text-center p-4">
                        <div class="card-icon">
                            <i class="bi bi-award-fill" style="font-size: 3rem;"></i>
                        </div>
                        <div class="card-body px-0 pb-0">
                            <h5 class="card-title">Harga Terjangkau</h5>
                            <p class="card-text">Harga kompetitif dengan kualitas yang tidak mengecewakan, disertai diskon untuk pemesanan dalam jumlah besar.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card h-100 text-center p-4">
                        <div class="card-icon">
                            <i class="bi bi-truck" style="font-size: 3rem;"></i>
                        </div>
                        <div class="card-body px-0 pb-0">
                            <h5 class="card-title">Pengiriman</h5>
                            <p class="card-text">Layanan pengiriman ke seluruh wilayah dengan aman dan cepat, dengan kemasan khusus anti kerusakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title">Produk Kami</h2>
            <div class="row mt-5">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="product-card h-100">
                        <div class="img-overlay">
                            <img src="../assets/img/banner.jpg" class="card-img-top" alt="Banner" style="height: 220px; object-fit: cover;">
                            <div class="overlay-content">
                                <a href="<?= $base_url ?>/user/product-update.php?product=banner" class="btn btn-primary">Pesan Sekarang</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title">Banner</h5>
                            <p class="card-text">Banner berkualitas tinggi dengan berbagai ukuran sesuai kebutuhan Anda. Ideal untuk promosi indoor dan outdoor.</p>
                            <p class="text-primary fw-bold">Mulai dari Rp 35.000/m²</p>
                            <a href="<?= $base_url ?>/user/product-update.php?product=banner" class="btn btn-outline-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="product-card h-100">
                        <div class="img-overlay">
                            <img src="../assets/img/spanduk.jpg" class="card-img-top" alt="Spanduk" style="height: 220px; object-fit: cover;">
                            <div class="overlay-content">
                                <a href="<?= $base_url ?>/user/product-update.php?product=spanduk" class="btn btn-primary">Pesan Sekarang</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title">Spanduk</h5>
                            <p class="card-text">Spanduk dengan bahan tahan lama dan desain yang menarik untuk acara atau promosi bisnis Anda.</p>
                            <p class="text-primary fw-bold">Mulai dari Rp 25.000/m²</p>
                            <a href="<?= $base_url ?>/user/product-update.php?product=spanduk" class="btn btn-outline-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="product-card h-100">
                        <div class="img-overlay">
                            <img src="../assets/img/xbanner.jpg" class="card-img-top" alt="X-Banner" style="height: 220px; object-fit: cover;">
                            <div class="overlay-content">
                                <a href="<?= $base_url ?>/user/product-update.php?product=xbanner" class="btn btn-primary">Pesan Sekarang</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title">X-Banner</h5>
                            <p class="card-text">X-Banner yang praktis dan eye-catching untuk pameran, presentasi, atau display di toko Anda.</p>
                            <p class="text-primary fw-bold">Mulai dari Rp 85.000/pcs</p>
                            <a href="<?= $base_url ?>/user/product-update.php?product=xbanner" class="btn btn-outline-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="<?= $base_url ?>/products.php" class="btn btn-outline-primary">Lihat Semua Produk <i class="bi bi-arrow-right ms-2"></i></a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center section-title">Cara Pemesanan</h2>
            <div class="row mt-5">
                <div class="col-lg-3 col-md-6 mb-4 text-center">
                    <div class="border border-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                        <h3 class="mb-0 text-primary">1</h3>
                    </div>
                    <h5 class="mt-3">Pilih Produk</h5>
                    <p>Pilih produk yang Anda butuhkan dari katalog kami</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 text-center">
                    <div class="border border-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                        <h3 class="mb-0 text-primary">2</h3>
                    </div>
                    <h5 class="mt-3">Kirim Desain</h5>
                    <p>Upload desain Anda atau minta bantuan tim desain kami</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 text-center">
                    <div class="border border-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                        <h3 class="mb-0 text-primary">3</h3>
                    </div>
                    <h5 class="mt-3">Pembayaran</h5>
                    <p>Lakukan pembayaran dengan metode yang tersedia</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 text-center">
                    <div class="border border-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                        <h3 class="mb-0 text-primary">4</h3>
                    </div>
                    <h5 class="mt-3">Cetak & Kirim</h5>
                    <p>Kami proses dan kirim produk ke tempat Anda</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section
    <section class="testimonial py-5">
        <div class="container">
            <h2 class="text-center section-title">Apa Kata Pelanggan Kami</h2>
            <div class="row mt-5">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="testimonial-card h-100">
                        <div class="mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="mb-3">"Kualitas cetak banner sangat bagus, warna tajam dan konsisten. Pengiriman juga cepat. Sangat merekomendasikan Danis Printing!"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <span class="text-white fw-bold">AB</span>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Ahmad Baihaki</h6>
                                <small class="text-muted">Pemilik Toko</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="testimonial-card h-100">
                        <div class="mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-half text-warning"></i>
                        </div>
                        <p class="mb-3">"Sudah dua kali pesan spanduk untuk acara kampus, hasilnya selalu memuaskan. Tim customer service juga ramah dan responsif."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <span class="text-white fw-bold">LS</span>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Lisa Susanti</h6>
                                <small class="text-muted">Mahasiswa</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="testimonial-card h-100">
                        <div class="mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="mb-3">"Harga terjangkau dengan kualitas premium. Proses pemesanan online sangat mudah dan cepat. Pasti akan kembali untuk pesan lagi!"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <span class="text-white fw-bold">DP</span>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Denny Pratama</h6>
                                <small class="text-muted">Event Organizer</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
-->

    <!-- CTA Section -->
    <section class="cta-section hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-3">Siap Untuk Memesan?</h2>
                    <p class="lead mb-4">Hubungi kami sekarang untuk konsultasi gratis atau langsung buat pesanan Anda secara online.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?= $base_url ?>/user/product-update.php" class="btn btn-light btn-lg">
                            <i class="bi bi-cart-plus me-2"></i>Pesan Sekarang
                        </a>
                        <a href="<?= $base_url ?>/contact.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-chat-dots me-2"></i>Hubungi Kami
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center section-title">Pertanyaan Umum</h2>
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item mb-3 border">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Berapa lama waktu pengerjaan?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Waktu pengerjaan tergantung pada jenis dan jumlah pesanan. Untuk banner dan spanduk standar, biasanya selesai dalam 1-2 hari kerja. Untuk pesanan dalam jumlah besar, bisa memakan waktu 3-5 hari kerja.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item mb-3 border">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Apakah ada minimal order?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tidak ada minimal order. Kami melayani pesanan satuan hingga pesanan dalam jumlah besar.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item mb-3 border">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Bagaimana proses pembayaran?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kami menerima pembayaran melalui transfer bank, QRIS, dan berbagai e-wallet populer. Untuk pesanan dalam jumlah besar, kami juga menerima sistem pembayaran DP.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Apakah bisa melayani desain custom?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya, kami menyediakan layanan desain custom dengan biaya tambahan. Tim desain profesional kami siap membantu mewujudkan ide Anda menjadi desain yang menarik dan efektif.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <?php include ('../view/footer.php'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Contact form submission
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Here you would typically send the form data to your server
                // For now, we'll just show a success message
                alert('Terima kasih! Pesan Anda telah dikirim. Kami akan menghubungi Anda segera.');
                contactForm.reset();
            });
        }
    </script>
</body>
</html>