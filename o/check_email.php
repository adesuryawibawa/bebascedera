<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; 
    header("Location: ../login.php"); 
    exit;
}

// Cek apakah level pengguna adalah "BOD"
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; 
    header("Location: ../login.php"); 
    exit;
}

include '../includes/config.php'; // Koneksi ke database


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo 'taken';
    } else {
        echo 'available';
    }

    $stmt->close();
}
?>
