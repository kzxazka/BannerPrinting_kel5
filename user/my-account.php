<?php
session_start();
require_once '../config.php';

// Cek login
$user_id = $_SESSION["ses_id"] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Ambil data user dan customer
$stmt = $conn->prepare("SELECT u.*, c.id as customer_id, c.bank_account, c.bank_name, c.account_holder_name 
                       FROM users u 
                       LEFT JOIN customers c ON u.id_user = c.user_id 
                       WHERE u.id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Proses update profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $bank_account = $_POST['bank_account'];
        $bank_name = $_POST['bank_name'];
        $account_holder = $_POST['account_holder'];
        
        // Update users table
        $update_user = $conn->prepare("UPDATE users SET nama_user = ?, email = ?, phone = ?, address = ? WHERE id_user = ?");
        $update_user->bind_param("ssssi", $nama, $email, $phone, $address, $user_id);
        $update_user->execute();
        
        // Update customers table
        $update_customer = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, 
                                         bank_account = ?, bank_name = ?, account_holder_name = ? WHERE user_id = ?");
        $update_customer->bind_param("sssssssi", $nama, $email, $phone, $address, 
                                   $bank_account, $bank_name, $account_holder, $user_id);
        $update_customer->execute();
        
        header("Location: my-account.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya - Danis Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../view/header.php'); ?>

    <div class="container my-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Menu Akun</h5>
                        <div class="list-group">
                            <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="fas fa-user me-2"></i> Profil Saya
                            </a>
                            <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-shopping-bag me-2"></i> Riwayat Pesanan
                            </a>
                            <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-cog me-2"></i> Pengaturan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profil Saya</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success" role="alert">
                                    Profil berhasil diperbarui!
                                </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="nama" 
                                                       value="<?= htmlspecialchars($user_data['nama_user']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?= htmlspecialchars($user_data['email']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">No. Telepon</label>
                                                <input type="tel" class="form-control" name="phone" 
                                                       value="<?= htmlspecialchars($user_data['phone']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Alamat</label>
                                                <textarea class="form-control" name="address" rows="3" 
                                                          required><?= htmlspecialchars($user_data['address']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nomor Rekening</label>
                                                <input type="text" class="form-control" name="bank_account" 
                                                       value="<?= htmlspecialchars($user_data['bank_account'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Bank</label>
                                                <input type="text" class="form-control" name="bank_name" 
                                                       value="<?= htmlspecialchars($user_data['bank_name'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Pemilik Rekening</label>
                                                <input type="text" class="form-control" name="account_holder" 
                                                       value="<?= htmlspecialchars($user_data['account_holder_name'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Riwayat Pesanan</h5>
                            </div>
                            <div class="card-body">
                                <?php include('riwayat-pesanan.php'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Pengaturan Akun</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Ganti Password</label>
                                        <input type="password" class="form-control" name="current_password" 
                                               placeholder="Password Saat Ini">
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" class="form-control" name="new_password" 
                                               placeholder="Password Baru">
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" class="form-control" name="confirm_password" 
                                               placeholder="Konfirmasi Password Baru">
                                    </div>
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Ganti Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../view/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>