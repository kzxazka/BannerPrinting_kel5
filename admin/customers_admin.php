<?php
session_start();
require_once '../config.php';  // Error: Missing semicolon (;)

// Query untuk mengambil semua pelanggan
$customers_query = "
    SELECT c.*, 
           COUNT(o.id) as total_orders,
           SUM(CASE WHEN o.status IN ('paid', 'admin_confirmed', 'confirmed', 'processing', 'completed') THEN o.total_price ELSE 0 END) as total_spent
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
";
$customers_result = $conn->query($customers_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Admin Dashboard</title>
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
                            <a class="nav-link active text-white" href="customers_admin.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Pelanggan</h1>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No. Telepon</th>
                                        <th>Total Pesanan</th>
                                        <th>Total Pembelian</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $customer['id'] ?></td>
                                        <td><?= htmlspecialchars($customer['name']) ?></td>
                                        <td><?= htmlspecialchars($customer['email']) ?></td>
                                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                                        <td><?= $customer['total_orders'] ?></td>
                                        <td>Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewCustomerModal<?= $customer['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Detail Modals -->
    <?php while ($customer = mysqli_fetch_assoc(mysqli_query($conn, $customers_query))): ?>
    <div class="modal fade" id="viewCustomerModal<?= $customer['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Nama:</strong> <?= htmlspecialchars($customer['name']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>No. Telepon:</strong> <?= htmlspecialchars($customer['phone']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Alamat:</strong> <?= htmlspecialchars($customer['address']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Total Pesanan:</strong> <?= $customer['total_orders'] ?>
                    </div>
                    <div class="mb-3">
                        <strong>Total Pembelian:</strong> Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?>
                    </div>
                    <div class="mb-3">
                        <strong>Tanggal Daftar:</strong> <?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>