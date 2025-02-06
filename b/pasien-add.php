<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: pasien.php");
    exit;
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: pasien.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$nama = trim($_POST['nama']);
$jenis_kelamin = trim($_POST['jenis_kelamin']);
$tempat_lahir = trim($_POST['tempat_lahir']);
$tanggal_lahir = trim($_POST['tanggal_lahir']);
$nomor_wa = trim($_POST['nomor_wa']);
$email = trim($_POST['email']);
$alamat = trim($_POST['alamat']);
$keterangan = trim($_POST['keterangan'] ?? null);
$id_cabang = $_SESSION['cabang']; // Ambil id_cabang dari session
$user_input = $_SESSION['username']; // Ambil nama user dari session

// Validasi input
$errors = [];
if (empty($nama)) $errors[] = "Nama wajib diisi.";
if (empty($jenis_kelamin)) $errors[] = "Jenis kelamin wajib diisi.";
if (empty($nomor_wa)) $errors[] = "Nomor WA wajib diisi.";
if (empty($id_cabang)) $errors[] = "ID cabang tidak valid.";

if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: pasien.php");
    exit;
}

try {
    // Lakukan insert data pasien
    $sql_insert = "INSERT INTO pasien (nama, jenis_kelamin, tempat_lahir, tanggal_lahir, nomor_wa, email, alamat, keterangan, id_cabang, user_input, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssssssss", $nama, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $nomor_wa, $email, $alamat, $keterangan, $id_cabang, $user_input);

    if ($stmt_insert->execute()) {
        $_SESSION['success_message'] = "Pasien berhasil ditambahkan.";
    } else {
        throw new Exception("Gagal menambahkan pasien.");
    }

    $stmt_insert->close();
} catch (mysqli_sql_exception $e) {
    // Tangani error duplicate entry
    if ($e->getCode() === 1062) {
        $_SESSION['error_message'] = "Pasien dengan Nomor WA atau Email ini sudah ada di database.";
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

$conn->close();
header("Location: pasien.php");
exit;
?>
