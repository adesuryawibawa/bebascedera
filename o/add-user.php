<?php
session_start(); // Memulai sesi

include '../includes/config.php'; // Koneksi ke database

// Fungsi untuk memvalidasi input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

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
        // Ambil dan sanitasi input
        $username = sanitizeInput($_POST['username']);
        $fullname = sanitizeInput($_POST['fullname']);
        $email = filter_var(sanitizeInput($_POST['email']), FILTER_VALIDATE_EMAIL) ? sanitizeInput($_POST['email']) : null;
        $phone = sanitizeInput($_POST['phone']);
        $password = sanitizeInput($_POST['password']);
        $level = sanitizeInput($_POST['level']);
		$cabang = sanitizeInput($_POST['cabang']);
		
        // Validasi input
        if (empty($username) || empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($level) || $cabang === null || $cabang === '') {
            redirectWithError('Semua field harus diisi!');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            redirectWithError('Username hanya boleh berisi huruf, angka, dan _.');
        }
       if (!preg_match('/^[a-zA-Z0-9\s]+$/', $fullname)) {
			redirectWithError('Full Name hanya boleh berisi huruf, angka, dan spasi.');
		}
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithError('Email tidak valid.');
        }
        if (!preg_match('/^[0-9]+$/', $phone) || strlen($phone) > 15) {
            redirectWithError('Nomor telepon harus angka dan maksimal 15 karakter.');
        }
        if (strlen($password) < 6) {
            redirectWithError('Password harus minimal 6 karakter.');
        }

        // Cek apakah username atau email sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            throw new Exception('Kesalahan pada database.');
        }
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirectWithError('Username atau email sudah digunakan.');
        }
        $stmt->close();

        // Hash password sebelum menyimpan ke database
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Siapkan data default untuk user baru
        $flagactive = 1; // Set user sebagai aktif
        $picture = null; // Tidak ada gambar profil
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');

        // Insert data ke dalam database
        $stmt = $conn->prepare("INSERT INTO users (username, fullname, email, phone, flagactive, picture, password, level,id_cabang, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Kesalahan pada database saat menyimpan.');
        }
        $stmt->bind_param("ssssissssss", $username, $fullname, $email, $phone, $flagactive, $picture, $hashed_password, $level, $cabang, $created_at, $updated_at);

        // Eksekusi query
        if ($stmt->execute()) {
            $stmt->close();
            redirectWithSuccess('User baru berhasil ditambahkan.');
        } else {
            $stmt->close();
            throw new Exception('Gagal menambahkan user. Silakan coba lagi.');
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
