<?php
session_start(); 

// Simpan pesan sukses logout dalam session
$_SESSION['success_message'] = "Anda telah berhasil logout.";

// Hapus semua variabel sesi
$_SESSION = array();

// Jika cookie sesi digunakan, hapus cookie tersebut
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Mulai sesi baru hanya untuk menyimpan pesan sukses logout
session_start();
$_SESSION['success_message'] = "Anda telah berhasil logout.";

// Alihkan ke halaman login
header("Location: login.php");
exit;
