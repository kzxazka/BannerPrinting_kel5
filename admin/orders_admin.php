<?php
session_start();
require_once '../config.php';
// Get orders with customer information
$orders_query = "
    SELECT o.*, c.name as customer_name, c.phone, c.email, p.name as product_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC
";
$orders_result = $conn->query($orders_query);

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
        case 'refund_requested': return 'bg-secondary';
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
        case 'refund_requested': return 'REFUND DIAJUKAN';
        case 'refunded': return 'TELAH DIREFUND';
        default: return 'STATUS TIDAK DIKETAHUI';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin Dashboard</title>
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
                            <a class="nav-link text-white" href="dashboard_unfinish.php">
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
                                        <a class="nav-link text-white" href="dashboard_unfinish.php?status=new">
                                            <i class="fas fa-circle-notch me-2"></i> Pesanan Baru
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="dashboard_unfinish.php?status=confirmed">
                                            <i class="fas fa-check-circle me-2"></i> Konfirmasi
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="dashboard_unfinish.php?status=processing">
                                            <i class="fas fa-cog me-2"></i> Dalam Proses
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="dashboard_unfinish.php?status=completed">
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
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logout_admin.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Pesanan</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message']); endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Produk</th>
                                        <th>Total Harga</th>
                                        <th>Status Pembayaran</th>
                                        <th>Status Produksi</th>
                                        <th>Tanggal Pesanan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $order['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($order['customer_name']) ?>
                                            <div class="small text-muted"><?= $order['phone'] ?></div>
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

                                    <!-- Modal Update Status -->
                                    <div class="modal fade" id="orderModal<?= $order['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Status Pesanan #<?= $order['id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Pembayaran</label>
                                                            <select name="status" class="form-select">
                                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                                                <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Pembayaran Sukses</option>
                                                                <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Pesanan Dikonfirmasi</option>
                                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                                                <option value="refunded" <?= $order['status'] === 'refunded' ? 'selected' : '' ?>>Direfund</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Status Produksi</label>
                                                            <select name="production_status" class="form-select">
                                                                <option value="pending" <?= $order['production_status'] === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                                                <option value="confirmed" <?= $order['production_status'] === 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                                                <option value="processing" <?= ($order['production_status'] === 'processing' || $order['production_status'] === 'in_progress') ? 'selected' : '' ?>>Dalam Proses Produksi</option>
                                                                <option value="completed" <?= $order['production_status'] === 'completed' ? 'selected' : '' ?>>Selesai</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Catatan Admin</label>
                                                            <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($order['admin_notes'] ?? '') ?></textarea>
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
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>