<?php
require_once 'midtrans-php-master/Midtrans.php';
\Midtrans\Config::$serverKey = 'SB-Mid-server-W0IqsDgpn3f6SgIQtvi9jjYK';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$order_id = $_GET['order_id'];
$gross_amount = $_GET['amount'];
$product_name = $_GET['product_name'];
$customer_name = $_GET['customer_name'];
$email = $_GET['email'];
$phone = $_GET['phone'];

$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $gross_amount,
    ],
    'item_details' => [
        [
            'id' => $order_id,
            'price' => $gross_amount,
            'quantity' => 1,
            'name' => $product_name,
        ]
    ],
    'customer_details' => [
        'first_name' => $customer_name,
        'email' => $email,
        'phone' => $phone,
    ],
    'callbacks' => [
    'finish' => 'http://localhost/printproject/user/payment_success.php',
    'unfinish' => 'http://localhost/printproject/user/payment_pending.php',
    'error' => 'http://localhost/printproject/user/payment_error.php',
]

];

$snapToken = \Midtrans\Snap::getSnapToken($params);
echo $snapToken;

?>