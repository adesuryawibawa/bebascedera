<?php
session_start(); // Mulai session untuk menyimpan pesan

include 'includes/config.php';
include 'includes/api-wa-old.php';


date_default_timezone_set('Asia/Jakarta'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Permintaan tidak valid.";
        header('Location: forgot-password.php');
        exit();
    }

    // Validasi input
    $username = trim($_POST['username']);
    if (empty($username)) {
        $_SESSION['error_message'] = "Silakan masukkan username !";
        header('Location: forgot-password.php');
        exit();
    }

	$sql = "SELECT * FROM users WHERE username = ? AND flagactive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
	
	
    if (!$user) {
        // Jika user tidak ditemukan, simpan pesan error ke session
        $_SESSION['error_message'] = "Username tidak ditemukan.";
    } else {
        // Generate token reset password
        $token = bin2hex(random_bytes(32)); // Token 64 karakter (32 byte)
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token berlaku selama 1 jam

        // Simpan token ke tabel password_resets
        $insertTokenSql = "INSERT INTO password_resets (username, token, expires_at) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertTokenSql);
        $insertStmt->bind_param('sss', $user['username'], $token, $expiry);
        $insertStmt->execute();

        // Kirim link reset password via WhatsApp
        $resetLink = "https://klinik.bebascedera.com/reset-password.php?token=" . $token;
        $contactNumber = $user['phone']; // Pastikan nomor telepon dalam format internasional
        $message = "Hai {$user['fullname']}, klik tautan berikut untuk mereset kata sandi Anda:\n\n" . $resetLink . "\n\nTautan ini berlaku selama 1 jam.";

        // Panggil fungsi untuk mengirimkan pesan via WhatsApp
        $sendResult = sendWhatsAppMessage($contactNumber, $message);

        if ($sendResult) {
            // Pesan berhasil terkirim, simpan pesan sukses ke session
            $_SESSION['success_message'] = "Link reset password telah dikirim ke WhatsApp Anda.";
            // Redirect ke halaman login setelah proses berhasil
            header('Location: login.php');
            exit();
        } else {
            // Jika gagal mengirim pesan, simpan pesan error ke session
            $_SESSION['error_message'] = "Gagal mengirim pesan via WhatsApp.";
            // Redirect ke halaman forgot-password untuk menunjukkan pesan error
            header('Location: forgot-password.php');
            exit();
        }

    }

    // Tutup statement dan koneksi
    $stmt->close();
    $conn->close();

    // Redirect ke halaman login setelah proses
    header('Location: login.php');
    exit(); // Hentikan eksekusi script setelah redirect
}
?>
