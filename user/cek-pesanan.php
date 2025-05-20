<?php
session_start();
include("../config.php");

// Cek session
if (!isset($_SESSION["ses_username"])) {
    // Simpan halaman yang ingin dituju
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("location: login.php");
    exit();
}

// Ambil data session
$data_id = $_SESSION["ses_id"];
$data_nama = $_SESSION["ses_nama"];
$data_user = $_SESSION["ses_username"];
$data_level = $_SESSION["ses_level"];

// Proses pencarian pesanan
$order = null;
$message = '';

if (isset($_POST['cek'])) {
    $order_id = trim($_POST['kode_pesanan']);
    
    // Query untuk mencari pesanan
    $query = "SELECT o.*, p.name as product_name, c.name as customer_name 
              FROM orders o 
              JOIN products p ON o.product_id = p.id 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger mt-4">Pesanan tidak ditemukan!</div>';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'info';
        case 'confirmed': return 'primary';
        case 'processing': return 'info';
        case 'completed': return 'success';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'pending': return 'Menunggu Pembayaran';
        case 'paid': return 'Sudah Dibayar';
        case 'confirmed': return 'Dikonfirmasi';
        case 'processing': return 'Diproses';
        case 'completed': return 'Selesai';
        default: return 'Unknown';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pesanan - Danis Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../view/header.php'); ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Cek Status Pesanan</h4>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="kode_pesanan" class="form-label">Masukkan ID Pesanan:</label>
                                <input type="text" name="kode_pesanan" id="kode_pesanan" class="form-control" required 
                                       placeholder="Contoh: 123">
                                <div class="form-text">ID Pesanan dapat dilihat pada struk pesanan Anda.</div>
                            </div>
                            <button type="submit" name="cek" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cek Status
                            </button>
                        </form>

                        <?php if (!empty($message)): ?>
                            <div class="mt-4">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($order): ?>
                        <div class="mt-4">
                            <h5 class="mb-3">Detail Pesanan #<?php echo $order['id']; ?></h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tr>
                                        <td>Produk</td>
                                        <td>: <?php echo htmlspecialchars($order['product_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Total Harga</td>
                                        <td>: Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>: <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                            <?php echo getStatusText($order['status']); ?>
                                        </span></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Pesanan</td>
                                        <td>: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <a href="order_receipt.php?order_id=<?php echo $order['id']; ?>" 
                                   class="btn btn-info">
                                    <i class="fas fa-info-circle"></i> Lihat Detail Pesanan
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<br>
<br>
<br>
<br>
<br>
    <?php include('../view/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>