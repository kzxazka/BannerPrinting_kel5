<?php
// File: php/midtrans-notification.php
include(__DIR__ . '/../config.php');
require_once 'midtrans-php-master/Midtrans.php';

\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$serverKey = 'SB-Mid-server-W0IqsDgpn3f6SgIQtvi9jjYK';

$notif = new \Midtrans\Notification();

$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

// Ekstrak ID pesanan asli dari Midtrans order_id (yang sebelumnya dibuat dengan format: order_id-timestamp)
$original_order_id = explode('-', $order_id)[0];

// Siapkan query untuk mengambil ID pesanan berdasarkan midtrans_order_id
$query = "SELECT id FROM orders WHERE midtrans_order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

// Jika tidak ditemukan dengan midtrans_order_id, coba cari dengan original_order_id
if ($result->num_rows == 0) {
    $stmt->close();
    $query = "SELECT id FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $original_order_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows > 0) {
    $order_data = $result->fetch_assoc();
    $real_order_id = $order_data['id'];
    
    // Perbarui status transaksi berdasarkan notifikasi Midtrans
    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if($fraud == 'challenge') {
                // TODO: proses pesanan dengan status 'challenge'
                $status = 'pending';
            } else {
                $status = 'paid';
            }
        }
    } else if ($transaction == 'settlement') {
        $status = 'paid';
    } else if ($transaction == 'pending') {
        $status = 'pending';
    } else if ($transaction == 'deny') {
        $status = 'cancelled';
    } else if ($transaction == 'expire') {
        $status = 'expired';
    } else if ($transaction == 'cancel') {
        $status = 'cancelled';
    }

    // Update status pesanan di database
    $update_query = "UPDATE orders SET status = ?, payment_method = ?, transaction_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $status, $type, $notif->transaction_id, $real_order_id);
    $update_stmt->execute();
    
    // Tambahkan log transaksi untuk debugging
    $log_query = "INSERT INTO payment_logs (order_id, midtrans_order_id, status, payment_type, raw_response) VALUES (?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $raw_response = json_encode($notif);
    $log_stmt->bind_param("issss", $real_order_id, $order_id, $status, $type, $raw_response);
    $log_stmt->execute();
    
    // Berikan respons OK ke Midtrans
    header('HTTP/1.1 200 OK');
    echo "OK";
} else {
    // Order ID tidak ditemukan di database
    header('HTTP/1.1 404 Not Found');
    echo "Order ID not found";
    
    // Log error
    $error_log_query = "INSERT INTO payment_logs (midtrans_order_id, status, raw_response) VALUES (?, 'error', ?)";
    $error_log_stmt = $conn->prepare($error_log_query);
    $error_message = "Order ID tidak ditemukan: " . $order_id;
    $error_log_stmt->bind_param("ss", $order_id, $error_message);
    $error_log_stmt->execute();
}