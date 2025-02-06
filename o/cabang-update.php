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
$id_cabang = (int)($_POST['id_cabang'] ?? 0);
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
if ($id_cabang <= 0) $errors[] = "ID Cabang tidak valid.";
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

// Cek apakah nama cabang sudah digunakan oleh cabang lain
$check_sql = "SELECT COUNT(*) AS total FROM cabang WHERE nama_cabang = ? AND id_cabang != ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $nama_cabang, $id_cabang);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

if ($check_row['total'] > 0) {
    $errors[] = "Nama cabang sudah digunakan oleh cabang lain.";
}
$check_stmt->close();

// Jika ada error, kembali ke halaman sebelumnya
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: cabang.php");
    exit;
}

// Perbarui data di database
$update_sql = "UPDATE cabang 
               SET nama_cabang = ?, alamat = ?, kota = ?, link_google_map = ?, jam_buka = ?, jam_tutup = ?, kapasitas_bed = ?, kontak_cabang = ?, pic = ?
               WHERE id_cabang = ?";

$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param(
    "ssssssissi",
    $nama_cabang,
    $alamat,
    $kota,
    $link_google_map,
    $jam_buka,
    $jam_tutup,
    $kapasitas_bed,
    $kontak_cabang,
    $pic,
    $id_cabang
);

if ($update_stmt->execute()) {
    $_SESSION['success_message'] = "Cabang berhasil diperbarui.";

    // Log aktivitas
    $log_message = sprintf(
        "[%s] User '%s' memperbarui cabang: %s (ID: %d)",
        date('Y-m-d H:i:s'),
        $_SESSION['username'] ?? 'unknown',
        $nama_cabang,
        $id_cabang
    );
    error_log($log_message . PHP_EOL, 3, "../logs/activity.log");
} else {
    $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui cabang. Silakan coba lagi.";
}

// Tutup koneksi
$update_stmt->close();
$conn->close();

// Redirect kembali ke halaman cabang
header("Location: cabang.php");
exit;
?>
