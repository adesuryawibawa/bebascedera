echo "test";
exit();

<?php
// Memulai sesi jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login, jika belum redirect ke login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Redirect berdasarkan level user yang disimpan di session
if (isset($_SESSION['level'])) {
    switch ($_SESSION['level']) {
        case 'bod':
            header("Location: o/index.php");
            break;
        case 'cabang':
            header("Location: b/index.php");
            break;
        case 'cs':
            header("Location: cs/index.php");
            break;
		case 'customer':
            header("Location: public/index.php");
            break;
        default:
            // Jika level tidak valid, redirect ke halaman login
            header("Location: login.php?error=invalid_user_level");
            exit;
    }
} else {
    // Jika level tidak ada di sesi, redirect ke halaman login
    header("Location: login.php?error=no_user_level");
    exit;
}
?>
