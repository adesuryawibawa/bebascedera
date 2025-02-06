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
if ($_SESSION['level'] !== 'customer') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan customer
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
      <li class="nav-item nav-profile dropdown">
        <!-- Profile Image -->
        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="<?php echo $profileImage; ?>" alt="profile" class="rounded-circle" style="width: 40px; height: 40px;" />
        </a>
        <!-- Dropdown Menu -->
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li>
            <a class="dropdown-item" href="profile.php">
              <i class="ti-user text-primary"></i> Profile
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="settings.php">
              <i class="ti-settings text-primary"></i> Settings
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="../logout.php">
              <i class="ti-power-off text-primary"></i> Logout
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>