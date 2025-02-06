<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika belum login
    exit;
}

// Cek apakah level pengguna adalah "Admin Cabang"
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan Admin Cabang
    exit;
}

// Include konfigurasi database
include '../includes/config.php';

// Validasi parameter
$tanggal = $_GET['tanggal'] ?? null;
$id_cabang = $_GET['id_cabang'] ?? null;


if (!$tanggal || !$id_cabang) {
    $_SESSION['error_message'] = "Parameter tanggal atau id_cabang tidak valid.";
    echo json_encode(['success' => false, 'message' => 'Parameter tanggal atau Cabang tidak valid.']);
    exit;
}

try {
    // Step 1: Ambil informasi jam operasional dan kapasitas bed dari table cabang
    $queryCabang = "SELECT jam_buka, jam_tutup, kapasitas_bed FROM cabang WHERE id_cabang = ?";
    $stmtCabang = $conn->prepare($queryCabang);
    $stmtCabang->bind_param("i", $id_cabang);
    $stmtCabang->execute();
    $resultCabang = $stmtCabang->get_result();

    if ($resultCabang->num_rows === 0) {
        $_SESSION['error_message'] = "Cabang tidak ditemukan.";
        echo json_encode(['success' => false, 'message' => 'Cabang tidak ditemukan.']);
        exit;
    }

    $cabangInfo = $resultCabang->fetch_assoc();
    $jam_buka = $cabangInfo['jam_buka'];
    $jam_tutup = $cabangInfo['jam_tutup'];
    $kapasitas_bed = (int)$cabangInfo['kapasitas_bed']; // Kapasitas total per slot

    // Step 2: Ambil kapasitas terpakai dari table booking_kapasitas
    $queryKapasitas = "
        SELECT jam, SUM(kapasitas_terpakai) AS kapasitas_terpakai
        FROM booking_kapasitas
        WHERE id_cabang = ? AND tanggal = ?
        GROUP BY jam
    ";
    $stmtKapasitas = $conn->prepare($queryKapasitas);
    $stmtKapasitas->bind_param("is", $id_cabang, $tanggal);
    $stmtKapasitas->execute();
    $resultKapasitas = $stmtKapasitas->get_result();

    // Step 3: Format data kapasitas terpakai menjadi array
	$slotTerpakai = [];
	while ($row = $resultKapasitas->fetch_assoc()) {
		$slotTerpakai[$row['jam']] = (int)$row['kapasitas_terpakai'];
	}

	// Step 4: Bentuk slot waktu dari jam buka hingga jam tutup
	$slots = [];
	$currentSlot = strtotime($jam_buka);
	$endSlot = strtotime($jam_tutup);

	while ($currentSlot < $endSlot) {
		$slotStart = date("H:i:s", $currentSlot); // Format waktu sebagai H:i:s
		$slotEnd = date("H:i:s", strtotime('+1 hour', $currentSlot)); // Akhir slot
		$keySlot = $slotStart; // Gunakan format H:i:s untuk konsistensi

		// Cek kapasitas terpakai untuk slot ini
		$terpakai = isset($slotTerpakai[$keySlot]) ? $slotTerpakai[$keySlot] : 0;
		$sisaKapasitas = $kapasitas_bed - $terpakai;

		// Tentukan status slot dan ketersediaan
		if ($sisaKapasitas <= 0) {
			$status = "Full";
			$available = false;
		} else {
			$status = "{$sisaKapasitas} Slot";
			$available = true;
		}

		// Tambahkan slot ke array
		$slots[] = [
			'jam' => date("H:i", $currentSlot) . " - " . date("H:i", strtotime('+1 hour', $currentSlot)), // Format untuk tampilan
			'status' => $status,
			'available' => $available
		];

		$currentSlot = strtotime('+1 hour', $currentSlot); // Pindah ke slot berikutnya
	}

    // Step 5: Kirim response JSON ke Frontend
    echo json_encode([
    'success' => true,
    'debug' => [
        'jam_buka' => $jam_buka,
        'jam_tutup' => $jam_tutup,
        'kapasitas_bed' => $kapasitas_bed,
        'slotTerpakai' => $slotTerpakai,
        'slots' => $slots
		]
	]);
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    exit;
}
?>
