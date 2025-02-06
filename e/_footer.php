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

// Cek apakah level pengguna adalah "employee"
if ($_SESSION['level'] !== 'employee') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan employee
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

</div>
 
	<footer class="footer">
	  <div class="d-sm-flex justify-content-center justify-content-sm-between">
		<span class="text-muted text-center text-sm-left d-block d-sm-inline-block">&copy; 2024 Ade Surya Wibawa. All Rights Reserved.</span>
		<span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">www.bebascedera.com</span>
	  </div>
	</footer>

 </div>
      </div>
    </div>
    <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="../assets/js/off-canvas.js"></script>
    <script src="../assets/js/template.js"></script>
    <script src="../assets/js/settings.js"></script>
    <script src="../assets/js/todolist.js"></script>
	<script>
	// Disable right-click
	//document.addEventListener('contextmenu', function(event) {
	//	event.preventDefault(); 
	//});
	</script>
  </body>
</html>
