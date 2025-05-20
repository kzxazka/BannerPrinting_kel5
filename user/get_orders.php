<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["ses_id"])) {
    exit('Unauthorized');
}

$user_id = $_SESSION["ses_id"];
$status_filter = $_GET['status'] ?? 'all';

// Query dasar
$base_query = "SELECT o.*, p.name as product_name, c.name as customer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN customers c ON o.customer_id = c.id 
          WHERE c.user_id = ?";

// Filter berdasarkan status
switch($status_filter) {
    case 'completed':
        $base_query .= " AND o.status = 'completed'";
        break;
    case 'active':
        $base_query .= " AND o.status != 'completed'";
        break;
}

$base_query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($base_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Output pesanan dalam format HTML
while ($order = $result->fetch_assoc()): ?>
    <div class="card mb-3 order-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title">Pesanan #<?= $order['id'] ?></h5>
                <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                    <?= getStatusText($order['status']) ?>
                </span>
            </div>
            <p class="card-text">
                <strong>Produk:</strong> <?= htmlspecialchars($order['product_name']) ?><br>
                <strong>Total:</strong> Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
            </p>
            <a href="order_receipt.php?order_id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                Lihat Detail
            </a>
            <small>Dibuat: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
        </div>
    </div>
<?php endwhile;

// Include helper functions
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
?>