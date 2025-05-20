<?php
require_once 'NotificationManager.php';

// Inisialisasi NotificationManager
$notificationManager = new DanisPrinting\Notifications\NotificationManager();

// Fungsi wrapper untuk kompatibilitas dengan kode lama
function sendOrderCompletionEmail($customerEmail, $customerName, $orderId, $orderDetails) {
    global $notificationManager;
    return $notificationManager->sendOrderStatusUpdateEmail(
        $customerEmail,
        $customerName,
        $orderId,
        'completed',
        $orderDetails
    );
}

function sendOrderConfirmationEmail($customerEmail, $customerName, $orderId, $orderDetails) {
    global $notificationManager;
    return $notificationManager->sendOrderConfirmationEmail(
        $customerEmail,
        $customerName,
        $orderId,
        $orderDetails
    );
}
?>
