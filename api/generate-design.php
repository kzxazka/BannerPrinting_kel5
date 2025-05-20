<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once '../config.php';

    // Get JSON input
    $json = file_get_contents('php://input');
    if (!$json) {
        throw new Exception('Tidak ada data yang diterima');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Format JSON tidak valid: ' . json_last_error_msg());
    }

    // Validasi input yang diperlukan
    $required_fields = ['theme', 'colors', 'purpose', 'additional_details'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Field '$field' harus diisi");
        }
    }

    // Validasi array colors
    if (!is_array($data['colors']) || count($data['colors']) < 2) {
        throw new Exception('Minimal harus memilih 2 warna');
    }

    // Buat deskripsi prompt yang lebih detail
    $prompt_description = "Buatkan desain {$data['purpose']} dengan tema {$data['theme']}. ";
    $prompt_description .= "Gunakan warna dominan {$data['colors'][0]} dan {$data['colors'][1]}. ";
    
    if (!empty($data['additional_details'])) {
        $prompt_description .= "Detail tambahan: {$data['additional_details']}. ";
    }
    
    // Tambahkan detail berdasarkan tujuan
    switch($data['purpose']) {
        case 'Promosi Bisnis':
            $prompt_description .= "Desain harus profesional dan menarik perhatian. Tambahkan elemen yang menunjukkan kepercayaan dan kredibilitas bisnis.";
            break;
        case 'Event/Kegiatan':
            $prompt_description .= "Desain harus energetik dan informatif. Fokus pada waktu, tempat, dan highlight utama acara.";
            break;
        case 'Penggunaan Pribadi':
            $prompt_description .= "Desain lebih personal dan kreatif. Sesuaikan dengan preferensi individual.";
            break;
        case 'Edukasi/Informasi':
            $prompt_description .= "Desain harus jelas dan mudah dibaca. Fokus pada penyampaian informasi dengan visual yang mendukung.";
            break;
    }
    
    // Simulasi generate gambar (ganti dengan integrasi AI sebenarnya)
    $image_path = '/printproject/uploads/generated/' . time() . '_generated.jpg';
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/printproject/uploads/generated/' . time() . '_generated.jpg';
    
    // Pastikan folder ada dan memiliki permission yang benar
    if (!is_dir(dirname($full_path))) {
        mkdir(dirname($full_path), 0755, true);
    }
    
    // Buat placeholder image menggunakan GD Library
    $width = 800;
    $height = 600;
    $image = imagecreatetruecolor($width, $height);
    
    // Konversi warna hex ke RGB
    $color1 = sscanf($data['colors'][0], "#%02x%02x%02x");
    $color2 = sscanf($data['colors'][1], "#%02x%02x%02x");
    
    // Buat warna dari input user
    $bg_color = imagecolorallocate($image, $color1[0], $color1[1], $color1[2]);
    $text_color = imagecolorallocate($image, $color2[0], $color2[1], $color2[2]);
    
    // Isi background
    imagefill($image, 0, 0, $bg_color);
    
    // Tambah teks preview menggunakan imagestring sebagai alternatif imagettftext
    $text_y = 50;
    imagestring($image, 5, 50, $text_y, "Preview Design", $text_color);
    imagestring($image, 5, 50, $text_y + 30, "Tema: " . $data['theme'], $text_color);
    imagestring($image, 5, 50, $text_y + 60, "Tujuan: " . $data['purpose'], $text_color);
    
    // Simpan sebagai JPG
    imagejpeg($image, $full_path, 90);
    imagedestroy($image);
    
    // Tambahkan debugging sebelum return response
    error_log("Generated image path: " . $full_path);
    error_log("Image URL path: " . $image_path);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Desain berhasil digenerate',
        'image_url' => $image_path,
        'image_path' => $full_path,
        'prompt_used' => $prompt_description
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}