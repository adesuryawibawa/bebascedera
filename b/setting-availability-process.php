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

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

include '../includes/config.php';

// Ambil data dari form
$tanggal = $_POST['tanggal'] ?? '';
$jam = $_POST['jam'] ?? '';
$is_holiday = isset($_POST['is_holiday']) ? intval($_POST['is_holiday']) : 0;
$id_cabang = $_SESSION['cabang'];
$username = $_SESSION['username'] ?? 'system'; // Default 'system' jika username tidak tersedia

// Ambil kapasitas bed dari database
$queryCabang = "SELECT kapasitas_bed, jam_buka, jam_tutup FROM cabang WHERE id_cabang = ?";
$stmtCabang = $conn->prepare($queryCabang);
$stmtCabang->bind_param("i", $id_cabang);
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();

if ($resultCabang->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cabang tidak ditemukan.']);
    exit;
}

$cabang = $resultCabang->fetch_assoc();
$kapasitas_bed = $cabang['kapasitas_bed'];
$jam_buka = $cabang['jam_buka'];
$jam_tutup = $cabang['jam_tutup'];

// Validasi input
if (empty($tanggal)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal harus diisi.']);
    exit;
}

// Jika hari libur
if ($is_holiday) {
    // Buat daftar slot dari jam buka hingga jam tutup
    $slots = [];
    $current_time = strtotime($jam_buka);
    $end_time = strtotime($jam_tutup);

    while ($current_time < $end_time) {
        $slots[] = date('H:i:s', $current_time);
        $current_time = strtotime('+1 hour', $current_time);
    }

    // Query untuk menambahkan atau memperbarui slot
    $queryHoliday = "INSERT INTO booking_kapasitas (id_booking, id_cabang, tanggal, jam, kapasitas_terpakai, status, user_input) 
                     VALUES (NULL, ?, ?, ?, ?, 'holiday', ?) 
                     ON DUPLICATE KEY UPDATE kapasitas_terpakai = VALUES(kapasitas_terpakai), status = 'holiday', user_input = VALUES(user_input)";
    $stmtHoliday = $conn->prepare($queryHoliday);

    foreach ($slots as $slot) {
        $stmtHoliday->bind_param("issis", $id_cabang, $tanggal, $slot, $kapasitas_bed, $username);
        $stmtHoliday->execute();
    }

    $stmtHoliday->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => 'Hari libur berhasil ditetapkan. Semua slot telah ditandai sebagai holiday.']);
    exit;
}

// Jika memilih jam tertentu (bukan hari libur)
if (!empty($jam)) {
    // Validasi jam agar berada dalam rentang jam buka dan jam tutup
    if (strtotime($jam) < strtotime($jam_buka) || strtotime($jam) >= strtotime($jam_tutup)) {
        echo json_encode(['success' => false, 'message' => 'Jam harus berada dalam rentang jam operasional.']);
        exit;
    }

    // Query untuk menambahkan atau memperbarui slot untuk jam tertentu
    $querySlot = "INSERT INTO booking_kapasitas (id_booking, id_cabang, tanggal, jam, kapasitas_terpakai, status, user_input) 
                  VALUES (NULL, ?, ?, ?, ?, 'other', ?) 
                  ON DUPLICATE KEY UPDATE kapasitas_terpakai = VALUES(kapasitas_terpakai), status = 'other', user_input = VALUES(user_input)";
    $stmtSlot = $conn->prepare($querySlot);
    $stmtSlot->bind_param("issis", $id_cabang, $tanggal, $jam, $kapasitas_bed, $username);

    if ($stmtSlot->execute()) {
        echo json_encode(['success' => true, 'message' => 'Slot pada tanggal dan jam tersebut berhasil diperbarui sebagai Not Available.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui slot.']);
    }

    $stmtSlot->close();
    $conn->close();
    exit;
}

// Jika tidak memenuhi kondisi apapun
echo json_encode(['success' => false, 'message' => 'Operasi tidak valid.']);
exit;
?>
