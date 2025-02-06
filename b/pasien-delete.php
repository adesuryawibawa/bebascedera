<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validasi login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Anda perlu login untuk mengakses halaman ini.']);
    exit;
}

// Validasi level pengguna
if ($_SESSION['level'] !== 'cabang') {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.']);
    exit;
}

// Validasi CSRF
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_SERVER['HTTP_X_CSRF_TOKEN']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

include '../includes/config.php';

$patientId = intval($data['id']);
$idCabang = $_SESSION['cabang'];

// Cek apakah pasien memiliki catatan di tabel booking
$queryCheckBooking = "SELECT COUNT(*) AS count FROM booking WHERE id_pasien = ? AND id_cabang = ?";
$stmtCheckBooking = $conn->prepare($queryCheckBooking);
$stmtCheckBooking->bind_param("ii", $patientId, $idCabang);
$stmtCheckBooking->execute();
$resultCheckBooking = $stmtCheckBooking->get_result();
$row = $resultCheckBooking->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Pasien memiliki catatan di tabel booking dan tidak dapat dihapus.']);
    exit;
}

// Lanjutkan penghapusan pasien
$queryDelete = "DELETE FROM pasien WHERE id = ? AND id_cabang = ?";
$stmtDelete = $conn->prepare($queryDelete);
$stmtDelete->bind_param("ii", $patientId, $idCabang);

if ($stmtDelete->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pasien berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus pasien.']);
}

$stmtCheckBooking->close();
$stmtDelete->close();
$conn->close();
?>
