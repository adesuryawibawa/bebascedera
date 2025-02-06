<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika belum login
    exit;
}

// Cek apakah level pengguna adalah "hrd"
if ($_SESSION['level'] !== 'hrd') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan hrd
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


// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Set timezone sesuai kebutuhan Anda, misalnya Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Dapatkan tanggal dan waktu saat ini
$currentDate = date('l, d F Y'); // Contoh: Tuesday, 04 October 2024
$currentTime = date('g:i a'); // Misalnya: 03:15 pm

$pageTitle = !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Profile';

// Path default gambar profil
$defaultImage = '../assets/images/faces/no-images.png';

// Cek apakah gambar profil disimpan dalam sesi dan path gambar ada di folder yang benar
if (isset($_SESSION['picture']) && !empty($_SESSION['picture'])) {
    // Path gambar berdasarkan kolom 'picture' di tabel 'users'
    $profileImagePath = '../assets/images/faces/' . $_SESSION['picture'];

    // Cek apakah file gambar yang disebutkan di 'picture' ada di folder ../assets/images/faces/
    if (file_exists($profileImagePath)) {
        $profileImage = $profileImagePath;  // Jika gambar ditemukan, gunakan gambar tersebut
    } else {
        $profileImage = $defaultImage;  // Jika gambar tidak ditemukan, gunakan gambar default
    }
} else {
    // Jika kolom 'picture' kosong atau tidak ada, gunakan gambar default
    $profileImage = $defaultImage;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>	 
	<script src="../assets/js/jquery-3.7.1.min.js"></script>

	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
	<!-- DataTables Responsive CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

	<!-- DataTables JS -->
	<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
	<!-- DataTables Responsive JS -->
	<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
	
	<link rel="shortcut icon" href="../assets/images/logo-bebascedera.png" />
</head>


  <body>
   <div class="container-scroller">
	  <?php include '_navbar.php'; ?>
      <div class="container-fluid page-body-wrapper">
		<?php include '_sidebar.php'; ?>
        <div class="main-panel">
          <div class="content-wrapper">
		 