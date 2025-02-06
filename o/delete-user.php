<?php
session_start(); // Memulai sesi

include '../includes/config.php'; // Koneksi ke database

// Fungsi untuk mengarahkan dengan pesan error menggunakan session
function redirectWithError($message) {
    $_SESSION['error_message'] = $message;
    header("Location: users.php");
    exit;
}

// Fungsi untuk mengarahkan dengan pesan sukses menggunakan session
function redirectWithSuccess($message) {
    $_SESSION['success_message'] = $message;
    header("Location: users.php");
    exit;
}

// Validasi apakah request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek apakah CSRF token valid
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithError('CSRF token tidak valid.');
    }

    // Mulai try-catch untuk menangani error
    try {
        // Ambil dan sanitasi input (ID user yang akan dihapus)
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            redirectWithError('ID user tidak valid.');
        }

        $userId = intval($_POST['id']); // Mengubah ID menjadi integer untuk mencegah SQL Injection

        // Validasi bahwa user yang akan dihapus bukan superadmin
        $stmt = $conn->prepare("SELECT level FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Kesalahan pada database.');
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($level);
        $stmt->fetch();
        $stmt->close();

        // Cek apakah user yang akan dihapus adalah superadmin
        if ($level === 'bod') {
            redirectWithError('Superadmin tidak dapat dihapus.');
        }
        // Hapus user dari database
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Kesalahan pada database saat menghapus user.');
        }
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithSuccess('User berhasil dihapus.');
        } else {
            $stmt->close();
            throw new Exception('Gagal menghapus user. Silakan coba lagi.');
        }

    } catch (Exception $e) {
        // Jika terjadi kesalahan, simpan pesan kesalahan ke session dan arahkan kembali
        redirectWithError($e->getMessage());
    }
} else {
    // Jika bukan POST, kirimkan error
    redirectWithError('Metode request tidak valid.');
}
?>