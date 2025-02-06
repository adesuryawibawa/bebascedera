<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Unauthorized access.");
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token.");
}

include '../includes/config.php';

// Ambil data dari form
$kode_promo = trim($_POST['kode_promo'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$tipe_diskon = trim($_POST['tipe_diskon'] ?? '');
$nilai_diskon = floatval($_POST['diskon'] ?? 0);
$berlaku_mulai = str_replace('T', ' ', trim($_POST['mulai'] ?? '')) . ':00';
$berlaku_sampai = str_replace('T', ' ', trim($_POST['berakhir'] ?? '')) . ':00';
$id_produk = intval($_POST['id_produk'] ?? 0);
$id_cabang = $_SESSION['cabang'] ?? null;
$user_input = $_SESSION['username'] ?? null; // Ambil nama user dari session

// Validasi input
$errors = [];
if (empty($kode_promo)) $errors[] = "Kode promo wajib diisi.";
if (empty($deskripsi)) $errors[] = "Deskripsi wajib diisi.";
if ($nilai_diskon <= 0) $errors[] = "Nilai diskon harus lebih besar dari 0.";
if (!in_array($tipe_diskon, ['persen', 'nominal'])) $errors[] = "Tipe diskon tidak valid.";
if (empty($berlaku_mulai) || !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $berlaku_mulai)) $errors[] = "Format tanggal mulai tidak valid.";
if (empty($berlaku_sampai) || !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $berlaku_sampai)) $errors[] = "Format tanggal berakhir tidak valid.";
if (strtotime($berlaku_mulai) > strtotime($berlaku_sampai)) $errors[] = "Tanggal mulai tidak boleh lebih besar dari tanggal berakhir.";
if (!$id_cabang) $errors[] = "Cabang tidak valid.";
if ($id_produk <= 0) $errors[] = "Produk harus dipilih.";

// Jika ada error
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: coupon.php");
    exit;
}

// Cek duplikasi kode promo
$sql_check = "SELECT COUNT(*) AS count FROM promo WHERE kode_promo = ? AND id_cabang = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("si", $kode_promo, $id_cabang);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();
if ($row_check['count'] > 0) {
    $_SESSION['error_message'] = "Kode promo sudah ada untuk cabang ini.";
    header("Location: coupon.php");
    exit;
}

// Masukkan data ke database
$sql = "INSERT INTO promo (id_cabang, kode_promo, deskripsi, tipe_diskon, nilai_diskon, berlaku_mulai, berlaku_sampai, id_produk, user_input, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssssis", $id_cabang, $kode_promo, $deskripsi, $tipe_diskon, $nilai_diskon, $berlaku_mulai, $berlaku_sampai, $id_produk, $user_input);

if (!$stmt->execute()) {
    error_log("MySQL Error: " . $stmt->error);
    $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan promo.";
    header("Location: coupon.php");
    exit;
}

// Sukses
$_SESSION['success_message'] = "Promo berhasil ditambahkan.";
$stmt->close();
$conn->close();
header("Location: coupon.php");
exit;
