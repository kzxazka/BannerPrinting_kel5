<?php
require_once '../../config.php';
require_once 'email_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
    exit;
}

try {
    // Ambil data order
    $query = "SELECT o.*, c.name as customer_name, c.email 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE o.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
        exit;
    }

    // Siapkan email
    $to = $order['email'];
    $subject = "Update Status Pesanan #" . $orderId . " - Danis Printing";
    $message = "Halo " . $order['customer_name'] . ",\n\n";
    $message .= "Pesanan Anda #" . $orderId . " telah selesai diproses.\n";
    $message .= "Silakan ambil pesanan Anda di toko kami.\n\n";
    $message .= "Terima kasih telah menggunakan jasa Danis Printing.";

    // Kirim email
    if (sendHTMLEmail($to, $subject, $message)) {
        echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil dikirim']);
    } else {
        throw new Exception("Gagal mengirim email");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>