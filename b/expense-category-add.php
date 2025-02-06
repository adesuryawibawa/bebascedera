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
$name = trim($_POST['kategori'] ?? '');
$id_cabang = $_SESSION['cabang'];

// Validasi input
if (empty($name)) {
    $_SESSION['error_message'] = "Kategori harus diisi.";
    header("Location: expense-category.php");
    exit;
}

try {
    // Cek apakah deskripsi sudah ada
    $queryCheck = "SELECT COUNT(*) FROM expense_categories WHERE name = ? AND id_cabang = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("si", $name, $id_cabang);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count > 0) {
        $_SESSION['error_message'] = "Kategori yang sama sudah ada.";
        header("Location: expense-category.php");
        exit;
    }

    // Tambah kategori ke database
    $queryInsert = "INSERT INTO expense_categories (name, id_cabang, created_at) VALUES (?, ?, NOW())";
    $stmtInsert = $conn->prepare($queryInsert);
    $stmtInsert->bind_param("si", $name, $id_cabang);

    if ($stmtInsert->execute()) {
        $_SESSION['success_message'] = "Kategori berhasil ditambahkan.";
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan kategori.";
    }

    $stmtInsert->close();
    $conn->close();

    header("Location: expense-category.php");
    exit;
} catch (mysqli_sql_exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: expense-category.php");
    exit;
}
?>
