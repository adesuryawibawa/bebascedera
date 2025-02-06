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
$kode_promo = trim($_POST['kode_promo'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$tipe_diskon = trim($_POST['tipe_diskon'] ?? '');
$nilai_diskon = floatval($_POST['diskon'] ?? 0);
$id_produk = intval($_POST['id_produk'] ?? 0);
$berlaku_mulai = str_replace('T', ' ', trim($_POST['mulai'] ?? '')) . ':00';
$berlaku_sampai = str_replace('T', ' ', trim($_POST['berakhir'] ?? '')) . ':00';

// Validasi input
$errors = [];
if ($id_promo <= 0) $errors[] = "ID promo tidak valid.";
if (empty($kode_promo)) $errors[] = "Kode promo wajib diisi.";
if (empty($deskripsi)) $errors[] = "Deskripsi wajib diisi.";
if ($nilai_diskon <= 0) $errors[] = "Nilai diskon harus lebih besar dari 0.";
if (!in_array($tipe_diskon, ['persen', 'nominal'])) $errors[] = "Tipe diskon tidak valid.";
if (strtotime($berlaku_mulai) > strtotime($berlaku_sampai)) $errors[] = "Tanggal mulai tidak boleh lebih besar dari tanggal berakhir.";

// Jika ada error
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: coupon.php");
    exit;
}

// Update data di database
$sql = "UPDATE promo 
        SET kode_promo = ?, deskripsi = ?, tipe_diskon = ?, nilai_diskon = ?, berlaku_mulai = ?, berlaku_sampai = ?, id_produk = ?, created_at = NOW()
        WHERE id = ? AND id_cabang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssis", $kode_promo, $deskripsi, $tipe_diskon, $nilai_diskon, $berlaku_mulai, $berlaku_sampai, $id_produk, $id_promo, $_SESSION['cabang']);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Promo berhasil diperbarui.";
} else {
    $_SESSION['error_message'] = "Gagal memperbarui promo.";
}

$stmt->close();
$conn->close();
header("Location: coupon.php");
exit;
