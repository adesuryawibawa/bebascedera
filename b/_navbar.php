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

// Cek apakah level pengguna adalah "Admin Cabang"
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan Admin Cabang
    exit;
}

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 120 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}

?>
<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <a class="navbar-brand brand-logo me-5" href="index.php"><img src="../assets/images/logo-bebascedera.png" class="me-2" style="width: 125px; height: auto;" alt="logo" /></a>
    <a class="navbar-brand brand-logo-mini" href="index.php"><img src="../assets/images/logo-bebascedera.png" alt="logo" /></a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="icon-menu"></span>
    </button>
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item nav-settings d-none d-lg-flex">
        <a class="nav-link" href="#">
          <i class="icon-ellipsis"></i>
        </a>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="icon-menu"></span>
    </button>
  </div>
</nav>