<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Ambil data dari request
$absensiId = $_GET['id'] ?? null;
$bookingKapasitasId = $_GET['idbooking'] ?? null;
$tanggal = $_GET['tanggal'] ?? null;
$jam = $_GET['jam'] ?? null;
$id_cabang = $_GET['cabang'] ?? 0;

// Validasi input
if (!$absensiId || !$bookingKapasitasId) {
    $_SESSION['error_message'] = "Data tidak valid.";
    header("Location: booking-data.php"); // Redirect ke halaman sebelumnya
    exit;
}

include '../includes/config.php';

// Mulai transaksi
$conn->begin_transaction();

try {
    // Hapus data dari tabel absensi_pasien
    $queryHapusAbsensi = "DELETE FROM absensi_pasien WHERE id = ?";
    $stmtHapusAbsensi = $conn->prepare($queryHapusAbsensi);
    $stmtHapusAbsensi->bind_param("i", $absensiId);

    if (!$stmtHapusAbsensi->execute()) {
        throw new Exception("Gagal menghapus data absensi: " . $stmtHapusAbsensi->error);
    }

    // Ambil dan update kapasitas_terpakai di tabel booking_kapasitas
    $queryGetKapasitas = "SELECT kapasitas_terpakai FROM booking_kapasitas WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
    $stmtGetKapasitas = $conn->prepare($queryGetKapasitas);

    if (!$stmtGetKapasitas) {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
    }

    $stmtGetKapasitas->bind_param("iss", $id_cabang, $tanggal, $jam);

    if (!$stmtGetKapasitas->execute()) {
        throw new Exception("Gagal mengambil data kapasitas terpakai: " . $stmtGetKapasitas->error);
    }

    $result = $stmtGetKapasitas->get_result();
    if ($result->num_rows <= 0) {
        throw new Exception("Data booking kapasitas tidak ditemukan.");
    }

    $row = $result->fetch_assoc();
    $kapasitasTerpakai = $row['kapasitas_terpakai'];
    $kapasitasTerpakaiBaru = $kapasitasTerpakai - 1;

    $queryUpdateKapasitas = "UPDATE booking_kapasitas SET kapasitas_terpakai = ? WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
    $stmtUpdateKapasitas = $conn->prepare($queryUpdateKapasitas);

    if (!$stmtUpdateKapasitas) {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
    }

    $stmtUpdateKapasitas->bind_param("iiss", $kapasitasTerpakaiBaru, $id_cabang, $tanggal, $jam);

    if (!$stmtUpdateKapasitas->execute()) {
        throw new Exception("Gagal mengupdate data booking kapasitas: " . $stmtUpdateKapasitas->error);
    }

    if ($stmtUpdateKapasitas->affected_rows <= 0) {
        throw new Exception("Tidak ada data booking kapasitas yang diupdate.");
    }

    // Commit transaksi
    $conn->commit();

    // Simpan pesan sukses ke session
    $_SESSION['success_message'] = "Data absensi pasien berhasil dihapus dan kapasitas terpakai diperbarui.";
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();

    // Simpan pesan error ke session
    $_SESSION['error_message'] = $e->getMessage();
} finally {
    // Tutup statement dan koneksi
    if (isset($stmtHapusAbsensi)) $stmtHapusAbsensi->close();
    if (isset($stmtGetKapasitas)) $stmtGetKapasitas->close();
    if (isset($stmtUpdateKapasitas)) $stmtUpdateKapasitas->close();
    $conn->close();

    // Redirect ke halaman sebelumnya
    header("Location: booking-data.php");
    exit;

}
?>