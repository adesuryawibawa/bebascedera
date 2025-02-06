<?php
//ini_set('session.cookie_httponly', 1);  // Cegah akses JavaScript ke cookie
//ini_set('session.cookie_secure', 1);    // Pastikan cookie hanya dikirim melalui HTTPS
//ini_set('session.use_strict_mode', 1);  // Batasi penggunaan session ID hanya dari server yang valid

session_start();
//session_regenerate_id(true); // Regenerasi ID session untuk mencegah session hijacking

// HTTPS enforcement, kecuali di localhost
if (!isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

require 'includes/config.php';

// Fungsi untuk menghindari serangan XSS
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Fungsi untuk validasi token CSRF menggunakan hash_equals untuk menghindari timing attack
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk memigrasi password MD5 ke password_hash()
function migratePassword($conn, $username, $new_password_hash) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_password_hash, $username);
    return $stmt->execute();
}

// Fungsi untuk memeriksa percobaan login yang gagal (Rate Limiting)
function checkLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    if (!isset($_SESSION['lockout_time'])) {
        $_SESSION['lockout_time'] = 0;
    }

    $lockout_duration = 180; // Durasi lockout dalam detik (3 menit)
    
    if ($_SESSION['login_attempts'] >= 3 && (time() - $_SESSION['lockout_time']) < $lockout_duration) {
        $_SESSION['error_message'] = 'Terlalu banyak percobaan login gagal. Coba lagi dalam 3 menit.';
        header("Location: login.php");
        exit;
    }

    if ($_SESSION['login_attempts'] >= 3 && (time() - $_SESSION['lockout_time']) >= $lockout_duration) {
        // Reset setelah lockout selesai
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
    }
    
    if ($_SESSION['login_attempts'] >= 3 && !isset($_SESSION['recaptcha_verified'])) {
        $_SESSION['error_message'] = "Verifikasi CAPTCHA diperlukan setelah 3 percobaan gagal.";
        header("Location: login.php");
        exit;
    }
}

// Fungsi untuk mencatat percobaan login yang gagal
function recordFailedLoginAttempt() {
    $_SESSION['login_attempts'] += 1;
    if ($_SESSION['login_attempts'] >= 3) {
        $_SESSION['lockout_time'] = time();
    }
}

// Logging error ke file
function logError($message) {
    error_log($message, 3, 'error_log.log'); // Simpan error ke file log
}

// Periksa percobaan login
checkLoginAttempts();

try {
    // Mengambil data dari form login
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    $csrf_token = $_POST['csrf_token'];

    // Validasi CSRF Token
    if (!validateCsrfToken($csrf_token)) {
        throw new Exception('CSRF token tidak valid.');
    }

    // Validasi input
    if (empty($username) || empty($password)) {
        throw new Exception('Username dan password harus diisi.');
    }

    // Ambil reCAPTCHA response dari form
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // Secret key Anda dari Google reCAPTCHA
    $secret_key = '6LeiYlwqAAAAAJb8geB9SCX0i--HYJbOZjy3nDRq'; // Ganti dengan secret key Anda

    // Kirim permintaan POST ke Google untuk verifikasi reCAPTCHA
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($recaptcha_url . '?secret=' . $secret_key . '&response=' . $recaptcha_response);
    $response_keys = json_decode($response, true);

    // Cek apakah verifikasi berhasil
    if (intval($response_keys['success']) !== 1) {
        throw new Exception('Verifikasi reCAPTCHA gagal.');
    }

    // Membuat koneksi ke database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Cek koneksi
    if ($conn->connect_error) {
        throw new Exception("Koneksi ke database gagal: " . $conn->connect_error);
    }

    // Mengambil data pengguna berdasarkan username
    $stmt = $conn->prepare("SELECT id, username, fullname, email, phone, password, level, picture, about, flagactive, id_cabang FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Cek apakah pengguna aktif (flagactive == 1)
    if ($user && $user['flagactive'] != 1) {
        throw new Exception("Akun Anda tidak aktif.");
    }

    if ($user) {
        // Verifikasi password menggunakan MD5 (password lama)
        if (md5($password) === $user['password']) {
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (migratePassword($conn, $username, $new_password_hash)) {
                $_SESSION['success_message'] = "Password berhasil diperbarui ke format baru!";
            }
            setUserSession($user);
            redirectByUserLevel($user['level']);
        } elseif (password_verify($password, $user['password'])) {
            setUserSession($user);
            redirectByUserLevel($user['level']);
        } else {
            recordFailedLoginAttempt();
            throw new Exception("Username atau password salah.");
        }
    } else {
        throw new Exception("Username tidak ditemukan.");
    }
} catch (Exception $e) {
    // Logging kesalahan ke file log
    logError($e->getMessage());

    // Tampilkan pesan error yang aman ke pengguna
    $_SESSION['error_message'] = "Terjadi kesalahan. Silakan coba lagi nanti.";
    header("Location: login.php");
    exit;
} finally {
    // Menutup koneksi jika koneksi ada
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Fungsi untuk menyetel sesi pengguna
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['level'] = $user['level'];
    $_SESSION['picture'] = $user['picture'];
    $_SESSION['about'] = $user['about'];	
    $_SESSION['cabang'] = $user['id_cabang'];
    $_SESSION['loggedin'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Set token CSRF baru untuk halaman berikutnya
    session_regenerate_id(true); // Regenerasi ID session setelah login
}

// Fungsi untuk mengarahkan pengguna berdasarkan level
function redirectByUserLevel($level) {
    switch ($level) {
        case 'bod':
            header("Location: o/index.php");
            break;
        case 'cabang':
            header("Location: b/index.php");
            break;
        case 'cs':
            header("Location: cs/index.php");
            break;
		case 'hrd':
            header("Location: hrd/index.php");
            break;
		case 'employee':
            header("Location: e/index.php");
            break;
		case 'customer':
            header("Location: public/index.php");
            break;
        default:
            $_SESSION['error_message'] = "Level user tidak valid.";
            header("Location: login.php");
            break;
    }
    exit;
}
?>
