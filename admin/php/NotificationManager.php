<?php
namespace DanisPrinting\Notifications;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Konfigurasi default SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'email.danis.printing@gmail.com';
        $this->mailer->Password = 'abcd efgh ijkl mnop'; // Ganti dengan App Password Gmail
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom($this->mailer->Username, 'Danis Printing');
    }
    
    public function sendOrderConfirmationEmail($customerEmail, $customerName, $orderId, $orderDetails) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($customerEmail, $customerName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Konfirmasi Pesanan #{$orderId}";
            
            $trackingLink = "http://localhost/printproject/user/cek-pesanan.php?order_id=" . $orderId;
            
            $this->mailer->Body = $this->getOrderConfirmationTemplate(
                $customerName,
                $orderId,
                $orderDetails,
                $trackingLink
            );
            
            $this->mailer->send();
            return ['success' => true, 'message' => 'Email konfirmasi berhasil dikirim'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal mengirim email: ' . $this->mailer->ErrorInfo];
        }
    }
    
    public function sendOrderStatusUpdateEmail($customerEmail, $customerName, $orderId, $status, $orderDetails) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($customerEmail, $customerName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Update Status Pesanan #{$orderId}";
            
            $trackingLink = "http://localhost/printproject/user/cek-pesanan.php?order_id=" . $orderId;
            
            $this->mailer->Body = $this->getStatusUpdateTemplate(
                $customerName,
                $orderId,
                $status,
                $orderDetails,
                $trackingLink
            );
            
            $this->mailer->send();
            return ['success' => true, 'message' => 'Email update status berhasil dikirim'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal mengirim email: ' . $this->mailer->ErrorInfo];
        }
    }
    
    public function sendWhatsAppOrderConfirmation($phone, $orderId, $orderDetails) {
        $message = "Halo! Pesanan Anda #{$orderId} telah dikonfirmasi.\n\n";
        $message .= "Detail Pesanan:\n";
        $message .= "Produk: {$orderDetails['product_name']}\n";
        $message .= "Ukuran: {$orderDetails['width']}m x {$orderDetails['height']}m\n";
        $message .= "Total: Rp " . number_format($orderDetails['total_price'], 0, ',', '.');
        
        $whatsappLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text=" . urlencode($message);
        
        return [
            'success' => true,
            'whatsapp_link' => $whatsappLink,
            'message' => 'Link WhatsApp berhasil dibuat'
        ];
    }
    
    public function sendWhatsAppOrderStatusUpdate($phone, $orderId, $status, $orderDetails) {
        $message = "Halo! Status pesanan Anda #{$orderId} telah diperbarui menjadi '{$status}'.\n\n";
        $message .= "Detail Pesanan:\n";
        $message .= "Produk: {$orderDetails['product_name']}\n";
        $message .= "Ukuran: {$orderDetails['width']}m x {$orderDetails['height']}m\n";
        $message .= "Total: Rp " . number_format($orderDetails['total_price'], 0, ',', '.');
        
        $whatsappLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text=" . urlencode($message);
        
        return [
            'success' => true,
            'whatsapp_link' => $whatsappLink,
            'message' => 'Link WhatsApp berhasil dibuat'
        ];
    }
    
    private function getOrderConfirmationTemplate($customerName, $orderId, $orderDetails, $trackingLink) {
        return "
            <h2>Konfirmasi Pesanan</h2>
            <p>Halo {$customerName},</p>
            <p>Terima kasih telah melakukan pemesanan di Danis Printing.</p>
            <p>Detail pesanan Anda:</p>
            <ul>
                <li>Nomor Pesanan: #{$orderId}</li>
                <li>Produk: {$orderDetails['product_name']}</li>
                <li>Ukuran: {$orderDetails['width']}m x {$orderDetails['height']}m</li>
                <li>Total: Rp " . number_format($orderDetails['total_price'], 0, ',', '.') . "</li>
            </ul>
            <p>Silakan klik link berikut untuk melacak pesanan Anda:</p>
            <a href='{$trackingLink}' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Lacak Pesanan</a>
            <p>Terima kasih telah mempercayai Danis Printing!</p>
        ";
    }
    
    private function getStatusUpdateTemplate($customerName, $orderId, $status, $orderDetails, $trackingLink) {
        return "
            <h2>Update Status Pesanan</h2>
            <p>Halo {$customerName},</p>
            <p>Status pesanan Anda #{$orderId} telah diperbarui.</p>
            <p>Status saat ini: <strong>{$status}</strong></p>
            <p>Detail pesanan:</p>
            <ul>
                <li>Produk: {$orderDetails['product_name']}</li>
                <li>Ukuran: {$orderDetails['width']}m x {$orderDetails['height']}m</li>
                <li>Total: Rp " . number_format($orderDetails['total_price'], 0, ',', '.') . "</li>
            </ul>
            <p>Silakan klik link berikut untuk melihat detail pesanan Anda:</p>
            <a href='{$trackingLink}' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Lihat Pesanan</a>
            <p>Terima kasih telah menggunakan layanan Danis Printing!</p>
        ";
    }
}