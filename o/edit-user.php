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

// CSRF token validation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "CSRF token tidak valid.";
        header("Location: users.php");
        exit;
    }
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$userId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$level = isset($_POST['level']) ? trim($_POST['level']) : '';
$cabang = isset($_POST['cabang']) ? trim($_POST['cabang']) : '';

// Validasi userId
if (!$userId) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: users.php");
    exit;
}

// Validasi level: Hanya izinkan 'customer','employee', 'cabang','cs','hrd'
$allowed_levels = ['customer','employee', 'cabang','cs','hrd'];
if (!in_array($level, $allowed_levels)) {
    $_SESSION['error_message'] = "Level user ini tidak bisa diedit";
    header("Location: users.php");
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Email tidak valid.";
    header("Location: users.php");
    exit;
}

// Validasi fullname, email, phone
if (strlen($fullname) > 50 || !preg_match('/^[a-zA-Z0-9\s_]+$/', $fullname)) {
    $_SESSION['error_message'] = "Nama lengkap hanya boleh berisi huruf, angka, spasi, dan _ maksimal 50 karakter.";
    header("Location: users.php");
    exit;
}

if (!preg_match('/^[0-9]+$/', $phone) || strlen($phone) > 14) {
    $_SESSION['error_message'] = "Nomor telepon harus angka dan maksimal 14 karakter.";
    header("Location: users.php");
    exit;
}

// Update data user di database
$stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, level = ?, id_cabang = ? WHERE id = ?");
if ($stmt === false) {
    $_SESSION['error_message'] = "Terjadi kesalahan saat mempersiapkan statement.";
    header("Location: users.php");
    exit;
}

// Gunakan 's' (string) untuk level, karena level adalah tipe ENUM
$stmt->bind_param("sssssi", $fullname, $email, $phone, $level,$cabang, $userId);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Data user berhasil diperbarui.";
} else {
    $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui data user.";
}

$stmt->close();
$conn->close();

// Redirect kembali ke halaman users
header("Location: users.php");
exit;
?>
