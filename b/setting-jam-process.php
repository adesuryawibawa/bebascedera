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

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: booking.php");
    exit;
}

// Koneksi ke database
include '../includes/config.php';

header('Content-Type: application/json'); // Pastikan respon berupa JSON

// Ambil data dari form
$jam_buka = $_POST['jam_buka'] ?? '';
$jam_tutup = $_POST['jam_tutup'] ?? '';

// Tambahkan detik jika hanya menerima format HH:mm
if (strlen($jam_buka) === 5) {
    $jam_buka .= ':00'; // Tambahkan detik
}
if (strlen($jam_tutup) === 5) {
    $jam_tutup .= ':00';
}

// Validasi input
if (empty($jam_buka) || empty($jam_tutup)) {
    echo json_encode(['success' => false, 'message' => 'Jam buka dan jam tutup harus diisi.']);
    exit;
}

if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $jam_buka)) {
    echo json_encode(['success' => false, 'message' => 'Format jam buka tidak valid.']);
    exit;
}

if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $jam_tutup)) {
    echo json_encode(['success' => false, 'message' => 'Format jam tutup tidak valid.']);
    exit;
}

if (strtotime($jam_buka) >= strtotime($jam_tutup)) {
    echo json_encode(['success' => false, 'message' => 'Jam buka harus lebih awal daripada jam tutup.']);
    exit;
}

// Update jam operasional di database
$sql = "UPDATE cabang SET jam_buka = ?, jam_tutup = ? WHERE id_cabang = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssi", $jam_buka, $jam_tutup, $_SESSION['cabang']);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Jam operasional berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan pada jam operasional.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui jam operasional.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Kesalahan pada query database.']);
}

$conn->close();
exit;
?>
