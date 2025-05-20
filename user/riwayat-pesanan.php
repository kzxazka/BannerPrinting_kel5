<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION["ses_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["ses_id"];
$status_filter = $_GET['status'] ?? 'all';

// Query untuk mengambil semua pesanan user
$query = "SELECT o.*, p.name as product_name, c.name as customer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN customers c ON o.customer_id = c.id 
          WHERE c.user_id = ? 
          ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
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
    <div class="container mt-5">
        <h2 class="mb-4">Riwayat Pesanan</h2>

        <!-- Filter Status -->
        <div class="mb-4">
            <div class="btn-group" id="status-filters">
                <button type="button" class="btn btn-outline-primary active" data-status="all">Semua Pesanan</button>
                <button type="button" class="btn btn-outline-primary" data-status="active">Pesanan Aktif</button>
                <button type="button" class="btn btn-outline-primary" data-status="completed">Pesanan Selesai</button>
            </div>
        </div>

        <!-- Orders Container -->
        <div class="row" id="orders-container">
            <!-- Pesanan akan dimuat di sini -->
        </div>
    </div>

    <!-- Tambahkan tombol kembali -->
    <div class="container mb-5">
        <a href="index-update.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Load initial orders
        loadOrders('all');

        // Handle filter button clicks
        $('#status-filters button').click(function(e) {
            e.preventDefault();
            const status = $(this).data('status');
            
            // Update active button
            $('#status-filters button').removeClass('active');
            $(this).addClass('active');
            
            // Load orders
            loadOrders(status);
        });

        function loadOrders(status) {
            $.ajax({
                url: 'get_orders.php',
                type: 'GET',
                data: { status: status },
                beforeSend: function() {
                    $('#orders-container').html('<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                },
                success: function(response) {
                    $('#orders-container').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#orders-container').html('<div class="col-12"><div class="alert alert-danger">Terjadi kesalahan saat memuat data.</div></div>');
                }
            });
        }
    });
    </script>
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
        case 'confirmed': return 'Dikonfirmasi';
        case 'processing': return 'Diproses';
        case 'completed': return 'Selesai';
        default: return 'Unknown';
    }
}

function getProductionStatusText($status) {
    switch($status) {
        case 'pending': return 'Menunggu Konfirmasi';
        case 'confirmed': return 'Dikonfirmasi';
        case 'processing': return 'Dalam Proses';
        case 'completed': return 'Selesai';
        default: return 'Unknown';
    }
}
?>