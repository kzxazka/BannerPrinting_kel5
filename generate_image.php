<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

// Fungsi untuk memformat prompt berdasarkan input
function formatPrompt($style, $color, $theme, $description) {
    // Validasi input
    if (empty($style) || empty($color) || empty($theme) || empty($description)) {
        throw new Exception('Semua parameter harus diisi');
    }
    
    $prompt = "Create a {$style} design with {$color} as the dominant color. ";
    $prompt .= "Theme: {$theme}. ";
    $prompt .= "Additional details: {$description}";
    return $prompt;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ambil input dari form
        $style = $_POST['style'];
        $color = $_POST['dominant_color'];
        $theme = $_POST['theme'];
        $description = $_POST['description'];
        $size = $_POST['size'];
        
        // Format prompt untuk DeepSeek
        $prompt = formatPrompt($style, $color, $theme, $description);
        
        // Konfigurasi DeepSeek API
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.deepseek.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . DEEPSEEK_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'prompt' => $prompt,
                'size' => $size,
                'n' => 1
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        // Simpan gambar yang dihasilkan
        if (isset($result['data'][0]['url'])) {
            $imageUrl = $result['data'][0]['url'];
            $imageName = uniqid() . '.png';
            $imagePath = 'uploads/generated/' . $imageName;
            
            // Download dan simpan gambar
            file_put_contents($imagePath, file_get_contents($imageUrl));
            
            // Kirim response sukses
            echo json_encode([
                'success' => true,
                'image_url' => $imagePath
            ]);
        } else {
            throw new Exception('Gagal mendapatkan URL gambar');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}