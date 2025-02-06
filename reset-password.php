<?php
ini_set('session.cookie_httponly', 1);  // Cegah akses JavaScript ke cookie
ini_set('session.cookie_secure', 1);    // Pastikan cookie hanya dikirim melalui HTTPS
ini_set('session.use_strict_mode', 1);  // Batasi penggunaan session ID hanya dari server yang valid

session_start();
session_regenerate_id(true); // Regenerasi ID session untuk mencegah session hijacking

// HTTPS enforcement, kecuali di localhost
if (!isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Membuat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time(); // Simpan waktu token dibuat
}

// Validasi token di URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    echo "Token tidak valid.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bebas Cedera - Reset Password</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/logo-samsaka-ico.png" />
</head>
<body>
<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
            <div class="row w-100 mx-0">
                <div class="col-lg-4 mx-auto">
                    <div class="auth-form-light text-left py-5 px-4 px-sm-5">
                        <div class="brand-logo">
                            <img src="assets/images/logo-bebascedera.png" alt="logo">
                        </div>
                        <h4>Reset Password</h4>
						
                        <?php
                        // Tampilkan pesan error di bawah <h4>
                        if (isset($_SESSION['error_message'])) {
                            echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
                            unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
                        }
                        ?>
						
						<form action="reset-password-process.php" method="POST" id="reset-form">
						  <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
						  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
						  
						  <div class="form-group">
							  <label for="new_password">Masukkan Kata Sandi Baru:</label>
							  <input type="password" id="new_password" name="new_password" class="form-control form-control-lg" maxlength="50" required />
						  </div>
						  <div class="form-group">
							  <label for="confirm_password">Konfirmasi Kata Sandi Baru:</label>
							  <input type="password" id="confirm_password" maxlength="50" name="confirm_password" class="form-control form-control-lg" required />
						  </div>
						  <button type="submit" class="btn btn-block btn-danger btn-lg font-weight-medium auth-form-btn">Reset Password</button>
						</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>

<script>
  document.getElementById('reset-form').addEventListener('submit', function(e) {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
          alert('Password harus memiliki minimal 8 karakter, termasuk satu huruf besar dan satu angka.');
          e.preventDefault();
      }

      if (newPassword !== confirmPassword) {
          alert('Password dan konfirmasi password tidak cocok.');
          e.preventDefault();
      }
  });
</script>

  <script>
    // Mencegah klik kanan di seluruh halaman
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
    });
</script>

</body>
</html>
