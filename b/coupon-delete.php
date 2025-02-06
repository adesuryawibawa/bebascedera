<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: coupon.php");
    exit;
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: coupon.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$id_promo = intval($_POST['id_promo'] ?? 0);

// Validasi input
if ($id_promo <= 0) {
    $_SESSION['error_message'] = "ID promo tidak valid.";
    header("Location: coupon.php");
    exit;
}

// Hapus promo dari database
$sql = "DELETE FROM promo WHERE id = ? AND id_cabang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_promo, $_SESSION['cabang']);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Promo berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Promo tidak ditemukan atau tidak dapat dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus promo.";
}

$stmt->close();
$conn->close();
header("Location: coupon.php");
exit;
