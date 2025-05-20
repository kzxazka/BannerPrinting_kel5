<?php
session_start();
require_once '../config.php';

// Cek jika admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Proses tambah produk
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price_per_meter = $_POST['price_per_meter'];
    $description = $_POST['description'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $query = "INSERT INTO products (name, price_per_meter, description, is_active) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsi", $name, $price_per_meter, $description, $is_active);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil ditambahkan!";
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }

    header("Location: products_admin.php");
    exit();
}

// Proses edit produk
if (isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $price_per_meter = $_POST['price_per_meter'];
    $description = $_POST['description'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $query = "UPDATE products SET name = ?, price_per_meter = ?, description = ?, is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsii", $name, $price_per_meter, $description, $is_active, $product_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil diperbarui!";
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }

    header("Location: products_admin.php");
    exit();
}

// Proses hapus produk
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil dihapus!";
    } else {
        $_SESSION['message'] = "Error: " . $conn->error;
    }
    
    header("Location: products_admin.php");
    exit();
}
?>