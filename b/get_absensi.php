<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include konfigurasi database
include '../includes/config.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah level pengguna adalah Admin Cabang
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}
include '../includes/config.php';

// Ambil id_booking dari request
$id_booking = $_POST['id_booking'] ?? null;

if (!$id_booking) {
    echo json_encode([]);
    exit;
}

// Query untuk mengambil data absensi
$sql = "
    SELECT 
        a.id,
		a.booking_id,
        a.tanggal_booking AS tanggal_treatment,
        a.waktu_booking,
        t.nama_terapis AS nama_terapis,
		b.id_cabang
    FROM absensi_pasien AS a
	LEFT JOIN booking AS b ON b.id=a.booking_id
    LEFT JOIN terapis AS t ON a.id_terapis = t.id
    WHERE a.booking_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>