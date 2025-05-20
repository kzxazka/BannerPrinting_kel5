<?php
session_start();
require_once '../config.php';

// Query untuk mendapatkan total pendapatan
$total_revenue_query = "
    SELECT SUM(total_price) as total_revenue
    FROM orders 
    WHERE status IN ('paid', 'completed', 'admin_confirmed', 'processing')
";
$total_revenue = $conn->query($total_revenue_query)->fetch_assoc()['total_revenue'];

// Query untuk mendapatkan laporan penjualan per bulan
$monthly_sales_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(total_price) as total_revenue
    FROM orders 
    WHERE status IN ('paid', 'completed', 'admin_confirmed', 'processing')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
";
$monthly_sales = $conn->query($monthly_sales_query);

// Query untuk produk terlaris
$top_products_query = "
    SELECT 
        p.name as product_name,
        COUNT(*) as total_orders,
        SUM(o.total_price) as total_revenue
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.status IN ('paid', 'completed', 'admin_confirmed', 'processing')
    GROUP BY p.id, p.name
    ORDER BY total_orders DESC
    LIMIT 5
";
$top_products = $conn->query($top_products_query);

// Query untuk pelanggan teratas
$top_customers_query = "
    SELECT 
        c.name as customer_name,
        COUNT(*) as total_orders,
        SUM(o.total_price) as total_spent
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.status IN ('paid', 'completed', 'admin_confirmed', 'processing')
    GROUP BY c.id, c.name
    ORDER BY total_spent DESC
    LIMIT 5
";
$top_customers = $conn->query($top_customers_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin Dashboard</title>
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
                            <a class="nav-link active text-white" href="reports_admin.php">
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
                <h1 class="h2 mb-4">Laporan Penjualan</h1>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 report-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Pendapatan</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            Rp <?= number_format($total_revenue, 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Top Products -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Produk Terlaris</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Total Pesanan</th>
                                                <th>Total Pendapatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($product = $top_products->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                <td><?= $product['total_orders'] ?></td>
                                                <td>Rp <?= number_format($product['total_revenue'], 0, ',', '.') ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Customers -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Pelanggan Teratas</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Pelanggan</th>
                                                <th>Total Pesanan</th>
                                                <th>Total Pembelian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($customer = $top_customers->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                                                <td><?= $customer['total_orders'] ?></td>
                                                <td>Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for monthly sales chart
        const monthlyData = <?php 
            $labels = [];
            $revenues = [];
            while ($row = $monthly_sales->fetch_assoc()) {
                $labels[] = date('M Y', strtotime($row['month'] . '-01'));
                $revenues[] = $row['total_revenue'];
            }
            echo json_encode([
                'labels' => $labels,
                'revenues' => $revenues
            ]);
        ?>;

        // Create monthly sales chart
        new Chart(document.getElementById('monthlySalesChart'), {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Pendapatan Bulanan',
                    data: monthlyData.revenues,
                    borderColor: '#4e73df',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>