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
    header("Location: booking-data.php");
    exit;
}

// Koneksi ke database
include '../includes/config.php';
include '../includes/api-wa-old.php'; // Include API WhatsApp

// Mulai transaksi
$conn->begin_transaction();

try {
    // Ambil data dari form
    $id_booking = $_POST['id_booking'] ?? null;
    $id_cabang = $_POST['id_cabang'] ?? null;
    $tanggal_booking = $_POST['tanggal_booking'] ?? null;
    $waktu_booking = $_POST['waktu_booking'] ?? null;
    $user_input = $_SESSION['username'] ?? null;
    $id_terapis = $_POST['id_terapis'] ?? null;
	
    // Validasi input
    $errors = [];
    if (!$id_booking) $errors[] = "ID Booking tidak valid.";
    if (!$id_cabang) $errors[] = "ID Cabang tidak valid.";
    if (!$tanggal_booking) $errors[] = "Tanggal booking tidak valid.";
    if (!$waktu_booking) $errors[] = "Waktu booking tidak valid.";
    if (!$user_input) $errors[] = "User input tidak valid.";
    if (!$id_terapis) $errors[] = "ID Terapis tidak valid.";

    // Ekstrak waktu awal dari rentang waktu
    if (strpos($waktu_booking, ' - ') !== false) {
        $waktu_booking = explode(' - ', $waktu_booking)[0];
    } else {
        $errors[] = "Format waktu booking tidak valid.";
    }

    // Jika ada error, simpan ke session dan redirect
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        header("Location: booking-data.php");
        exit;
    }

    // Query untuk memeriksa apakah slot sudah ada
    $querySlot = "
        SELECT kapasitas_terpakai 
        FROM booking_kapasitas 
        WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
    $stmtSlot = $conn->prepare($querySlot);
    $stmtSlot->bind_param("iss", $id_cabang, $tanggal_booking, $waktu_booking);
    $stmtSlot->execute();
    $resultSlot = $stmtSlot->get_result();

    if ($resultSlot->num_rows > 0) {
        // Jika slot sudah ada, update kapasitas_terpakai
        $queryUpdateSlot = "
            UPDATE booking_kapasitas 
            SET kapasitas_terpakai = kapasitas_terpakai + 1, user_input = ? 
            WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
        $stmtUpdateSlot = $conn->prepare($queryUpdateSlot);
        $stmtUpdateSlot->bind_param("siss", $user_input, $id_cabang, $tanggal_booking, $waktu_booking);
        $stmtUpdateSlot->execute();

        if ($stmtUpdateSlot->affected_rows <= 0) {
            throw new Exception("Gagal memperbarui kapasitas.");
        }
    } else {
        // Jika slot belum ada, insert data baru
        $queryInsertSlot = "
            INSERT INTO booking_kapasitas (id_booking, id_cabang, tanggal, jam, kapasitas_terpakai, user_input) 
            VALUES (?, ?, ?, ?, 1, ?)";
        $stmtInsertSlot = $conn->prepare($queryInsertSlot);
        $stmtInsertSlot->bind_param("iisss", $id_booking, $id_cabang, $tanggal_booking, $waktu_booking, $user_input);
        $stmtInsertSlot->execute();

        if ($stmtInsertSlot->affected_rows <= 0) {
            throw new Exception("Gagal menambahkan slot baru.");
        }
    }

    // Insert data ke tabel absensi_pasien
    $queryInsertAbsensi = "
        INSERT INTO absensi_pasien (booking_id, tanggal_booking, waktu_booking, id_terapis, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";
    $stmtInsertAbsensi = $conn->prepare($queryInsertAbsensi);
    $stmtInsertAbsensi->bind_param("issss", $id_booking, $tanggal_booking, $waktu_booking, $id_terapis, $user_input);
    $stmtInsertAbsensi->execute();

    if ($stmtInsertAbsensi->affected_rows <= 0) {
        throw new Exception("Gagal menambahkan data absensi.");
    }

    // Kirim WhatsApp notifikasi (non-blocking)
    // Contoh: Kirim notifikasi jika diperlukan
    // $message = "Hai, booking Anda berhasil dibuat.";
    // if ($contactNumber) {
    //     $response = sendWhatsAppMessage($contactNumber, $message);
    //     if ($response) {
    //         error_log("Pesan WhatsApp berhasil dikirim.");
    //     } else {
    //         error_log("Pesan WhatsApp gagal dikirim.");
    //     }
    // }

    // Commit transaksi
    $conn->commit();

    // Simpan pesan sukses ke session dan redirect
    $_SESSION['success_message'] = "Jadwal treatment berhasil dibuat.";
    header("Location: booking-data.php");
    exit;
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();

    // Simpan pesan error ke session dan redirect
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: booking-data.php");
    exit;
} finally {
    // Tutup statement dan koneksi
    if (isset($stmtSlot)) $stmtSlot->close();
    if (isset($stmtUpdateSlot)) $stmtUpdateSlot->close();
    if (isset($stmtInsertSlot)) $stmtInsertSlot->close();
    if (isset($stmtInsertAbsensi)) $stmtInsertAbsensi->close();
    $conn->close();
}
?>