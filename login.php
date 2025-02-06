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

// Durasi token CSRF aktif (misalnya 30 menit)
$token_expiry_time = 1800; // 30 menit
if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > $token_expiry_time) {
    // Jika token kadaluwarsa, buat token baru
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time(); // Perbarui timestamp token
}


$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Cek jika pengguna dalam periode lockout
$lockout_active = false;
$remaining_time = 0;
if (isset($_SESSION['lockout_time']) && (time() - $_SESSION['lockout_time']) < 180) {
    $lockout_active = true;
    $remaining_time = 180 - (time() - $_SESSION['lockout_time']); // Hitung sisa waktu lockout dalam detik
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bebascedera - Login Clinic</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="shortcut icon" href="assets/images/logo-bebascedera.png" />
</head>
<body>
<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
            <div class="row w-100 mx-0">
                <div class="col-lg-4 mx-auto">
                    <div class="auth-form-light text-left py-3 px-4 px-sm-5">
                        <div class="brand-logo">
                            <img src="assets/images/logo-bebascedera.png" alt="logo">
                        </div>
                        <h4>Hello! Let's get started</h4>
                        <h6 class="font-weight-light">Sign in to continue.</h6>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <form class="pt-3" action="login_process.php" method="POST" id="login-form">
                            <div class="form-group">
                                <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Username" required autocomplete="username" aria-label="Username">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required autocomplete="current-password" aria-label="Password">
                            </div>
                            <!-- CSRF token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <!-- Google reCAPTCHA -->
                            <div class="g-recaptcha" data-sitekey="6LeiYlwqAAAAAIExgsZu6J8MrCKn30qyI35wAAmz"></div>

                            <div class="mt-3 d-grid gap-2">
                                <button type="submit" class="btn btn-block btn-danger btn-lg font-weight-medium auth-form-btn" id="login-btn">SIGN IN</button>
                            </div>
                            <div class="my-2 d-flex justify-content-between align-items-center">
                                <a href="forgot-password.php" class="auth-link text-black">Forgot password?</a>
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
<!-- Google reCAPTCHA v2 -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    // Validasi JavaScript untuk mencegah pengiriman form kosong
    document.getElementById('login-form').addEventListener('submit', function(event) {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        if (!username || !password) {
            event.preventDefault();
            alert('Username and Password are required!');
        }
    });

    // Nonaktifkan klik kanan untuk mencegah inspect element
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
    });

    // Jika dalam lockout, disable tombol login
    var lockoutActive = <?php echo json_encode($lockout_active); ?>;
    var remainingTime = <?php echo isset($remaining_time) ? $remaining_time : 0; ?>;
    var loginButton = document.getElementById('login-btn');

    if (lockoutActive) {
        loginButton.disabled = true;
        var lockoutMessage = document.createElement('div');
        lockoutMessage.innerHTML = `<p class="text-danger">Terlalu banyak percobaan login gagal. Tunggu <span id="lockout-timer">${remainingTime}</span> detik sebelum mencoba lagi.</p>`;
        document.querySelector('.auth-form-light').insertBefore(lockoutMessage, document.querySelector('#login-form'));

        // Hitung mundur untuk lockout
        var timerInterval = setInterval(function() {
            remainingTime--;
            document.getElementById('lockout-timer').innerText = remainingTime;
            if (remainingTime <= 0) {
                clearInterval(timerInterval);
                loginButton.disabled = false;
                lockoutMessage.remove(); // Hapus pesan setelah waktu habis
            }
        }, 1000);
    }
</script>
</body>
</html>
