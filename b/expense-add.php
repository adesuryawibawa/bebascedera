<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json"); // Mengatur header untuk JSON response

// Validasi login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: expense.php");
    exit;
}

// Cek apakah level pengguna adalah Admin Cabang
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: expense.php");
    exit;
}

// Include konfigurasi database
include '../includes/config.php';

$id_cabang = $_SESSION['cabang'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Validasi input
$category_id = intval($_POST['category_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');
$transaction_date = $_POST['transaction_date'] ?? '';

// Validasi wajib diisi
if ($category_id <= 0 || $amount <= 0 || empty($transaction_date)) {
    $_SESSION['error_message'] = "Semua field wajib diisi kecuali bukti transaksi.";
    header("Location: expense.php");
    exit;
}

// Validasi tanggal transaksi
if (!strtotime($transaction_date)) {
    $_SESSION['error_message'] = "Tanggal transaksi tidak valid.";
    header("Location: expense.php");
    exit;
}

// Handle upload gambar
$receipt_image = null;
if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png'];

    // Validasi tipe file
    $file_type = mime_content_type($_FILES['receipt_image']['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Jenis file bukti transaksi tidak valid. Hanya JPG atau PNG yang diperbolehkan.";
        header("Location: expense.php");
        exit;
    }

    // Direktori penyimpanan gambar
    $upload_dir = '../assets/expenses/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Nama file unik
    $unique_name = uniqid('expense_', true) . '.webp';
    $upload_path = $upload_dir . $unique_name;

    // Proses konversi ke WEBP
    try {
        if ($file_type === 'image/jpeg') {
            $image = imagecreatefromjpeg($_FILES['receipt_image']['tmp_name']);
        } elseif ($file_type === 'image/png') {
            $image = imagecreatefrompng($_FILES['receipt_image']['tmp_name']);

            // Tambahkan background putih jika gambar memiliki transparansi
            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $image = $bg;
        } else {
            throw new Exception("Format gambar tidak didukung.");
        }

        // Simpan sebagai .webp
        if (!imagewebp($image, $upload_path, 100)) {
            throw new Exception("Gagal menyimpan gambar bukti transaksi.");
        }

        imagedestroy($image);

        $receipt_image = $unique_name; // Simpan nama file untuk database
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: expense.php");
        exit;
    }
}

// Masukkan data ke database
$query = "INSERT INTO expenses (id_cabang, category_id, amount, description, receipt_image, transaction_date, user_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iissssi", $id_cabang, $category_id, $amount, $description, $receipt_image, $transaction_date, $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Pengeluaran berhasil ditambahkan.";
} else {
    $_SESSION['error_message'] = "Gagal menambahkan pengeluaran. Silakan coba lagi.";
}

$stmt->close();
$conn->close();
header("Location: expense.php");
exit;
?>
