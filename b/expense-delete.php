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

// Cek apakah level pengguna adalah Admin Cabang
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $receipt_image = $_POST['receipt_image'] ?? '';

    if ($id > 0) {
        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Hapus file fisik jika ada
        if ($receipt_image && file_exists("../assets/expenses/$receipt_image")) {
            unlink("../assets/expenses/$receipt_image");
        }

        $_SESSION['success_message'] = "Data berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal hapus, Invalid ID";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus, invalid request method";
}

// Redirect kembali ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;