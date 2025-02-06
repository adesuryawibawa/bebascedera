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

header('Content-Type: application/json');

// Validasi input
if (!isset($_GET['id_cabang']) || !isset($_GET['tanggal'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
    exit;
}

$id_cabang = (int)$_GET['id_cabang'];
$tanggal = $_GET['tanggal'];


try {
    // Format tanggal ke dalam format YYYY-MM-DD
    $formattedDate = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$formattedDate) {
        throw new Exception('Format tanggal tidak valid.');
    }
    $tanggalFormatted = $formattedDate->format('Y-m-d');

    // Query untuk mendapatkan informasi cabang
    $queryCabang = "SELECT nama_cabang, jam_buka, jam_tutup, kapasitas_bed FROM cabang WHERE id_cabang = ?";
    $stmtCabang = $conn->prepare($queryCabang);
    $stmtCabang->bind_param("i", $id_cabang);
    $stmtCabang->execute();
    $resultCabang = $stmtCabang->get_result();

    if ($resultCabang->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Cabang tidak ditemukan.']);
        exit;
    }

    $cabangInfo = $resultCabang->fetch_assoc();
    $nama_cabang = $cabangInfo['nama_cabang'];
    $jam_buka = $cabangInfo['jam_buka'];
    $jam_tutup = $cabangInfo['jam_tutup'];
    $kapasitas_bed = (int)$cabangInfo['kapasitas_bed'];

    // Query untuk mendapatkan kapasitas terpakai per jam dari tabel booking_kapasitas
    $queryKapasitas = "
        SELECT jam, SUM(kapasitas_terpakai) AS kapasitas_terpakai
        FROM booking_kapasitas 
        WHERE id_cabang = ? AND tanggal = ?
        GROUP BY jam
    ";
    $stmtKapasitas = $conn->prepare($queryKapasitas);
    $stmtKapasitas->bind_param("is", $id_cabang, $tanggalFormatted);
    $stmtKapasitas->execute();
    $resultKapasitas = $stmtKapasitas->get_result();

    $bookedSlots = [];
    while ($row = $resultKapasitas->fetch_assoc()) {
        $bookedSlots[$row['jam']] = (int)$row['kapasitas_terpakai'];
    }

    // Generate slot waktu
    $slots = [];
    $startTime = new DateTime($jam_buka);
    $endTime = new DateTime($jam_tutup);

    while ($startTime < $endTime) {
        $slotStart = $startTime->format('H:i:s');
        $slotEnd = $startTime->modify('+1 hour')->format('H:i:s');

        $bookedCount = $bookedSlots[$slotStart] ?? 0;

        // Hitung status slot berdasarkan kapasitas per slot
        $status = ($bookedCount >= $kapasitas_bed) ? 'Full' : ($kapasitas_bed - $bookedCount) . ' Slot';
        $slots[] = [
            'jam' => $slotStart . ' - ' . $slotEnd,
            'status' => $status,
        ];
    }

    // Hitung total pasien dan kapasitas
    $totalPasien = array_sum($bookedSlots);
    $totalKapasitas = count($slots) * $kapasitas_bed;

    // Kirim response JSON
    echo json_encode([
        'success' => true,
        'tanggal' => $formattedDate->format('l, d F Y'), // Format tanggal dalam teks
        'nama_cabang' => $nama_cabang,
        'jam_operasional' => $jam_buka . ' - ' . $jam_tutup . ' WIB',
        'slots' => $slots,
        'total_pasien' => $totalPasien,
        'total_kapasitas' => $totalKapasitas,
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>
