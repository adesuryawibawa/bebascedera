<?php
ini_set('session.cookie_httponly', 1);  // Mencegah akses JavaScript ke cookie
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bebas Cedera - Lupa Password</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/logo-bebascedera.png" />
    
    
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
                        // Tampilkan pesan sukses
                        if (isset($_SESSION['success_message'])) {
                            echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
                            unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
                        }

                        // Tampilkan pesan error
                        if (isset($_SESSION['error_message'])) {
                            echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
                            unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
                        }
                        ?>

                        <form action="forgot-password-process.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="form-group">
                                <label for="username">Masukkan Username :</label>
                                <input type="text" id="text" class="form-control form-control-lg" name="username" placeholder="Username" required />
                            </div>
                            <div class="mt-3 d-grid gap-2">
                                <button type="submit" class="btn btn-block btn-teal btn-lg font-weight-medium auth-form-btn">Reset Password</button>
                            </div>
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
        // Mencegah klik kanan di seluruh halaman
        document.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });
    </script>
</body>
</html>
