<?php
include(__DIR__ . '/../config.php');

// Start session
session_start();

// Check if order_id is provided in URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$transaction_id = isset($_GET['transaction_id']) ? htmlspecialchars($_GET['transaction_id']) : null;

if (!$order_id) {
    die("Order ID tidak ditemukan.");
}

// Update transaction_id if provided (from Midtrans callback)
if ($transaction_id) {
    $update_transaction = $conn->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
    $update_transaction->bind_param("si", $transaction_id, $order_id);
    $update_transaction->execute();
}

// Get order details
$query = "
    SELECT o.*, p.name as product_name, c.name as customer_name, c.phone, c.email, 
           c.address, c.bank_account, c.bank_name, c.account_holder_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order tidak ditemukan.");
}

// Check order status and update if not already paid
if ($order['status'] !== 'paid' && !empty($order['midtrans_order_id'])) {
    // Check the payment status with Midtrans 
    require_once __DIR__ . '/../php/midtrans-php-master/Midtrans.php';
    
    \Midtrans\Config::$serverKey = 'SB-Mid-server-W0IqsDgpn3f6SgIQtvi9jjYK';
    \Midtrans\Config::$isProduction = false;
    
    try {
        // Use transaction_id if available, otherwise use midtrans_order_id
        $transaction_id_to_check = $order['transaction_id'] ?? $order['midtrans_order_id'];
        
        if ($transaction_id_to_check) {
            $status_response = (object)\Midtrans\Transaction::status($transaction_id_to_check);
            
            // Log payment response
            $log_stmt = $conn->prepare("INSERT INTO payment_logs (order_id, midtrans_order_id, status, payment_type, raw_response) VALUES (?, ?, ?, ?, ?)");
            $status_str = $status_response->transaction_status ?? 'unknown';
            $payment_type = $status_response->payment_type ?? null;
            $raw_response = json_encode($status_response);
            $log_stmt->bind_param("issss", $order_id, $transaction_id_to_check, $status_str, $payment_type, $raw_response);
            $log_stmt->execute();
            
            // If payment is successful, update order status
            if (isset($status_response->transaction_status) && 
                ($status_response->transaction_status == 'settlement' || $status_response->transaction_status == 'capture')) {
                $update_stmt = $conn->prepare("
                    UPDATE orders 
                    SET status = 'paid', 
                        payment_method = ?,
                        status_updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $payment_method = $status_response->payment_type ?? 'midtrans';
                $update_stmt->bind_param("si", $payment_method, $order_id);
                $update_stmt->execute();
                
                // Refresh order data
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
            }
        }
    } catch (Exception $e) {
        // Log errors but don't stop execution
        error_log("Error checking Midtrans status: " . $e->getMessage());
    }

}

function canCancelOrder($order) {
    // Jika status produksi sudah processing atau completed, tidak bisa refund
    if ($order['production_status'] === 'processing' || 
        $order['production_status'] === 'completed') {
        return false;
    }
    
    // Jika pesanan belum dibayar atau status bukan admin_confirmed, tidak bisa refund
    if ($order['status'] !== 'admin_confirmed') {
        return false;
    }
    
    // Jika pesanan sudah dikonfirmasi admin, cek waktu untuk refund
    if ($order['status_updated_at']) {
        $confirmation_time = strtotime($order['status_updated_at']);
        $current_time = time();
        $time_diff_minutes = ($current_time - $confirmation_time) / 60;
        
        // Izinkan refund hanya dalam 30 menit setelah konfirmasi admin
        return $time_diff_minutes < 30;
    }
    
    return false;
}

// Calculate refund availability
// Sebelum bagian HTML, tambahkan logika untuk $refund_available
$refund_available = (
    ($order['status'] === 'paid' || $order['status'] === 'confirmed' || $order['status'] === 'admin_confirmed') &&
    ($order['production_status'] === 'pending' || $order['production_status'] === 'confirmed') &&
    !in_array($order['status'], ['cancelled', 'refunded', 'refund_requested'])
);

// Handle refund request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refund_submit']) && $refund_available) {
    // Validate refund form
    $refund_reason = isset($_POST['refund_reason']) ? htmlspecialchars($_POST['refund_reason']) : '';
    $bank_account = isset($_POST['bank_account']) ? htmlspecialchars($_POST['bank_account']) : '';
    $bank_name = isset($_POST['bank_name']) ? htmlspecialchars($_POST['bank_name']) : '';
    $account_holder_name = isset($_POST['account_holder_name']) ? htmlspecialchars($_POST['account_holder_name']) : '';
    
    // Basic validation
    if (empty($refund_reason) || empty($bank_account) || empty($bank_name) || empty($account_holder_name)) {
        $_SESSION['error'] = "Semua kolom harus diisi.";
    } else {
        $refund_stmt = $conn->prepare("
            INSERT INTO refunds (
                order_id, customer_id, refund_amount, 
                refund_reason, bank_account, bank_name, 
                account_holder_name, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $refund_stmt->bind_param(
            "iidssss", 
            $order_id, 
            $order['customer_id'], 
            $order['total_price'], 
            $refund_reason, 
            $bank_account, 
            $bank_name, 
            $account_holder_name
        );

        if ($refund_stmt->execute()) {
            // Update order status to refund requested
            $update_order_stmt = $conn->prepare("UPDATE orders SET status = 'refund_requested' WHERE id = ?");
            $update_order_stmt->bind_param("i", $order_id);
            $update_order_stmt->execute();

            // Redirect or show success message
            $_SESSION['message'] = "Permintaan refund berhasil diajukan.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?order_id=" . $order_id);
            exit();
        } else {
            $_SESSION['error'] = "Terjadi kesalahan saat mengajukan refund. Silakan coba lagi.";
        }
    }
}

// Function untuk mendapatkan status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-warning';
        case 'paid': return 'bg-info';
        case 'admin_confirmed':
        case 'confirmed': return 'bg-primary';
        case 'processing':
        case 'in_progress': return 'bg-warning text-dark';
        case 'completed': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        case 'refund_requested': return 'bg-warning';
        case 'refund_approved': return 'bg-info';
        case 'refund_completed': return 'bg-success';
        case 'refund_rejected': return 'bg-danger';
        case 'refunded': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Function untuk mendapatkan status text
function getStatusText($status) {
    switch($status) {
        case 'pending': return 'MENUNGGU PEMBAYARAN';
        case 'paid': return 'PEMBAYARAN SUKSES';
        case 'admin_confirmed':
        case 'confirmed': return 'PESANAN DIKONFIRMASI';
        case 'processing':
        case 'in_progress': return 'DALAM PROSES PRODUKSI';
        case 'completed': return 'PESANAN SELESAI';
        case 'cancelled': return 'PESANAN DIBATALKAN';
        case 'refund_requested': return 'PENGAJUAN REFUND';
        case 'refund_approved': return 'REFUND DISETUJUI';
        case 'refund_completed': return 'REFUND SELESAI';
        case 'refund_rejected': return 'REFUND DITOLAK';
        default: return 'STATUS TIDAK DIKETAHUI';
    }
}

// Get status badge class and text
$status_badge_class = getStatusBadgeClass($order['status']);
$status_text = getStatusText($order['status']);

// Get production status badge class and text
$production_badge_class = getStatusBadgeClass($order['production_status']);
$production_text = getStatusText($order['production_status']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #<?= $order_id ?> - Danis Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .receipt-header {
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .receipt-company {
            font-size: 24px;
            font-weight: bold;
        }
        .receipt-footer {
            border-top: 1px dashed #dee2e6;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 14px;
        }
        .receipt-info {
            font-size: 14px;
        }
        .receipt-items {
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .dashed-line {
            border-bottom: 1px dashed #dee2e6;
            margin: 15px 0;
        }
        .receipt-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .badge-large {
            font-size: 1rem;
            padding: 8px 12px;
        }
        .tracking-step {
            position: relative;
            padding-bottom: 20px;
        }
        .tracking-step:before {
            content: '';
            position: absolute;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
            left: 11px;
            top: 5px;
        }
        .tracking-step:last-child:before {
            display: none;
        }
        .tracking-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            position: relative;
        }
        .tracking-content {
            padding-left: 15px;
            display: inline-block;
        }
        .tracking-date {
            font-size: 12px;
            color: #6c757d;
        }
        .tracking-text {
            font-weight: 500;
        }
        .active-step .tracking-dot {
            background-color: #198754;
            color: white;
        }
        .inactive-step .tracking-dot {
            background-color: #dee2e6;
            color: #6c757d;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php include('./view/header.php'); ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success mb-4 no-print">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
            <?php endif; ?>
            
            <div class="receipt-card">
                <!-- Receipt Header -->
                <div class="receipt-header text-center">
                    <div class="receipt-company mb-2">DANIS PRINTING</div>
                    <div class="receipt-info">Jl. Danis Printing No. 123, Indonesia</div>
                    <div class="receipt-info">Telp: (021) 1234-5678</div>
                    <div class="receipt-info">Email: info@danisprinting.com</div>
                </div>
                
                <!-- Order Status Banner -->
                <div class="text-center mb-4">
                    <div class="<?= $status_badge_class ?> text-white py-2 rounded mb-3">
                        <i class="<?= $order['status'] === 'paid' || $order['status'] === 'admin_confirmed' || $order['status'] === 'confirmed' || $order['status'] === 'processing' || $order['status'] === 'in_progress' || $order['status'] === 'completed' ? 'fas fa-check-circle' : ($order['status'] === 'cancelled' ? 'fas fa-times-circle' : 'fas fa-clock') ?> me-2"></i> 
                        <?= $status_text ?>
                    </div>
                </div>
                
                <!-- Order Info -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="fw-bold">No. Pesanan:</div>
                        <div>#<?= $order_id ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="fw-bold">Tanggal:</div>
                        <div><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <i class="fas fa-user me-2"></i> Informasi Pelanggan
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div><strong>Nama:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                                <div><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>No. Telepon:</strong> <?= htmlspecialchars($order['phone']) ?></div>
                                <?php if ($order['address']): ?>
                                <div><strong>Alamat:</strong> <?= htmlspecialchars($order['address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="receipt-items">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart me-2"></i> Detail Produk</h5>
                    
                    <div class="row fw-bold mb-2 d-none d-md-flex">
                        <div class="col-md-6">Produk</div>
                        <div class="col-md-3 text-center">Ukuran</div>
                        <div class="col-md-3 text-end">Harga</div>
                    </div>
                    
                    <div class="row mb-3 p-2 bg-light rounded">
                        <div class="col-md-6">
                            <div class="fw-bold d-md-none">Produk:</div>
                            <?= htmlspecialchars($order['product_name']) ?>
                            <div class="small text-muted">Jenis Desain: <?= ucfirst($order['design_type']) ?></div>
                            
                            <!-- Preview Design -->
                            <?php if ($order['design_type'] === 'upload' && $order['design_path']): ?>
                            <div class="mt-3">
                                <h6>Preview Design:</h6>
                                <?php
                                // Mengubah path untuk file upload
                                $display_path = $order['design_path'];
                                if (strpos($display_path, 'uploads/') !== false) {
                                    // Menggunakan path relatif dari folder user
                                    $display_path = 'uploads/' . basename($display_path);
                                }
                                ?>
                                <img src="<?= $display_path ?>" 
                                     class="img-fluid rounded" 
                                     alt="Design Preview"
                                     style="max-width: 200px;">
                            </div>
                            <?php elseif ($order['design_type'] === 'ai'): ?>
                            <div class="mt-3">
                                <h6>Deskripsi Design AI:</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Kategori:</strong> <?= htmlspecialchars($order['prompt_category']) ?></p>
                                        <p class="mb-0"><strong>Deskripsi:</strong> <?= htmlspecialchars($order['prompt_description']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-md-center">
                            <div class="fw-bold d-md-none">Ukuran:</div>
                            <?= $order['width'] ?>m x <?= $order['height'] ?>m
                        </div>
                        <div class="col-md-3 text-md-end">
                            <div class="fw-bold d-md-none">Harga:</div>
                            Rp <?= number_format($order['total_price'] - ($order['delivery_fee'] ?? 0), 0, ',', '.') ?>
                        </div>
                    </div>
                    
                    <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                    <div class="row mb-2">
                        <div class="col-md-9 text-md-end">Biaya Pengiriman:</div>
                        <div class="col-md-3 text-md-end">Rp <?= number_format($order['delivery_fee'], 0, ',', '.') ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="dashed-line"></div>
                    
                    <div class="row fw-bold">
                        <div class="col-md-9 text-md-end">Total:</div>
                        <div class="col-md-3 text-md-end">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i> Informasi Pembayaran</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div><strong>Status Pembayaran:</strong> 
                                <span class="badge <?= $status_badge_class ?>">
                                    <?= $status_text ?>
                                </span>
                            </div>
                            <?php if (in_array($order['status'], ['refund_requested', 'refund_approved', 'refund_completed', 'refund_rejected'])): ?>
                            <div class="mt-2">
                                <strong>Status Refund:</strong>
                                <?php if ($order['status'] === 'refund_completed'): ?>
                                    <div class="text-success">Refund telah selesai diproses</div>
                                <?php elseif ($order['status'] === 'refund_rejected'): ?>
                                    <div class="text-danger">Pengajuan refund ditolak</div>
                                <?php else: ?>
                                    <div class="text-warning">Pengajuan refund sedang diproses</div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($order['payment_method'])): ?>
                            <div><strong>Metode Pembayaran:</strong> <?= ucfirst($order['payment_method']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($order['transaction_id']): ?>
                            <div><strong>ID Transaksi:</strong> <?= $order['transaction_id'] ?></div>
                            <?php endif; ?>
                            <?php if (($order['status'] === 'paid' || $order['status'] === 'admin_confirmed') && $order['status_updated_at']): ?>
                            <div><strong>Tanggal Pembayaran:</strong> <?= date('d/m/Y H:i', strtotime($order['status_updated_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Production Status -->
                <?php if ($order['status'] === 'paid' || $order['status'] === 'admin_confirmed' || $order['status'] === 'confirmed' || $order['status'] === 'processing' || $order['status'] === 'in_progress' || $order['status'] === 'completed'): ?>
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-tasks me-2"></i> Status Produksi</h5>
                    
                    <?php
                    $steps = ['pending', 'confirmed', 'processing', 'completed'];
                    $step_names = [
                        'pending' => 'Menunggu Konfirmasi',
                        'confirmed' => 'Pesanan Dikonfirmasi',
                        'processing' => 'Dalam Proses Produksi',
                        'completed' => 'Produksi Selesai'
                    ];
                    
                    // Convert in_progress to processing for compatibility
                    $production_status = $order['production_status'];
                    if ($production_status === 'in_progress') {
                        $production_status = 'processing';
                    }
                    
                    $current_step = array_search($production_status, $steps);
                    
                    foreach ($steps as $index => $step):
                        $is_active = $current_step !== false && $index <= $current_step;
                    ?>
                    <div class="tracking-step <?= $is_active ? 'active-step' : 'inactive-step' ?>">
                        <div class="tracking-dot <?= getStatusBadgeClass($step) ?>">
                            <?php if ($is_active): ?>
                            <i class="fas fa-check fa-sm text-white"></i>
                            <?php else: ?>
                            <i class="fas fa-circle fa-sm"></i>
                            <?php endif; ?>
                        </div>
                        <div class="tracking-content">
                            <div class="tracking-text"><?= $step_names[$step] ?></div>
                            <?php if ($is_active && $step === $production_status): ?>
                            <div class="tracking-date">
                                <?= ($order['status_updated_at']) ? date('d/m/Y H:i', strtotime($order['status_updated_at'])) : date('d/m/Y H:i') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Admin Notes if available -->
                <?php if (!empty($order['admin_notes'])): ?>
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-comment me-2"></i> Catatan Admin</h5>
                    <div class="alert alert-info">
                        <?= nl2br(htmlspecialchars($order['admin_notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Preview Desain -->
                <?php if (!empty($order['design_path'])): ?>
                <div class="mb-4">
                    <h5 class="mb-3">
                        <?php if (strpos($order['design_path'], 'generated') !== false): ?>
                            <i class="fas fa-robot me-2"></i> Preview Desain AI
                        <?php else: ?>
                            <i class="fas fa-image me-2"></i> Preview Desain Upload
                        <?php endif; ?>
                    </h5>
                    <div class="text-center">
                        <?php 
                        $display_path = $order['design_path'];
                        if (strpos($display_path, 'uploads/') !== false) {
                            $display_path = '/printproject/uploads/' . substr($display_path, strpos($display_path, 'uploads/') + 8);
                        }
                        ?>
                        <img src="<?= $display_path ?>" alt="Desain" class="img-fluid border rounded" style="max-height: 200px;">
                    </div>
                </div>
                <?php endif;?>
                
                <?php if ($refund_available): ?>
                <div class="mb-4 no-print">
                    <h5 class="mb-3"><i class="fas fa-undo me-2"></i> Pengajuan Refund</h5>
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">Alasan Refund</label>
                            <textarea class="form-control" id="refund_reason" name="refund_reason" rows="3" required></textarea>
                            <div class="invalid-feedback">Mohon isi alasan refund.</div>
                        </div>
                        <div class="mb-3">
                            <label for="bank_account" class="form-label">Nomor Rekening</label>
                            <input type="text" class="form-control" id="bank_account" name="bank_account" value="<?= htmlspecialchars($order['bank_account'] ?? '') ?>" required>
                            <div class="invalid-feedback">Mohon isi nomor rekening.</div>
                        </div>
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">Nama Bank</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= htmlspecialchars($order['bank_name'] ?? '') ?>" required>
                            <div class="invalid-feedback">Mohon isi nama bank.</div>
                        </div>
                        <div class="mb-3">
                            <label for="account_holder_name" class="form-label">Nama Pemilik Rekening</label>
                            <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" value="<?= htmlspecialchars($order['account_holder_name'] ?? '') ?>" required>
                            <div class="invalid-feedback">Mohon isi nama pemilik rekening.</div>
                        </div>
                        <button type="submit" name="refund_submit" class="btn btn-warning">
                            <i class="fas fa-undo me-2"></i>Ajukan Refund
                        </button>
                    </form>
                </div>
                <?php endif;?>

                <!-- Order Footer -->
                <div class="receipt-footer text-center">
                    Terima kasih telah berbelanja di kami!
                </div> 

                <div class="text-center mt-4 no-print">
                    <a href="riwayat-pesanan.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Cetak
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('../view/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Modal untuk ajukan refund -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Informasi Refund:</strong><br>
                        - Refund hanya dapat diajukan dalam 30 menit setelah pesanan dikonfirmasi admin<br>
                        - Biaya administrasi Rp 7.000 akan dipotong dari jumlah refund<br>
                        - Proses refund membutuhkan waktu 3-5 hari kerja setelah disetujui
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation untuk form refund
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {~
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
</body>
</html>
