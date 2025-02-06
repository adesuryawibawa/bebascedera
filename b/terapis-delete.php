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
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: terapis.php");
    exit;
}

// Ambil id_terapis dari POST dan validasi
$id_terapis = filter_input(INPUT_POST, 'id_terapis', FILTER_VALIDATE_INT);

if (!$id_terapis || $id_terapis <= 0) {
    $_SESSION['error_message'] = "ID Terapis tidak valid.";
    header("Location: terapis.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

try {
    // Pastikan pengguna memiliki hak akses ke cabang ini
    $id_cabang = $_SESSION['cabang'] ?? null;
    if (!$id_cabang) {
        throw new Exception("Cabang tidak ditemukan dalam sesi.");
    }

    // Cek apakah terapis ada di database dan milik cabang yang sama
    $sqlCheck = "SELECT COUNT(*) AS count FROM terapis WHERE id = ? AND id_cabang = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) {
        throw new Exception("Prepare statement gagal: " . $conn->error);
    }

    $stmtCheck->bind_param("ii", $id_terapis, $id_cabang);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();

    if ($rowCheck['count'] == 0) {
        $_SESSION['error_message'] = "Terapis tidak ditemukan atau bukan milik cabang Anda.";
        header("Location: terapis.php");
        exit;
    }

    // Hapus data dari tabel terapis
    $sqlDelete = "DELETE FROM terapis WHERE id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    if (!$stmtDelete) {
        throw new Exception("Prepare statement gagal: " . $conn->error);
    }

    $stmtDelete->bind_param("i", $id_terapis);
    if ($stmtDelete->execute()) {
        $_SESSION['success_message'] = "Terapis berhasil dihapus.";
    } else {
        throw new Exception("Gagal menghapus terapis: " . $stmtDelete->error);
    }
} catch (Exception $e) {
    // Log error untuk debugging
    error_log("Error di terapis-delete.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan saat memproses permintaan.";
}

header("Location: terapis.php");
exit;
