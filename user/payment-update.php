<?php
include(__DIR__ . '/../config.php');

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    die("Order ID tidak ditemukan.");
}

//function untuk check if order can be cancelled
function canCancelOrder($order) {
    // Cek apakah status order adalah 'pending'
    if ($order['status'] === 'paid') {
        // if order selesai di bayar
        if($order['status_updated_at']) {
            $confirmation_time = strtotime($order['status_updated_at']);
            $current_time = time();
            $time_diff_hours = ($current_time - $confirmation_time) / 3600; 

            return $order['production_status'] === 'dikonfirmasi' && $time_diff_hours < 12;
        }
        return true;
    }

    //order can be cancelled if its not paid yet
    return $order['status'] !== 'paid';
}

// Ambil detail order + customer
$query = "
    SELECT o.*, p.name as product_name, c.name as customer_name, c.phone, c.email, c.address, 
           c.bank_account, c.bank_name, c.account_holder_name
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

// handle refund request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refund_submit'])) {
    // validate refund form
    $refund_reason = $_POST['refund_reason']?? '';
    $bank_account = $_POST['bank_account']?? '';
    $bank_name = $_POST['bank_name']?? '';
    $account_holder_name = $_POST['account_holder_name']?? '';

    $refund_stmt = $conn->prepare("
        INSERT INTO refunds (
            order_id, customer_id, refund_amount, 
            refund_reason, bank_account, bank_name, 
            account_holder_name, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $refund_stmt->bind_param(
        "iidsssss", 
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
    }
}
// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if (canCancelOrder($order)) {
        // Update order status to cancelled
        $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->bind_param("i", $order_id);
        
        if ($cancel_stmt->execute()) {
            // Redirect to cancellation confirmation page
            header("Location: order_cancelled.php?order_id=" . $order_id);
            exit();
        }
    } else {
        $_SESSION['error'] = "Pesanan tidak dapat dibatalkan pada tahap ini.";
    }
}

// Check if order is already paid
if ($order['status'] === 'paid') {
    header("Location: order_receipt.php?order_id=" . $order_id);
    exit();
}

// Check if order is cancelled
if ($order['status'] === 'cancelled') {
    header("Location: order_cancelled.php?order_id=" . $order_id);
    exit();
}

session_start();

//snaptoken
require_once __DIR__ . '/../php/midtrans-php-master/Midtrans.php';

\Midtrans\Config::$serverKey = 'SB-Mid-server-W0IqsDgpn3f6SgIQtvi9jjYK';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Membuat unique ID dengan timestamp untuk menghindari duplikasi order_id
$unique_order_id = $order_id . '-' . time();

// Update order_id midtrans di database
$update_midtrans_id_query = "UPDATE orders SET midtrans_order_id = ? WHERE id = ?";
$stmt_update = $conn->prepare($update_midtrans_id_query);
$stmt_update->bind_param("si", $unique_order_id, $order_id);
$stmt_update->execute();

// Ambil harga, id produk, dan info customer dari $order yang sudah kamu ambil dari DB
$params = array(
    'transaction_details' => array(
        'order_id' => $unique_order_id,
        'gross_amount' => (int)$order['total_price'],
    ),
    'item_details' => array(
        array(
            'id' => $order['product_id'],
            'price' => (int)$order['total_price'],
            'quantity' => 1,
            'name' => $order['product_name'],
        ),
    ),
    'customer_details' => array(
        'first_name' => $order['customer_name'],
        'email' => $order['email'],
        'phone' => $order['phone'],
    ),
    'callbacks' => array(
        'finish' => 'http://' . $_SERVER['HTTP_HOST'] . '/printproject/user/order_receipt.php?order_id=' . $order_id,
        'error' => 'http://' . $_SERVER['HTTP_HOST'] . '/printproject/user/payment-error.php?order_id=' . $order_id . '&status=error',
        'unfinish' => 'http://' . $_SERVER['HTTP_HOST'] . '/printproject/user/payment-pending.php?order_id=' . $order_id . '&status=unfinish',
    )
);

