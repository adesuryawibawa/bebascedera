<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json'); // Pastikan respon berupa JSON

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Anda perlu login untuk mengakses halaman ini.']);
    exit;
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Ambil data dari form
$kapasitas_bed = intval($_POST['kapasitas_bed'] ?? 0);

// Validasi input
if ($kapasitas_bed <= 0) {
    echo json_encode(['success' => false, 'message' => 'Kapasitas Bed harus bernilai positif.']);
    exit;
}

// Update kapasitas bed di database
$sql = "UPDATE cabang SET kapasitas_bed = ? WHERE id_cabang = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $kapasitas_bed, $_SESSION['cabang']);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Kapasitas Bed berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan pada Kapasitas Bed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui Kapasitas Bed.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Kesalahan pada query database.']);
}

$conn->close();
exit;
?>
