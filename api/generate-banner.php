<?php
header('Content-Type: application/json');
require_once('../config.php');
require_once('../php/AI_DesignGenerator.php');

// Ambil data JSON
$data = json_decode(file_get_contents('php://input'), true);

$requiredFields = ['theme', 'colors', 'purpose', 'product_type'];
foreach($requiredFields as $field) {
    if(empty($data[$field])) {
        http_response_code(400);
        die(json_encode([
            'error' => "Field '$field' harus diisi",
            'field' => $field
        ]));
    }
}

// Validasi
if(count($data['colors']) < 2) {
    http_response_code(400);
    die(json_encode(['error' => "Pilih minimal 2 warna"]));
}

try {
    $ai = new AI_DesignGenerator();
    $result = $ai->generate([
        'theme' => $data['theme'],
        'colors' => $data['colors'],
        'purpose' => $data['purpose'],
        'product_type' => $data['product_type']
    ]);

    echo json_encode([
        'image_url' => $result['image_url'],
        'prompt_used' => $result['prompt']
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>