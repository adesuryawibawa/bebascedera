<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: expense-category.php");
    exit;
}

// Validasi level pengguna
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: expense-category.php");
    exit;
}

// Cek CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: expense-category.php");
    exit;
}

// Include konfigurasi database
include '../includes/config.php';

// Ambil data dari form
$id = intval($_POST['id'] ?? 0);
$id_cabang = $_SESSION['cabang'];

// Validasi input
if ($id <= 0) {
    $_SESSION['error_message'] = "ID kategori tidak valid.";
    header("Location: expense-category.php");
    exit;
}

try {
    // Hapus kategori dari database
    $queryDelete = "DELETE FROM expense_categories WHERE id = ? AND id_cabang = ?";
    $stmtDelete = $conn->prepare($queryDelete);
    $stmtDelete->bind_param("ii", $id, $id_cabang);

    if ($stmtDelete->execute()) {
        if ($stmtDelete->affected_rows > 0) {
            $_SESSION['success_message'] = "Kategori berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Kategori tidak ditemukan atau tidak dapat dihapus.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal menghapus kategori.";
    }

    $stmtDelete->close();
    $conn->close();

    header("Location: expense-category.php");
    exit;
} catch (mysqli_sql_exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: expense-category.php");
    exit;
}
?>
