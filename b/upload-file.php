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

// Cek level akses
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Validasi input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien = $_POST['id_pasien'] ?? null;
    $file_type = $_POST['file_type'] ?? null;
    $csrf_token = $_POST['csrf_token'] ?? null;

    // Validasi CSRF token
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "CSRF token tidak valid.";
        header("Location: pasien.php");
        exit;
    }

    if (!$id_pasien || !in_array($file_type, ['soap', 'poc'])) {
        $_SESSION['error_message'] = "Data tidak valid.";
        header("Location: pasien.php");
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Gagal mengunggah file.";
        header("Location: pasien.php");
        exit;
    }

    $file = $_FILES['file'];

    // Validasi ukuran file
    if ($file['size'] > 5 * 1024 * 1024) { // Max 5MB
        $_SESSION['error_message'] = "File terlalu besar. Maksimal 5MB.";
        header("Location: pasien.php");
        exit;
    }

    // Validasi format file
    $allowedMimeTypes = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedMimeTypes)) {
        $_SESSION['error_message'] = "Format file tidak didukung. Hanya JPG atau PNG yang diizinkan.";
        header("Location: pasien.php");
        exit;
    }

    // Penentuan direktori dan kolom
    $uploadDir = $file_type === 'soap' ? '../assets/soap/' : '../assets/poc/';
    $column = $file_type === 'soap' ? 'soap_image' : 'poc_image';

    // Cek file lama
    $query = "SELECT $column FROM pasien WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_pasien);
    $stmt->execute();
    $stmt->bind_result($oldFile);
    $stmt->fetch();
    $stmt->close();

    // Hapus file lama jika ada
    if ($oldFile) {
        $oldFilePath = $uploadDir . $oldFile;
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }

    // Proses file baru
    $fileName = uniqid('P-', true) . '.webp';
    $filePath = $uploadDir . $fileName;

    // Konversi ke .webp
    try {
        if ($file['type'] === 'image/jpeg') {
            $image = imagecreatefromjpeg($file['tmp_name']);
        } elseif ($file['type'] === 'image/png') {
            $image = imagecreatefrompng($file['tmp_name']);

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
        if (!imagewebp($image, $filePath, 100)) { // Gunakan kualitas 100
            throw new Exception("Gagal mengonversi gambar ke format .webp.");
        }

        imagedestroy($image);

        // Validasi ukuran file hasil
        if (!file_exists($filePath) || filesize($filePath) > 250 * 1024) {
            unlink($filePath); // Hapus file jika terlalu besar
            throw new Exception("Ukuran gambar melebihi batas 250KB setelah konversi.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: pasien.php");
        exit;
    }

    // Simpan ke database
    $query = "UPDATE pasien SET $column = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $fileName, $id_pasien);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "File berhasil diupload.";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan data ke database.";
    }

    $stmt->close();
    header("Location: pasien.php");
    exit;
} else {
    $_SESSION['error_message'] = "Metode permintaan tidak valid.";
    header("Location: pasien.php");
    exit;
}
?>
