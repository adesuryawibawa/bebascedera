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

// Cek apakah level pengguna adalah "BOD"
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Validasi CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: cabang.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$nama_cabang = trim($_POST['nama_cabang'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$kota = trim($_POST['kota'] ?? '');
$link_google_map = trim($_POST['link_google_map'] ?? '');
$jam_buka = trim($_POST['jam_buka'] ?? '');
$jam_tutup = trim($_POST['jam_tutup'] ?? '');
$kapasitas_bed = (int)($_POST['kapasitas_bed'] ?? 0);
$kontak_cabang = trim($_POST['kontak_cabang'] ?? '');
$pic = trim($_POST['pic'] ?? '');

// Validasi data input
$errors = [];
if (empty($nama_cabang)) $errors[] = "Nama cabang wajib diisi.";
if (empty($alamat)) $errors[] = "Alamat wajib diisi.";
if (empty($kota)) $errors[] = "Kota wajib diisi.";
if (empty($jam_buka)) $errors[] = "Jam buka wajib diisi.";
if (empty($jam_tutup)) $errors[] = "Jam tutup wajib diisi.";
if ($kapasitas_bed <= 0) $errors[] = "Kapasitas bed harus lebih dari 0.";
if (!empty($kontak_cabang) && !preg_match('/^[0-9]+$/', $kontak_cabang)) {
    $errors[] = "Kontak cabang harus berupa angka.";
}
if (!empty($link_google_map) && !filter_var($link_google_map, FILTER_VALIDATE_URL)) {
    $errors[] = "Link Google Map harus berupa URL yang valid.";
}

// Validasi nama cabang tidak duplikat
$stmt = $conn->prepare("SELECT COUNT(*) FROM cabang WHERE nama_cabang = ?");
$stmt->bind_param("s", $nama_cabang);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    $errors[] = "Nama cabang sudah digunakan. Silakan pilih nama lain.";
}

// Jika ada error, kembali ke halaman sebelumnya
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: cabang.php");
    exit;
}

// Masukkan data ke database
$sql = "INSERT INTO cabang (nama_cabang, alamat, kota, link_google_map, jam_buka, jam_tutup, kapasitas_bed, kontak_cabang, pic) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssiss",
    $nama_cabang,
    $alamat,
    $kota,
    $link_google_map,
    $jam_buka,
    $jam_tutup,
    $kapasitas_bed,
    $kontak_cabang,
    $pic
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Cabang berhasil ditambahkan.";

    // Log aktivitas
    $log_message = sprintf(
        "[%s] User '%s' menambahkan cabang: %s (%s)",
        date('Y-m-d H:i:s'),
        $_SESSION['username'] ?? 'unknown',
        $nama_cabang,
        $kota
    );
    error_log($log_message . PHP_EOL, 3, "../logs/activity.log");
} else {
    $_SESSION['error_message'] = "Terjadi kesalahan saat menambahkan cabang. Silakan coba lagi.";
}

// Tutup koneksi
$stmt->close();
$conn->close();

// Redirect kembali ke halaman cabang
header("Location: cabang.php");
exit;
?>
