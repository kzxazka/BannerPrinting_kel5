<?php
session_start();
require_once '../config.php';

// Cek koneksi database
if ($conn->connect_error) {
    $_SESSION['error'] = "Koneksi database gagal: " . $conn->connect_error;
    header("Location: index-update.php#contactForm");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_pelapor'];
    $email = $_POST['email_pelapor'];
    $subjek = $_POST['subjek_pelapor'];
    $pesan = $_POST['pesan_pelapor'];
    
    // Validasi input
    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan)) {
        $_SESSION['error'] = "Semua field harus diisi!";
        header("Location: index-update.php#contactForm");
        exit();
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid!";
        header("Location: index-update.php#contactForm");
        exit();
    }
    
    try {
        // Pastikan tabel ada
        $check_table = "SHOW TABLES LIKE 'contact_messages'";
        $table_exists = $conn->query($check_table);
        
        if ($table_exists->num_rows == 0) {
            // Buat tabel jika belum ada
            $create_table = "CREATE TABLE contact_messages (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subjek VARCHAR(200) NOT NULL,
                pesan TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$conn->query($create_table)) {
                throw new Exception("Gagal membuat tabel: " . $conn->error);
            }
        }
        
        // Simpan ke database
        $query = "INSERT INTO contact_messages (nama, email, subjek, pesan) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan query: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Pesan Anda telah berhasil dikirim!";
        } else {
            throw new Exception("Gagal menyimpan pesan: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        error_log("Error in kirim_laporan.php: " . $e->getMessage());
    }
    
    header("Location: index-update.php#contactForm");
    exit();
}
?>
