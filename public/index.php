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

// Cek level akses
if ($_SESSION['level'] !== 'customer') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 30 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}

include '_header.php'; // Header halaman
?>

<div class="row">
  <div class="col-md-12 grid-margin">
	<div class="row">
	  <div class="col-12 col-xl-8 mb-4 mb-xl-0">
		<h3 class="font-weight-bold">Dashboard</h3>
		<h6 class="font-weight-normal mb-0">All systems are running smoothly.</h6>
	  </div>
	</div>
  </div>
</div>

<?php include '_footer.php'; // Footer halaman ?>
