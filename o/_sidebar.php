<?php
// Memeriksa apakah sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
	// Cek apakah pengguna sudah login
	if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
		header("Location: ../login.php?error=not_logged_in"); // Redirect ke halaman login jika belum login
		exit;
	}

	// Cek apakah level pengguna adalah "BOD"
	if ($_SESSION['level'] !== 'bod') {
		header("Location: ../login.php?error=unauthorized"); // Redirect ke halaman login jika bukan BOD
		exit;
	}
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
      <a class="nav-link" href="cabang.php">
        <i class="fa fa-store menu-icon"></i>
        <span class="menu-title">Cabang </span>
      </a>
    </li>
	<li class="nav-item">
      <a class="nav-link" href="users.php">
        <i class="fa fa-users menu-icon"></i>
        <span class="menu-title">Users </span>
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
