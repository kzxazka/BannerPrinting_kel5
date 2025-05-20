<?php
session_start();
require_once '../config.php';  // Mengubah path ke parent directory

if (isset($_POST['btnLogin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Cek di tabel admin terlebih dahulu
    $sql_admin = "SELECT * FROM admin WHERE username=? AND password=?";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("ss", $username, $password);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    
    if ($result_admin->num_rows > 0) {
        $data = $result_admin->fetch_assoc();
        $_SESSION['admin_id'] = $data['id'];
        $_SESSION['admin_username'] = $data['username'];
        header("Location: ../admin/dashboard_unfinish.php");  // Mengubah path redirect
        exit();
    } else {
        // Jika tidak ditemukan di tabel admin, cek di tabel users
        $sql_user = "SELECT * FROM users WHERE usn_user=? AND pass_user=?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ss", $username, $password);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        
        if ($result_user->num_rows > 0) {
            $data = $result_user->fetch_assoc();
            $_SESSION['ses_id'] = $data['id_user'];
            $_SESSION['ses_nama'] = $data['nama_user'];
            $_SESSION['ses_username'] = $data['usn_user'];
            $_SESSION['ses_level'] = $data['ses_level'];
            header("Location: index-update.php");  // Ini sudah benar karena dalam folder yang sama
            exit();
        }
    }
    
    // Jika login gagal
    $error_message = "Username atau password salah!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    body {
      background: linear-gradient(to right, #1e3c72, #2a5298);
    }
    .card {
      border-radius: 1rem;
    }
  </style>
</head>
<body>

<section class="vh-100 d-flex align-items-center justify-content-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card text-white bg-dark">
          <div class="card-body p-5 text-center">
            <h2 class="fw-bold mb-4">Login</h2>

            <?php if (isset($error_message)) : ?>
              <div class="alert alert-danger mb-4" role="alert">
                <?= $error_message ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="form-outline form-white mb-4 text-start">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control form-control-lg" required />
              </div>

              <div class="form-outline form-white mb-4 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required />
              </div>

              <button type="submit" name="btnLogin" class="btn btn-outline-light btn-lg px-5">Login</button>
            </form>

            <p class="mt-4 small"><a class="text-white-50" href="#">Lupa Password?</a></p>
            <p class="mt-3">Belum punya akun? <a class="text-white-50 fw-bold" href="register.php">Daftar</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>