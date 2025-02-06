<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah level pengguna adalah "cabang"
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Validasi CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: products.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil cabang dari session
$cabang = $_SESSION['cabang'] ?? null;
$username = $_SESSION['username'] ?? null; // User yang menambahkan produk
if (!$cabang || !$username) {
    $_SESSION['error_message'] = "Informasi cabang atau pengguna tidak ditemukan dalam sesi Anda.";
    header("Location: products.php");
    exit;
}

// Ambil data dari form
$nama_produk = trim($_POST['nama_produk'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$harga = (float)($_POST['harga'] ?? 0);

// Validasi data input
$errors = [];
if (empty($nama_produk)) $errors[] = "Nama produk wajib diisi.";
if (empty($deskripsi)) $errors[] = "Deskripsi wajib diisi.";
if ($harga <= 0) $errors[] = "Harga harus lebih dari 0.";

// Periksa apakah nama produk sudah ada di database untuk cabang ini
try {
    $queryCheckProduct = "SELECT COUNT(*) AS count FROM produk WHERE id_cabang = ? AND nama_produk = ?";
    $stmtCheckProduct = $conn->prepare($queryCheckProduct);
    $stmtCheckProduct->bind_param("is", $cabang, $nama_produk);
    $stmtCheckProduct->execute();
    $resultCheckProduct = $stmtCheckProduct->get_result();
    $productCount = $resultCheckProduct->fetch_assoc()['count'] ?? 0;

    if ($productCount > 0) {
        $errors[] = "Nama produk sudah ada. Silakan gunakan nama lain.";
    }

    $stmtCheckProduct->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan saat memeriksa nama produk: " . $e->getMessage();
    header("Location: products.php");
    exit;
}

// Jika ada error, kembali ke halaman sebelumnya
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: products.php");
    exit;
}

// Validasi file gambar (opsional)
$uploadDir = "../assets/img-products/";
$imageName = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = $_FILES['image'];
    $fileTmpPath = $fileInfo['tmp_name'];
    $fileType = mime_content_type($fileTmpPath);
    $allowedTypes = ['image/jpeg', 'image/png'];

    if (in_array($fileType, $allowedTypes)) {
        // Proses unggah dan konversi gambar ke .webp
        $uniqueFilename = uniqid('P-', true) . '.webp';
        $destinationPath = $uploadDir . $uniqueFilename;

        try {
            if ($fileType === 'image/jpeg') {
                $image = imagecreatefromjpeg($fileTmpPath);
            } elseif ($fileType === 'image/png') {
                $image = imagecreatefrompng($fileTmpPath);

                // Tambahkan background putih jika gambar memiliki transparansi
                $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                $white = imagecolorallocate($bg, 255, 255, 255);
                imagefill($bg, 0, 0, $white);
                imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                $image = $bg;
            }

            // Simpan sebagai .webp
            if (!imagewebp($image, $destinationPath, 100)) { // Gunakan kualitas 100
                throw new Exception("Gagal mengonversi gambar ke format .webp.");
            }

            imagedestroy($image);

            // Validasi ukuran file hasil
            if (file_exists($destinationPath) && filesize($destinationPath) <= 500 * 1024) {
                $imageName = $uniqueFilename;
            } else {
                unlink($destinationPath); // Hapus file jika terlalu besar
                throw new Exception("Ukuran gambar melebihi batas 250KB setelah konversi.");
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: products.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Format gambar tidak valid. Hanya mendukung JPG dan PNG.";
        header("Location: products.php");
        exit;
    }
}

// Masukkan data ke database
$sql = "INSERT INTO produk (id_cabang, nama_produk, kategori, deskripsi, harga, images, user_input) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssdss", $cabang, $nama_produk, $kategori, $deskripsi, $harga, $imageName, $username);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Produk berhasil ditambahkan.";
} else {
    $_SESSION['error_message'] = "Terjadi kesalahan saat menambahkan produk. Silakan coba lagi.";
}

// Tutup koneksi
$stmt->close();
$conn->close();

// Redirect kembali ke halaman produk
header("Location: products.php");
exit;
