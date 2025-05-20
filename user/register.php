<?php
session_start();
require_once '../config.php';

if (isset($_POST['btnRegister'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_user']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Validasi input
    $errors = [];
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    // Validasi nomor telepon (minimal 10 digit, hanya angka)
    if (!preg_match("/^[0-9]{10,}$/", $phone)) {
        $errors[] = "Nomor telepon harus berupa angka dan minimal 10 digit!";
    }
    
    // Validasi password (minimal 8 karakter)
    if (strlen($password) < 8) {
        $errors[] = "Password harus minimal 8 karakter!";
    }

    // Cek apakah username sudah digunakan
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE usn_user='$username'");
    if (mysqli_num_rows($check_user) > 0) {
        $errors[] = "Username sudah digunakan!";
    }

    // Cek apakah email sudah digunakan
    $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email sudah terdaftar!";
    }

    if (empty($errors)) {
        // Hash password sebelum disimpan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Simpan user baru
        $insert = mysqli_query($conn, "INSERT INTO users (nama_user, usn_user, pass_user, email, phone, address) 
                                     VALUES ('$nama', '$username', '$hashed_password', '$email', '$phone', '$address')");
        if ($insert) {
            $user_id = mysqli_insert_id($conn);
            
            $insert_customer = mysqli_query($conn, "INSERT INTO customers (user_id, name, email, phone, address) 
                                                  VALUES ('$user_id', '$nama', '$email', '$phone', '$address')");
            
            if ($insert_customer) {
                echo "<script>
                    alert('Registrasi berhasil! Silakan login.');
                    window.location.href = 'login.php';
                </script>";
                exit();
            } else {
                $errors[] = "Gagal membuat data customer. Coba lagi.";
            }
        } else {
            $errors[] = "Gagal mendaftar. Coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register - Print Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #1e3c72, #2a5298);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Daftar Akun</h2>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_user" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="btnRegister" class="btn btn-primary">
                                    Daftar
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <p class="mb-0">Sudah punya akun? 
                                    <a href="login.php" class="text-decoration-none">Login</a>
                                </p>
                            </div>
                        </form>

                        <?php if (!empty($errors)) : ?>
                            <div class="alert alert-danger mt-3" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error) : ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>