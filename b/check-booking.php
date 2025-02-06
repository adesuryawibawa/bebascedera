<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validasi login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['hasBookings' => false, 'error' => 'Anda perlu login untuk mengakses halaman ini.']);
    exit;
}

include '../includes/config.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['hasBookings' => false, 'error' => 'ID pasien tidak valid.']);
    exit;
}

$query = "SELECT COUNT(*) AS count FROM booking WHERE id_pasien = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['hasBookings' => $row['count'] > 0]);
exit;
?>
