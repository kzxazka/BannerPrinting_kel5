<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'printprojectsec';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$base_url = "/BannerPrinting_kel5";

?>