try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
} catch (Exception $e) {
    // Tampilkan pesan error yang lebih user-friendly
    die("Error saat memproses pembayaran: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Danis Printing</title>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-ORZOctTLqAW56siD"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include('../view/header.php'); ?>

<div class="container mt-5">
    <div class="progress" style="height: 8px;">
        <div class="progress-bar bg-primary" style="width: 100%"></div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <div class="text-center mx-3">
            <div class="badge bg-primary rounded-circle p-3">1</div>
            <p class="mt-2">Produk & Desain</p>
        </div>
        <div class="text-center mx-3">
            <div class="badge bg-primary rounded-circle p-3">2</div>
            <p class="mt-2">Pembayaran</p>
        </div>
    </div>

    <?php
    if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-4 mt-4">
        <h2 class="mb-4">Ringkasan Pesanan</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
        <div class="alert alert-danger">
            Pembayaran gagal. Silahkan coba lagi.
        </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'unfinish'): ?>
        <div class="alert alert-warning">
            Pembayaran belum selesai. Anda dapat mencoba kembali.
        </div>
        <?php endif; ?>
        
        <ul class="list-group mb-4">
            <li class="list-group-item"><strong>Nama Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></li>
            <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></li>
            <li class="list-group-item"><strong>Alamat:</strong> <?= htmlspecialchars($order['address']) ?></li>
            <li class="list-group-item"><strong>Nomor HP:</strong> <?= htmlspecialchars($order['phone']) ?></li>
            <li class="list-group-item"><strong>Produk:</strong> <?= htmlspecialchars($order['product_name']) ?></li>
            <li class="list-group-item"><strong>Ukuran:</strong> <?= $order['width'] ?>m x <?= $order['height'] ?>m</li>
            <li class="list-group-item"><strong>Jenis Desain:</strong> <?= ucfirst($order['design_type']) ?></li>
            <li class="list-group-item"><strong>Pilihan Pengiriman:</strong> 
                <?= $order['delivery_option'] === 'delivery' ? 'diantar (Rp.7000)' : 'Ambil di Toko (Gratis)' ?> </li>
            <li class="list-group-item"><strong>Total Harga:</strong> Rp <?= number_format($order['total_price'], 2, ',', '.') ?></li>
        </ul>

        <!-- pembatalan-->
         <div class="alert alert-warning">
            <h5>Informasi Pembatalan Pesanan</h5>
            <p>Anda dapat membatalkan pesanan dengan ketentuan:</p>
            <ul>
                <li>Pesanan belum dibayar dapat dibatalkan kapan saja</li>
                <li>Pesanan yang sudah dibayar dapat dibatalkan dalam 1 jam setelah dikonfirmasi admin</li>
                <li>Refund hanya bisa dilakukan sebelum admin memproses pesanan</li>
                <li>Pembatalan dikenakan biaya administrasi</li>
                <li>Refund akan diproses sesuai kebijakan kami</li>
            </ul>
        </div>

        <button id="pay-button" class="btn btn-success btn-lg w-100">Bayar Sekarang</button>
        
        <div class="mt-3 text-center">
            <?php if (canCancelOrder($order)): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    Batalkan Pesanan
                </button>
                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#refundModal">
                    Ajukan Refund
                </button>
            <?php endif; ?>
        </div>

        <!-- Modal untuk konfirmasi pembatalan -->
         <div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Batalkan Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Perhatian:</strong> 
                        Membatalkan pesanan akan dikenakan biaya administrasi. 
                        Apakah Anda yakin ingin membatalkan pesanan?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                    <button type="submit" name="cancel_order" class="btn btn-danger">Ya, Batalkan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                        <strong>Informasi Refund:</strong> 
                        Proses refund membutuhkan waktu 3-5 hari kerja setelah disetujui.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan Refund</label>
                        <textarea name="refund_reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="bank_account" class="form-control" 
                               value="<?= htmlspecialchars($order['bank_account'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Bank</label>
                        <input type="text" name="bank_name" class="form-control" 
                               value="<?= htmlspecialchars($order['bank_name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Pemilik Rekening</label>
                        <input type="text" name="account_holder_name" class="form-control" 
                               value="<?= htmlspecialchars($order['account_holder_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="refund_submit" class="btn btn-primary">Ajukan Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

        <!-- btn cancel order
        <div class="mt-3 text-center">
            <a href="cancel_order.php?order_id=<?= $order_id ?>" class="btn btn-outline-danger">Batalkan Pesanan</a>
        </div>
            -->
    </div>
</div>

<br>
<?php include('../view/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const payButton = document.getElementById("pay-button");
    payButton.addEventListener("click", function () {
        window.snap.pay('<?= $snapToken ?>', {
            onSuccess: function(result){
                window.location.href = 'http://<?= $_SERVER['HTTP_HOST'] ?>/printproject/user/order_receipt.php?order_id=<?= $order_id ?>&transaction_id=' + result.transaction_id;
            },
            onPending: function(result){
                alert("Menunggu pembayaran anda");
                console.log(result);
            },
            onError: function(result){
                window.location.href = 'http://<?= $_SERVER['HTTP_HOST'] ?>/printproject/user/payment-error.php?order_id=<?= $order_id ?>&status=error';
            },
            onClose: function(){
                alert("Anda menutup popup pembayaran tanpa menyelesaikan transaksi");
            }
        });
    });
});
</script>

</body>
</html>