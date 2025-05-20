<?php
session_start();
require_once '../config.php';
require_once(__DIR__ . '/php/email_functions.php');

// Query untuk mendapatkan data ringkasan
$new_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'paid' AND production_status = 'pending'";
$processing_query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('processing', 'in_progress')";
$completed_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'";
$refund_query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('refund_requested', 'refunded')";

$new_orders = $conn->query($new_orders_query)->fetch_assoc();
$processing_orders = $conn->query($processing_query)->fetch_assoc();
$completed_orders = $conn->query($completed_query)->fetch_assoc();
$refund_orders = $conn->query($refund_query)->fetch_assoc();

// Query untuk daftar pesanan
$orders_query = "
    SELECT o.*, c.name as customer_name, c.email, p.name as product_name 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC
";
$orders_result = $conn->query($orders_query);

// Define sendHTMLEmail if not already defined
if (!function_exists('sendHTMLEmail')) {
    function sendHTMLEmail($to, $subject, $message) {
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Danis Printing <no-reply@danisprinting.com>" . "\r\n";
        mail($to, $subject, $message, $headers);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle manual email sending
    if (isset($_POST['send_completion_email'])) {
        $order_id = $_POST['send_completion_email'];
        
        // Get order and customer details
        $order_query = "SELECT o.*, c.name as customer_name, c.email FROM orders o 
                        JOIN customers c ON o.customer_id = c.id 
                        WHERE o.id = ?";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order_data = $order_result->fetch_assoc();
        
        if ($order_data) {
            // Reuse existing email template and sending logic
            $to = $order_data['email'];
            $subject = "Pesanan #" . $order_id . " Telah Selesai - Danis Printing";
            // ... existing email template code ...
            
            if (sendHTMLEmail($to, $subject, $message)) {
                $_SESSION['message'] = "Email notifikasi berhasil dikirim!";
            } else {
                $_SESSION['error'] = "Gagal mengirim email notifikasi.";
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Order Status
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Get current order data
        $get_order = $conn->prepare("SELECT production_status FROM orders WHERE id = ?");
        $get_order->bind_param("i", $order_id);
        $get_order->execute();
        $order = $get_order->get_result()->fetch_assoc();
        
        // Update production status based on order status
        $production_status = $order['production_status']; // maintain existing status
        
        if ($new_status === 'refund_requested' || 
            $new_status === 'refund_approved' || 
            $new_status === 'refund_completed') {
            // Reset production status if entering refund flow
            $production_status = 'pending';
        } else if ($new_status === 'processing') {
            $production_status = 'processing';
        } else if ($new_status === 'completed') {
            $production_status = 'completed';
        }
        
        // Prepare update statement
        $update_sql = "UPDATE orders SET status = ?, production_status = ?, admin_notes = ?, status_updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $new_status, $production_status, $admin_notes, $order_id);
        
        if ($stmt->execute()) {
            // If status changed to completed, send notification email
            if ($production_status === 'completed') {
                // Get order and customer details
                $order_query = "SELECT o.*, c.name as customer_name, c.email FROM orders o 
                                JOIN customers c ON o.customer_id = c.id 
                                WHERE o.id = ?";
                $order_stmt = $conn->prepare($order_query);
                $order_stmt->bind_param("i", $order_id);
                $order_stmt->execute();
                $order_result = $order_stmt->get_result();
                $order_data = $order_result->fetch_assoc();
                
                // Send completion email
                if ($order_data) {
                    $to = $order_data['email'];
                    $subject = "Pesanan #" . $order_id . " Telah Selesai - Danis Printing";
                    $message = "
                    <html>
                    <head>
                        <title>Pesanan Anda Telah Selesai</title>
                    </head>
                    <body>
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background-color: #4CAF50; color: white; padding: 20px; text-align: center;'>
                                <h2>Pesanan Anda Telah Selesai</h2>
                            </div>
                            <div style='padding: 20px; border: 1px solid #ddd;'>
                                <p>Halo <strong>{$order_data['customer_name']}</strong>,</p>
                                <p>Kami dengan senang hati memberitahukan bahwa pesanan Anda <strong>#$order_id</strong> telah selesai diproses.</p>
                                <p>Silahkan datang ke toko kami untuk mengambil pesanan Anda atau hubungi kami untuk pengaturan pengiriman.</p>
                                <p>Detail pesanan Anda dapat dilihat pada tautan berikut:</p>
                                <p style='text-align: center;'>
                                    <a href='http://{$_SERVER['HTTP_HOST']}/customer/order_receipt.php?order_id=$order_id' 
                                       style='background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>
                                       Lihat Detail Pesanan
                                    </a>
                                </p>
                                <p>Terima kasih telah berbelanja di Danis Printing.</p>
                                <p>Salam,<br>Tim Danis Printing</p>
                            </div>
                            <div style='background-color: #f1f1f1; padding: 10px; text-align: center; font-size: 12px;'>
                                <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                                <p>&copy; " . date('Y') . " Danis Printing. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Send email notification
                    sendHTMLEmail($to, $subject, $message);
                }
            }
            
            $_SESSION['message'] = "Status pesanan berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui status pesanan: " . $conn->error;
        }
        
        // Redirect back to this page to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Process Refund
    if (isset($_POST['process_refund'])) {
        $refund_id = $_POST['refund_id'];
        $refund_status = $_POST['refund_status'];
        $refund_notes = $_POST['refund_notes'] ?? '';
        
        // Update refund status
        $update_refund = $conn->prepare("UPDATE refunds SET status = ?, admin_notes = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_refund->bind_param("ssi", $refund_status, $refund_notes, $refund_id);
        
        if ($update_refund->execute()) {
            // If approved, update order status to refunded
            if ($refund_status === 'approved') {
                // Get order ID
                $get_order = $conn->prepare("SELECT order_id FROM refunds WHERE id = ?");
                $get_order->bind_param("i", $refund_id);
                $get_order->execute();
                $result = $get_order->get_result();
                $order_data = $result->fetch_assoc();
                
                if ($order_data) {
                    $update_order = $conn->prepare("UPDATE orders SET status = 'refunded' WHERE id = ?");
                    $update_order->bind_param("i", $order_data['order_id']);
                    $update_order->execute();
                }
            }
            
            $_SESSION['message'] = "Permintaan refund berhasil diproses!";
        } else {
            $_SESSION['error'] = "Gagal memproses refund: " . $conn->error;
        }
        
        // Redirect back to this page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Query untuk mendapatkan pesanan yang masih aktif
$orders_query = "
    SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
           p.name as product_name, p.price_per_meter 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    WHERE o.status IN ('paid', 'admin_confirmed', 'processing', 'completed', 
                      'refund_requested', 'refund_approved', 'refund_completed', 
                      'refund_rejected')
    ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);

// Get pending refund requests
$refunds_query = "
    SELECT r.*, o.id as order_id, o.total_price, c.name as customer_name, c.email, c.phone
    FROM refunds r
    JOIN orders o ON r.order_id = o.id
    JOIN customers c ON r.customer_id = c.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
";
$refunds_result = $conn->query($refunds_query);

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-warning';
        case 'paid': return 'bg-info';
        case 'admin_confirmed': return 'bg-primary';
        case 'processing': return 'bg-warning text-dark';
        case 'completed': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        case 'refund_requested': return 'bg-warning';
        case 'refund_approved': return 'bg-info';
        case 'refund_completed': return 'bg-success';
        case 'refund_rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Function to get status badge text
function getStatusText($status) {
    switch($status) {
        case 'pending': return 'MENUNGGU KONFIRMASI';
        case 'paid': return 'PEMBAYARAN SUKSES';
        case 'admin_confirmed': return 'PESANAN DIKONFIRMASI';
        case 'processing': return 'DALAM PROSES PRODUKSI';
        case 'completed': return 'PESANAN SELESAI';
        case 'cancelled': return 'PESANAN DIBATALKAN';
        case 'refund_requested': return 'PENGAJUAN REFUND';
        case 'refund_approved': return 'REFUND DISETUJUI';
        case 'refund_completed': return 'REFUND SELESAI';
        case 'refund_rejected': return 'REFUND DITOLAK';
        default: return 'STATUS TIDAK DIKETAHUI';
    }
}


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Project - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            padding: 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #1a1a1a;
            width: 16.66667%;
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                width: 25%;
            }
        }
        
        .nav-link {
            color: rgba(255,255,255,.8);
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
        }
        
        .summary-card {
            transition: all 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }

        .main-content {
            margin-left: 16.66667%;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 25%;
            }
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                width: 100%;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <h3 class="text-white text-center py-3">Print Project</h3>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="dashboard_unfinish.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white dropdown-toggle" href="#orderSubmenu" data-bs-toggle="collapse">
                                <i class="fas fa-shopping-cart me-2"></i> Pesanan
                            </a>
                            <div class="collapse" id="orderSubmenu">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="?status=new">
                                            <i class="fas fa-circle-notch me-2"></i> Pesanan Baru
                                            <?php if ($new_orders['count'] > 0): ?>
                                                <span class="badge bg-danger ms-2"><?= $new_orders['count'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="?status=confirmed">
                                            <i class="fas fa-check-circle me-2"></i> Konfirmasi
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="?status=processing">
                                            <i class="fas fa-cog me-2"></i> Dalam Proses
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="?status=completed">
                                            <i class="fas fa-check-double me-2"></i> Selesai
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="products_admin.php">
                                <i class="fas fa-tags me-2"></i> Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="customers_admin.php">
                                <i class="fas fa-users me-2"></i> Pelanggan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports_admin.php">
                                <i class="fas fa-chart-bar me-2"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link text-white" href="logout_admin.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Admin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-export me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Pesanan Hari Ini</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURRENT_DATE")->fetch_assoc();
                                            echo $today_orders['count'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Dalam Produksi</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $processing_orders_query = "SELECT COUNT(*) as count FROM orders WHERE production_status IN ('processing', 'in_progress')";
                                            $processing_orders_result = $conn->query($processing_orders_query);
                                            $processing_orders = $processing_orders_result->fetch_assoc();
                                            echo $processing_orders['count'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Pesanan Selesai</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $completed_orders_query = "SELECT COUNT(*) as count FROM orders WHERE production_status = 'completed'";
                                            $completed_orders_result = $conn->query($completed_orders_query);
                                            $completed_orders = $completed_orders_result->fetch_assoc();
                                            echo $completed_orders['count'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Permintaan Refund</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $refund_query = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
                                            $refund_result = $conn->query($refund_query);
                                            $refund_count = $refund_result->fetch_assoc();
                                            echo $refund_count['count'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-undo fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                            Pesanan Aktif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="refunds-tab" data-bs-toggle="tab" data-bs-target="#refunds" type="button">
                            Permintaan Refund <span class="badge bg-danger"><?= $refund_count['count'] ?></span>
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="myTabContent">
                    <!-- Orders Tab -->
                    <div class="tab-pane fade show active" id="orders" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Pesanan Aktif</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>No. Pesanan</th>
                                                <th>Pelanggan</th>
                                                <th>Produk</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Status Produksi</th>
                                                <th>Tanggal</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($orders_result->num_rows > 0): ?>
                                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $order['id'] ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($order['customer_name']) ?>
                                                        <div class="small text-muted"><?= $order['customer_phone'] ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                                    <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge <?= getStatusBadgeClass($order['status']) ?>">
                                                            <?= getStatusText($order['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= getStatusBadgeClass($order['production_status']) ?>">
                                                            <?= getStatusText($order['production_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?= $order['id'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="order_detail_admin.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Order Update Modal -->
                                                <div class="modal fade" id="orderModal<?= $order['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Status Pesanan #<?= $order['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status Pembayaran</label>
                                                                        <select name="status" class="form-select">
                                                                            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Pembayaran Sukses</option>
                                                                            <option value="admin_confirmed" <?= $order['status'] === 'admin_confirmed' ? 'selected' : '' ?>>Dikonfirmasi Admin</option>
                                                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                                                            <?php if ($order['status'] === 'refund_requested'): ?>
                                                                            <option value="refund_requested" selected>Refund Diajukan</option>
                                                                            <?php endif; ?>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status Produksi</label>
                                                                        <select class="form-select" name="production_status">
                                                            <option value="pending" <?= $order['production_status'] === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                                            <option value="confirmed" <?= $order['production_status'] === 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                                            <option value="processing" <?= $order['production_status'] === 'processing' ? 'selected' : '' ?>>Dalam Proses Produksi</option>
                                                            <option value="completed" <?= $order['production_status'] === 'completed' ? 'selected' : '' ?>>Selesai</option>
                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Catatan Admin</label>
                                                                        <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($order['admin_notes'] ?? '') ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Tidak ada pesanan aktif.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Refunds Tab -->
                    <div class="tab-pane fade" id="refunds" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Permintaan Refund</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>No. Refund</th>
                                                <th>No. Pesanan</th>
                                                <th>Pelanggan</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Catatan</th>
                                                <th>Tanggal</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($refunds_result->num_rows > 0): ?>
                                                <?php while ($refund = $refunds_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $refund['id'] ?></td>
                                                    <td><?= $refund['order_id'] ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($refund['customer_name']) ?>
                                                        <div class="small text-muted"><?= $refund['phone'] ?></div>
                                                    </td>
                                                    <td>Rp <?= number_format($refund['total_price'], 0, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge <?= getStatusBadgeClass($refund['status']) ?>">
                                                            <?= getStatusText($refund['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($refund['admin_notes'] ?? '') ?></td>
                                                    <td><?= date('d/m/Y', strtotime($refund['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#refundModal<?= $refund['id'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <!-- Refund Modal -->
                                                <div class="modal fade" id="refundModal<?= $refund['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Proses Refund #<?= $refund['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="refund_id" value="<?= $refund['id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status Refund</label>
                                                                        <select name="refund_status" class="form-select">
                                                                            <option value="pending" <?= $refund['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                            <option value="approved" <?= $refund['status'] === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                                                                            <option value="rejected" <?= $refund['status'] === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Catatan Admin</label>
                                                                        <textarea name="refund_notes" class="form-control" rows="3"><?= htmlspecialchars($refund['admin_notes'] ?? '') ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="process_refund" class="btn btn-primary">Proses Refund</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Tidak ada permintaan refund.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function sendNotification(orderId) {
    // Tampilkan loading state
    const button = document.querySelector(`button[data-order-id="${orderId}"]`);
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    button.disabled = true;

    // Kirim request ke endpoint
    fetch('php/send_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order_id: orderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Tampilkan pesan sukses
            alert('Notifikasi berhasil dikirim!');
            button.innerHTML = '<i class="fas fa-check"></i> Terkirim';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
        } else {
            // Tampilkan pesan error
            alert('Gagal mengirim notifikasi: ' + data.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim notifikasi');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
                <script>
                    // Auto close alert setelah 5 detik
                    setTimeout(() => {
                        const alerts = document.querySelectorAll('.alert');
                        alerts.forEach(alert => {
                            new bootstrap.Alert(alert).close();
                        });
                    }, 5000);
                </script>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Dashboard Statistics
$stats = array();

// Total Pendapatan
$revenue_query = "SELECT SUM(total_price) as total_revenue FROM orders WHERE status IN ('paid', 'completed', 'admin_confirmed', 'processing')";
$revenue_result = $conn->query($revenue_query);
$stats['total_revenue'] = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Total Pesanan
$orders_query = "SELECT 
    COUNT(CASE WHEN status = 'paid' AND production_status = 'pending' THEN 1 END) as new_orders,
    COUNT(CASE WHEN status = 'admin_confirmed' AND production_status = 'confirmed' THEN 1 END) as confirmed_orders,
    COUNT(CASE WHEN production_status = 'processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN production_status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'refund_requested' THEN 1 END) as refund_requests
FROM orders";
$orders_result = $conn->query($orders_query);
$stats['orders'] = $orders_result->fetch_assoc();

// Query untuk pesanan berdasarkan status
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$base_query = "SELECT o.*, c.name as customer_name, c.email as customer_email, 
               p.name as product_name, p.price_per_meter 
               FROM orders o
               JOIN customers c ON o.customer_id = c.id
               JOIN products p ON o.product_id = p.id
               WHERE 1=1";

// Filter berdasarkan status
switch($status_filter) {
    case 'new':
        $base_query .= " AND o.status = 'paid' AND o.production_status = 'pending'";
        break;
    case 'confirmed':
        $base_query .= " AND o.status = 'admin_confirmed'";
        break;
    case 'processing':
        $base_query .= " AND o.production_status = 'processing'";
        break;
    case 'completed':
        $base_query .= " AND o.production_status = 'completed'";
        break;
    case 'refund':
        $base_query .= " AND o.status = 'refund_requested'";
        break;
}

// Tambahkan pencarian
if (!empty($search_query)) {
    $base_query .= " AND (o.id LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
}

$base_query .= " ORDER BY o.created_at DESC";

// Prepare dan execute query
$stmt = $conn->prepare($base_query);
if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Print Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Print Project</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-export me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <!-- Daftar Pesanan -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Pesanan</h6>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Cari pesanan...">
                            <select class="form-select form-select-sm" style="width: auto;">
                                <option value="">Semua Status</option>
                                <option value="paid">Pembayaran Sukses</option>
                                <option value="processing">Dalam Proses</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan</th>
                                        <th>Produk</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($order['customer_name']) ?><br>
                                            <small class="text-muted"><?= $order['customer_email'] ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($order['product_name']) ?><br>
                                            <small class="text-muted"><?= $order['width'] ?>x<?= $order['height'] ?> meter</small>
                                        </td>
                                        <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($order['status']) {
                                                case 'paid': $status_class = 'bg-warning text-dark'; break;
                                                case 'admin_confirmed': $status_class = 'bg-info'; break;
                                                case 'processing': $status_class = 'bg-primary'; break;
                                                case 'completed': $status_class = 'bg-success'; break;
                                                case 'refund_requested': $status_class = 'bg-danger'; break;
                                            }
                                            ?>
                                            <span class="status-badge <?= $status_class ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="window.location.href='order_detail.php?id=<?= $order['id'] ?>'">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateStatusModal<?= $order['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($order['status'] == 'completed'): ?>
                                                    <button type="button" class="btn btn-primary btn-sm" data-order-id="<?= $order['id'] ?>" onclick="sendNotification(<?= $order['id'] ?>)">
                                                        <i class="fas fa-bell"></i> Kirim Notif
                                                    </button>
                                                <?php endif; ?>
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
    </div>

    <!-- Status Update Modals -->
    <?php foreach ($orders as $order): ?>
    <div class="modal fade" id="updateStatusModal<?= $order['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Status Pesanan #<?= $order['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Status Baru</label>
                            <select name="status" class="form-select" required>
                                <option value="">Pilih Status</option>
                                <?php if ($order['status'] == 'paid'): ?>
                                    <option value="admin_confirmed">Dikonfirmasi Admin</option>
                                    <option value="processing">Proses Produksi</option>
                                <?php elseif ($order['status'] == 'admin_confirmed'): ?>
                                    <option value="processing">Proses Produksi</option>
                                <?php elseif ($order['status'] == 'processing'): ?>
                                    <option value="completed">Selesai</option>
                                <?php endif; ?>
                                <option value="cancelled">Batalkan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($order['admin_notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function sendNotification(orderId) {
    // Tampilkan loading state
    const button = document.querySelector(`button[data-order-id="${orderId}"]`);
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    button.disabled = true;

    // Kirim request ke endpoint
    fetch('php/send_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order_id: orderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Tampilkan pesan sukses
            alert('Notifikasi berhasil dikirim!');
            button.innerHTML = '<i class="fas fa-check"></i> Terkirim';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
        } else {
            // Tampilkan pesan error
            alert('Gagal mengirim notifikasi: ' + data.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim notifikasi');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
</body>
</html>