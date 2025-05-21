<?php
session_start();
require_once '../config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil user_id dari session
$user_id = $_SESSION["ses_id"] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Ambil data customer berdasarkan user_id
$stmt_customer = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt_customer->bind_param("i", $user_id);
$stmt_customer->execute();
$result_customer = $stmt_customer->get_result();

if ($result_customer->num_rows == 0) {
    die("Data customer tidak ditemukan. Silakan lengkapi profil Anda terlebih dahulu.");
}

$customer = $result_customer->fetch_assoc();
$customer_id = $customer['id']; // Menggunakan id dari hasil query

// Ambil daftar produk untuk dropdown
$products_query = "SELECT * FROM products ORDER BY name";
$products_result = $conn->query($products_query);
$products = [];
while($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST["product_type"];
    $width = (float) $_POST["width"];
    $height = (float) $_POST["height"];
    $price = (float) $_POST["total_price"];
    $delivery_option = $_POST["delivery_option"];

    // Dapatkan product_id dari nama
    $stmt_product = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmt_product->bind_param("s", $product_name);
    $stmt_product->execute();
    $result = $stmt_product->get_result();
    $product = $result->fetch_assoc();
    $product_id = $product["id"];

    // Cek apakah ini pemesanan ulang
    $is_reorder = $_GET["reorder"] ?? false;

    if ($is_reorder) {
    // Ambil data customer yang sudah ada
    $customer = $result_customer->fetch_assoc();
    
    // Pre-fill form dengan data customer yang sudah ada
    $_SESSION['customer_data'] = [
        'name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'address' => $customer['address']
    ];
    }
    // Kalkulasi biaya pengiriman
    $delivery_fee = ($delivery_option === 'delivery') ? 7000 : 0;
    $total_price = $price + $delivery_fee;

    // Proses desain (AI atau Upload)
    $use_ai = $_POST["use_ai"];
    $design_type = ($use_ai === "yes") ? "ai" : "upload";
    $design_path = null;
    $ai_prompt = null;

    if ($design_type === "ai") {
        // Proses desain AI
        $ai_theme = $_POST["ai_theme"];
        $ai_color1 = $_POST["ai_color1"];
        $ai_color2 = $_POST["ai_color2"];
        $ai_purpose = $_POST["ai_purpose"];
        $design_path = $_POST["ai_design_path"];
        $ai_prompt = "Tema: $ai_theme | Warna: $ai_color1, $ai_color2 | Tujuan: $ai_purpose";
    } else {
        // Proses upload file
        $upload = $_FILES["design_file"];
        if ($upload["error"] === 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $filename = time() . '_' . basename($upload["name"]);
            $design_path = $target_dir . $filename;
            move_uploaded_file($upload["tmp_name"], $design_path);
        } else if ($upload["error"] !== UPLOAD_ERR_NO_FILE) {
            die("Error uploading file: " . $upload["error"]);
        }
    }

    // Insert ke database
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, product_id, width, height, total_price, delivery_option, delivery_fee, design_type, design_path, prompt_category, prompt_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiddsssssss", $customer_id, $product_id, $width, $height, $total_price, $delivery_option, $delivery_fee, $design_type, $design_path, $ai_theme, $ai_prompt);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        header("Location: ../user/payment-update.php?order_id=" . $order_id);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Form Produk</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .color-picker {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            display: inline-block;
        }
        #aiDesignResult {
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        #aiDesignResult img {
            max-width: 100%;
            border: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include('./view/header.php'); ?>
    
    <div class="container mt-5">
        <!-- Progress Bar -->
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-primary" style="width: 50%"></div>
        </div>

        <!-- Status Steps -->
        <div class="d-flex justify-content-center mt-4">
            <div class="text-center mx-3">
                <div class="badge bg-primary rounded-circle p-3">1</div>
                <p class="mt-2">Produk & Desain</p>
            </div>
            <div class="text-center mx-3">
                <div class="badge bg-secondary rounded-circle p-3">2</div>
                <p class="mt-2">Pembayaran</p>
            </div>
        </div>

        <!-- Form Produk -->
        <div class="card shadow-sm p-4 mt-4 mb-5">
            <h2 class="mb-4">Pilih Produk</h2>
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <!-- Informasi Produk -->
                <div class="mb-3">
                    <label>Jenis Produk</label>
                    <select name="product_type" id="product_type" class="form-control" required>
                        <?php foreach($products as $product): ?>
                        <option value="<?= htmlspecialchars($product['name']) ?>" 
                                data-price="<?= htmlspecialchars($product['price_per_meter']) ?>">
                            <?= htmlspecialchars($product['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dimensi Produk -->
                <div class="mb-3">
                    <label>Lebar (meter)</label>
                    <input type="number" step="0.01" id="width" name="width" class="form-control" 
                           oninput="calculatePrice()" required>
                </div>
                <div class="mb-3">
                    <label>Tinggi (meter)</label>
                    <input type="number" step="0.01" id="height" name="height" class="form-control" 
                           oninput="calculatePrice()" required>
                </div>

                <!-- Pilihan Desain -->
                <div class="mb-3">
                    <label>Desain</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="use_ai" id="use_ai_no" 
                               value="no" checked onclick="toggleDesignInput()">
                        <label class="form-check-label" for="use_ai_no">Upload Desain Sendiri</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="use_ai" id="use_ai_yes" 
                               value="yes" onclick="toggleDesignInput()">
                        <label class="form-check-label" for="use_ai_yes">Gunakan AI Designer</label>
                    </div>
                </div>

                <!-- Upload Field -->
                <div id="upload_field" class="mb-3">
                    <label>Upload Desain (Format: JPG/PNG)</label>
                    <input type="file" name="design_file" id="design_file" class="form-control" accept="image/*">
                </div>

                <!-- AI Design Fields -->
                <div id="ai_prompt_fields" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Tema Desain <span class="text-danger">*</span></label>
                        <input type="text" name="ai_theme" class="form-control" placeholder="Contoh: Modern, Klasik, Minimalis, Vintage, Futuristik">
                        <small class="text-muted">Jelaskan tema desain secara spesifik, misalnya: 'Modern minimalis dengan sentuhan alam'</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Warna Dominan <span class="text-danger">*</span></label>
                        <div class="color-picker">
                            <div>
                                <label>Warna Utama</label>
                                <input type="color" name="ai_color1" value="#3366ff">
                            </div>
                            <div>
                                <label>Warna Aksen</label>
                                <input type="color" name="ai_color2" value="#ffcc00">
                            </div>
                        </div>
                        <small class="text-muted">Pilih kombinasi warna yang harmonis untuk desain Anda</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tujuan Desain</label>
                        <select name="ai_purpose" class="form-select">
                            <option value="Promosi Bisnis">Promosi Bisnis</option>
                            <option value="Event/Kegiatan">Event/Kegiatan</option>
                            <option value="Penggunaan Pribadi">Penggunaan Pribadi</option>
                            <option value="Edukasi/Informasi">Edukasi/Informasi</option>
                        </select>
                        <small class="text-muted">Pilih tujuan yang paling sesuai untuk mengoptimalkan hasil desain</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detail Tambahan (Opsional)</label>
                        <textarea name="ai_additional_details" class="form-control" rows="3" placeholder="Tambahkan detail spesifik yang Anda inginkan dalam desain"></textarea>
                        <small class="text-muted">Contoh: 'Tambahkan elemen geometris', 'Gunakan font modern', dll.</small>
                    </div>
                    <button type="button" class="btn btn-primary mb-3" id="generateBtn" onclick="generateAIDesign()">
                        Generate Desain
                    </button>
                    
                    <div id="aiDesignResult">
                        <h5>Preview Desain</h5>
                        <img id="aiGeneratedImage" src="" class="img-fluid mb-2">
                        <input type="hidden" name="ai_design_path" id="aiDesignPath">
                        <div class="alert alert-success">Desain siap digunakan!</div>
                    </div>
                </div>

                <!-- Opsi Pengiriman -->
                <div class="mb-3">
                    <label>Pilih Pengiriman</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="delivery_option" 
                               id="pickup" value="pickup" checked>
                        <label class="form-check-label" for="pickup">Ambil di Toko</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="delivery_option" 
                               id="delivery" value="delivery">
                        <label class="form-check-label" for="delivery">Kirim ke Alamat (Rp 7.000)</label>
                    </div>
                </div>

                <!-- Total Harga -->
                <div class="mb-3">
                    <label>Total Harga (Rp)</label>
                    <input type="text" id="total_price" name="total_price" class="form-control" readonly required>
                </div>
                
                <!-- Tombol Aksi -->
                <button type="button" class="btn btn-danger" onclick="document.location='form_customer.php'">
                    Kembali
                </button>
                <button type="submit" class="btn btn-success">Lanjut ke Pembayaran</button>
            </form>
        </div>
    </div>

    <?php include('../view/footer.php'); ?>
    
    <script>
        // Fungsi kalkulasi harga
        function calculatePrice() {
            let width = parseFloat(document.getElementById("width").value);
            let height = parseFloat(document.getElementById("height").value);
            let productSelect = document.getElementById("product_type");
            let selectedOption = productSelect.options[productSelect.selectedIndex];
            let basePrice = parseFloat(selectedOption.getAttribute("data-price"));
            let deliveryFee = document.getElementById("delivery").checked ? 7000 : 0;

            if (!isNaN(width) && !isNaN(height) && !isNaN(basePrice)) {
                let area = width * height;
                let total = area * basePrice + deliveryFee;
                document.getElementById("total_price").value = total.toFixed(2);
            }
        }

        // Event listener untuk opsi pengiriman
        document.querySelectorAll('input[name="delivery_option"]').forEach(radio => {
            radio.addEventListener('change', calculatePrice);
        });

        // Toggle input desain
        function toggleDesignInput() {
            const useAi = document.getElementById("use_ai_yes").checked;
            document.getElementById("upload_field").style.display = useAi ? "none" : "block";
            document.getElementById("ai_prompt_fields").style.display = useAi ? "block" : "none";
            
            if (useAi) {
                document.getElementById("design_file").removeAttribute("required");
                document.querySelector("input[name='ai_theme']").setAttribute("required", "required");
            } else {
                document.getElementById("design_file").setAttribute("required", "required");
                document.querySelector("input[name='ai_theme']").removeAttribute("required");
            }
            
            if(!useAi) {
                document.getElementById("aiDesignResult").style.display = "none";
            }
        }

        // Generate AI Design
        function generateAIDesign() {
            // Ambil nilai input
            const theme = document.querySelector('input[name="ai_theme"]').value;
            const color1 = document.querySelector('input[name="ai_color1"]').value;
            const color2 = document.querySelector('input[name="ai_color2"]').value;
            const purpose = document.querySelector('select[name="ai_purpose"]').value;
            const additionalDetails = document.querySelector('textarea[name="ai_additional_details"]').value;
        
            // Validasi input
            if (!theme) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Mohon isi tema desain terlebih dahulu!'
                });
                return;
            }
        
            // Tampilkan loading
            const generateBtn = document.querySelector("#generateBtn");
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
        
            // Siapkan data untuk dikirim
            const requestData = {
                theme: theme,
                colors: [color1, color2],
                purpose: purpose,
                additional_details: additionalDetails || ''
            };
        
            // Kirim request ke endpoint AI
            fetch('../api/generate-design.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response:', response);
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Data response:', data); // Tambahkan ini untuk debug
                    document.getElementById('aiGeneratedImage').src = data.image_url;
                    document.getElementById('aiDesignPath').value = data.image_path;
                    document.getElementById('aiDesignResult').style.display = 'block';
                    document.getElementById('aiDesignResult').scrollIntoView({ behavior: 'smooth' });
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Desain berhasil digenerate!'
                    });
                } else {
                    throw new Error(data.message || 'Gagal generate desain');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Terjadi kesalahan saat generate desain'
                });
            })
            .finally(() => {
                generateBtn.disabled = false;
                generateBtn.innerHTML = 'Generate Desain';
            });
        }

        // Form validation
        document.getElementById("productForm").addEventListener("submit", function(e) {
            const useAi = document.getElementById("use_ai_yes").checked;
            
            if (useAi && !document.getElementById("aiDesignPath").value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Desain Belum Di-generate',
                    text: 'Harap generate desain AI terlebih dahulu',
                    confirmButtonColor: '#3085d6'
                });
            } else if (!useAi && document.getElementById("design_file").files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'File Belum Diupload',
                    text: 'Harap upload file desain anda',
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Initialize
        document.addEventListener("DOMContentLoaded", function() {
            calculatePrice();
            toggleDesignInput();
        });
    </script>
</body>
</html>


<!--
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Generate Desain dengan AI</h5>
    </div>
    <div class="card-body">
        <form id="aiGenerateForm" action="generate_image.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Gaya Desain</label>
                <select class="form-select" name="style" required>
                    <option value="realistic">Realistis</option>
                    <option value="cartoon">Kartun</option>
                    <option value="abstract">Abstrak</option>
                    <option value="minimalist">Minimalis</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Warna Dominan</label>
                <input type="color" class="form-control" name="dominant_color" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tema</label>
                <select class="form-select" name="theme" required>
                    <option value="nature">Alam</option>
                    <option value="urban">Perkotaan</option>
                    <option value="technology">Teknologi</option>
                    <option value="abstract">Abstrak</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi Detail</label>
                <textarea class="form-control" name="description" rows="4" placeholder="Jelaskan detail desain yang Anda inginkan..." required></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Ukuran Output</label>
                <select class="form-select" name="size" required>
                    <option value="1024x1024">1024 x 1024</option>
                    <option value="512x512">512 x 512</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Generate Desain</button>
        </form>
    </div>
</div>
    -->