<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validasi login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Anda perlu login untuk mengakses halaman ini.']);
    exit;
}

// Validasi level pengguna
if ($_SESSION['level'] !== 'cabang') {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.']);
    exit;
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

include '../includes/config.php';

$id = intval($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
$nomor_wa = trim($_POST['nomor_wa'] ?? '');
$email = trim($_POST['email'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');
$id_cabang = $_SESSION['cabang'];

// Validasi input
if (empty($id) || empty($nama) || empty($jenis_kelamin)) {
    echo json_encode(['success' => false, 'message' => 'Nama, Jenis Kelamin, dan ID wajib diisi.']);
    exit;
}

// Update data pasien
$queryUpdate = "
    UPDATE pasien 
    SET 
        nama = ?, 
        jenis_kelamin = ?, 
        tempat_lahir = ?, 
        tanggal_lahir = ?, 
        nomor_wa = ?, 
        email = ?, 
        alamat = ?, 
        keterangan = ? 
    WHERE id = ? AND id_cabang = ?";
$stmtUpdate = $conn->prepare($queryUpdate);
$stmtUpdate->bind_param(
    "ssssssssii",
    $nama,
    $jenis_kelamin,
    $tempat_lahir,
    $tanggal_lahir,
    $nomor_wa,
    $email,
    $alamat,
    $keterangan,
    $id,
    $id_cabang
);

if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true, 'message' => 'Data pasien berhasil diperbarui.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data pasien.']);
}

$stmtUpdate->close();
$conn->close();
