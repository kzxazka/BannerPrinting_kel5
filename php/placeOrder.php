<?php
/*Install Midtrans PHP Library (https://github.com/Midtrans/midtrans-php)
composer require midtrans/midtrans-php
                              
Alternatively, if you are not using **Composer**, you can download midtrans-php library 
(https://github.com/Midtrans/midtrans-php/archive/master.zip), and then require 
the file manually.   

require_once dirname(__FILE__) . '/pathofproject/Midtrans.php'; */
require_once dirname(__FILE__) . '/midtrans-php-master/Midtrans.php'; 

//SAMPLE REQUEST START HERE

// Set your Merchant Server Key
\Midtrans\Config::$serverKey = 'SB-Mid-server-W0IqsDgpn3f6SgIQtvi9jjYK';
// Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
\Midtrans\Config::$isProduction = false;
// Set sanitization on (default)
\Midtrans\Config::$isSanitized = true;
// Set 3DS transaction for credit card to true
\Midtrans\Config::$is3ds = true;

$order_id = "ORDER-". time(); // Generate unique order ID
$params = array(
    'transaction_details' => array(
        'order_id' => $order_id(),
        'gross_amount' => $_POST['harga'], // Total amount
    ),
    'item_details' => array(
        array(
            'id' => $_POST['id_produk'],
            'price' => $_POST['harga'],
            'quantity' => 1,
            'name' => $_POST['nama_produk'],
        ),
    ),
    'customer_details' => array(
        'first_name' => $_POST['nama'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
    ),
);

$snapToken = \Midtrans\Snap::getSnapToken($params);
echo $snapToken;

// Simpan order_id dan snapToken ke sesi, lalu redirect ke payment
session_start();
$_SESSION['snap_token'] = $snapToken;
$_SESSION['order_id'] = $_POST['order_id']; // Pastikan order_id ini konsisten

header("Location: payment.php?order_id=" . $_POST['order_id']);
exit;

?>
