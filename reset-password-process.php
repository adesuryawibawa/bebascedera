<?php
session_start(); // Mulai session untuk menyimpan pesan

// Koneksi ke database
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Permintaan tidak valid.";
        header('Location: reset-password.php?token=' . $_POST['token']);
        exit();
    }

    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        // Simpan pesan error ke dalam session dan redirect ke halaman reset password
        $_SESSION['error_message'] = "Password tidak cocok.";
        header('Location: reset-password.php?token=' . $token);
        exit();
    }

    // Validasi kekuatan password
    if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $_SESSION['error_message'] = "Password harus memiliki minimal 8 karakter, termasuk satu huruf besar dan satu angka.";
        header('Location: reset-password.php?token=' . $token);
        exit();
    }

    // Cari token di tabel password_resets
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if (!$reset) {
        // Simpan pesan error ke dalam session dan redirect ke halaman reset password
        $_SESSION['error_message'] = "Token tidak valid atau sudah kadaluarsa.";
        header('Location: reset-password.php?token=' . $token);
        exit();
    } else {
        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password di tabel users
        $updateSql = "UPDATE users SET password = ? WHERE username = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('ss', $hashed_password, $reset['username']);
        $updateStmt->execute();

        // Hapus token setelah password direset
        $deleteTokenSql = "DELETE FROM password_resets WHERE username = ?";
        $deleteTokenStmt = $conn->prepare($deleteTokenSql);
        $deleteTokenStmt->bind_param('s', $reset['username']);
        $deleteTokenStmt->execute();

        // Simpan pesan sukses ke dalam session dan redirect ke halaman login
        $_SESSION['success_message'] = "Password berhasil direset. Silakan login dengan password baru Anda.";
        header('Location: login.php');
        exit();
    }

    $stmt->close();
}
$conn->close();
?>
