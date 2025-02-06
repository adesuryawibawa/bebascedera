<?php
include '_header.php';
// Memeriksa apakah sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
	// Cek apakah pengguna sudah login
	if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
		header("Location: ../login.php?error=not_logged_in"); // Redirect ke halaman login jika belum login
		exit;
	}

	// Cek apakah level pengguna adalah "user"
	if ($_SESSION['level'] !== 'user') {
		header("Location: ../login.php?error=unauthorized"); // Redirect ke halaman login jika bukan user
		exit;
	}
}
?>


<?php include '_footer.php'; ?>