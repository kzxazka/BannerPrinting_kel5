<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$prompt = $data['prompt'] ?? '';

if (!$prompt) {
    echo json_encode(['error' => 'Prompt kosong']); exit;
}

// Ganti dengan API key dan endpoint sesuai DeepSeek
$apiKey = 'sk-955116775d9b4f9bbdacba68d2559ca3';
$endpoint = 'https://api.deepseek.com/v1/images/generate';

$payload = json_encode([
    'prompt' => $prompt,
    'size' => '1024x1024'  // Bisa disesuaikan
]);

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$result = json_decode($response, true);

// Misalnya DeepSeek mengembalikan 'image_url'
if (isset($result['image_url'])) {
    echo json_encode(['image_url' => $result['image_url']]);
} else {
    echo json_encode(['error' => 'Gagal mendapatkan gambar']);
}
