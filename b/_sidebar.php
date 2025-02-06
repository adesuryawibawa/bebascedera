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
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item">
      <a class="nav-link" href="index.php">
        <i class="icon-grid menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
	<li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
        <i class="fa fa-calendar-minus-o menu-icon"></i>
        <span class="menu-title">Booking</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="auth">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"> <a class="nav-link" href="booking-add.php"> Buat Booking </a></li>
          <li class="nav-item"> <a class="nav-link" href="booking-data.php"> Data Booking </a></li>
        </ul>
      </div>
    </li>
	<li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#exp" aria-expanded="false" aria-controls="exp">
        <i class="fa fa-exchange menu-icon"></i>
        <span class="menu-title">Expenses</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="exp">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"> <a class="nav-link" href="expense.php"> Input Expense </a></li>
          <li class="nav-item"> <a class="nav-link" href="expense-category.php"> Category </a></li>
        </ul>
      </div>
    </li>
	<li class="nav-item">
      <a class="nav-link" href="products.php">
        <i class="fa fa-tags menu-icon"></i>
        <span class="menu-title">Produk</span>
      </a>
    </li> 
	<li class="nav-item">
      <a class="nav-link" href="coupon.php">
        <i class="fa fa-percent menu-icon"></i>
        <span class="menu-title">Kupon</span>
      </a>
    </li> 
	<li class="nav-item">
      <a class="nav-link" href="pasien.php">
        <i class="fa fa-address-book menu-icon"></i>
        <span class="menu-title">Pasien</span>
      </a>
    </li> 
	<li class="nav-item">
      <a class="nav-link" href="terapis.php">
        <i class="fa fa-user-md menu-icon"></i>
        <span class="menu-title">Terapis</span>
      </a>
    </li>	
	<li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#rep" aria-expanded="false" aria-controls="rep">
        <i class="fa fa-file-text-o menu-icon"></i>
        <span class="menu-title">Report</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="rep">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"> <a class="nav-link" href="summary-report.php">Summary</a></li>
          <li class="nav-item"> <a class="nav-link" href="report-booking.php"> Report Booking </a></li>
          <li class="nav-item"> <a class="nav-link" href="report-expenses.php"> Report Expenses </a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="profile.php">
        <i class="icon-head menu-icon"></i>
        <span class="menu-title">Profile</span>
      </a>
    </li>
	<li class="nav-item">
      <a class="nav-link" href="settings.php">
        <i class="fa fa-gears menu-icon"></i>
        <span class="menu-title">Settings</span>
      </a>
    </li>
	  <li class="nav-item">
      <a class="nav-link" href="../logout.php">
        <i class="ti-power-off menu-icon"></i>
        <span class="menu-title">Logout</span>
      </a>
    </li>
  </ul>
</nav>