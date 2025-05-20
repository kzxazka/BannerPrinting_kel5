<?php
session_start();
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION["ses_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["ses_id"];

// Filter status jika ada
$status_filter = $_GET['status'] ?? 'all';

// Modifikasi query berdasarkan filter
$base_query = "SELECT o.*, p.name as product_name, c.name as customer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN customers c ON o.customer_id = c.id 
          WHERE c.user_id = ?";

// Tambahkan kondisi berdasarkan filter
switch($status_filter) {
    case 'completed':
        $base_query .= " AND o.status = 'completed'";
        break;
    case 'active':
        $base_query .= " AND o.status != 'completed'";
        break;
    // case 'all': tidak perlu kondisi tambahan
}

$base_query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($base_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card {
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include('../view/header.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4">Riwayat Pesanan</h2>

        <!-- Filter Status -->
        <div class="mb-4">
            <div class="btn-group">
                <a href="?status=all" class="btn btn-outline-primary <?= $status_filter == 'all' ? 'active' : '' ?>">
                    Semua
                </a>
                <a href="?status=active" class="btn btn-outline-primary <?= $status_filter == 'active' ? 'active' : '' ?>">
                    Pesanan Aktif
                </a>
                <a href="?status=completed" class="btn btn-outline-primary <?= $status_filter == 'completed' ? 'active' : '' ?>">
                    Pesanan Selesai
                </a>
            </div>
        </div>

        <!-- Daftar Pesanan -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Jenis Produk</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0):
                        while ($order = $result->fetch_assoc()): 
                            // Filter berdasarkan status
                            if ($status_filter == 'active' && $order['status'] == 'completed') continue;
                            if ($status_filter == 'completed' && $order['status'] != 'completed') continue;
                    ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['product_name']) ?></td>
                        <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <?php if ($order['status'] != 'completed'): ?>
                                <a href="order_receipt.php?order_id=<?= $order['id'] ?>" 
                                   class="btn btn-info btn-sm">
                                    <i class="fas fa-info-circle"></i> Detail
                                </a>
                            <?php else: ?>
                                <a href="product-update.php?customer_id=<?= $order['customer_id'] ?>&reorder=true" 
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-redo"></i> Pesan Lagi
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada pesanan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>


    <?php include('../view/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
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
        case 'admin_confirmed': return 'Dikonfirmasi Admin';
        case 'confirmed': return 'Dikonfirmasi';
        case 'processing': return 'Sedang Diproses';
        case 'in_progress': return 'Sedang Diproses';
        case 'completed': return 'Selesai';
        default: return 'Unknown';
    }
}

function getProductionStatusText($status) {
    switch($status) {
        case 'pending': return 'Menunggu Konfirmasi';
        case 'confirmed': return 'Dikonfirmasi';
        case 'in_progress': return 'Dalam Proses';
        case 'completed': return 'Selesai';
        default: return 'Unknown';
    }
}
?>