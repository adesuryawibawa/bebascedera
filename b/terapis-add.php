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

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token.";
    header("Location: terapis.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$id_user = $_POST['id_user'] ?? null;
$id_cabang = $_SESSION['cabang'] ?? null;

// Validasi input
if (empty($id_user) || empty($id_cabang)) {
    $_SESSION['error_message'] = "Semua data wajib diisi.";
    header("Location: terapis.php");
    exit;
}

try {
    // Ambil nama terapis berdasarkan id_user
    $sqlGetUser = "SELECT fullname FROM users WHERE id = ? AND id_cabang = ? ";
    $stmtGetUser = $conn->prepare($sqlGetUser);
    $stmtGetUser->bind_param("ii", $id_user, $id_cabang);
    $stmtGetUser->execute();
    $resultGetUser = $stmtGetUser->get_result();
    $user = $resultGetUser->fetch_assoc();

    if (!$user) {
        $_SESSION['error_message'] = "User tidak ditemukan atau tidak valid untuk ditambahkan sebagai terapis.";
        header("Location: terapis.php");
        exit;
    }

    $nama_terapis = $user['fullname'];

    // Cek apakah id_user sudah ada di tabel terapis
    $sqlCheck = "SELECT COUNT(*) AS count FROM terapis WHERE id_user = ? AND id_cabang = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $id_user, $id_cabang);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();

    if ($rowCheck['count'] > 0) {
        $_SESSION['error_message'] = "Terapis ini sudah ditambahkan sebelumnya.";
        header("Location: terapis.php");
        exit;
    }

    // Jika belum ada, tambahkan ke tabel terapis
    $sqlInsert = "INSERT INTO terapis (id_user, id_cabang, nama_terapis, created_at) VALUES (?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("iis", $id_user, $id_cabang, $nama_terapis);

    if ($stmtInsert->execute()) {
        $_SESSION['success_message'] = "Terapis berhasil ditambahkan.";
    } else {
        throw new Exception("Gagal menambahkan terapis: " . $stmtInsert->error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan saat memproses permintaan.";
}

header("Location: terapis.php");
exit;
