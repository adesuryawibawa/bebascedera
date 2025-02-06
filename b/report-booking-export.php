<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php';

$id_cabang = $_SESSION['cabang'];
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Query utama
$query = "
    SELECT 
        b.tanggal_booking, b.waktu_booking, b.metode_pembayaran, b.kode_promo, 
        b.diskon, b.harga_total, b.status, b.keluhan, 
        p.nama AS nama_pasien, p.jenis_kelamin, p.tanggal_lahir, p.nomor_wa, 
        p.email, p.alamat, p.keterangan,
        pr.nama_produk, pr.harga,
        t.nama_terapis, 
        c.nama_cabang
    FROM booking b
    LEFT JOIN pasien p ON b.id_pasien = p.id
    LEFT JOIN produk pr ON b.id_produk = pr.id
    LEFT JOIN terapis t ON b.id_terapis = t.id
    LEFT JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE b.id_cabang = ?
";

if ($start_date && $end_date) {
    $query .= " AND b.tanggal_booking BETWEEN ? AND ?";
}

$query .= " ORDER BY b.tanggal_booking DESC";
$stmt = $conn->prepare($query);

if ($start_date && $end_date) {
    $stmt->bind_param("iss", $id_cabang, $start_date, $end_date);
} else {
    $stmt->bind_param("i", $id_cabang);
}

$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);


// Penamaan file
$filename = "Report_Booking";
if ($start_date && $end_date) {
    $filename .= "_{$start_date}_to_{$end_date}";
}
$filename .= ".xls";

// Generate file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Report_Booking_{$start_date}_to_{$end_date}.xls");

echo "Nama Pasien\tJenis Kelamin\tTanggal Lahir\tNomor WA\tEmail\tAlamat\tKeterangan\tNama Produk\tNama Terapis\tNama Cabang\tHarga Produk\tKode Promo\tDiskon\tHarga Total\tMetode Pembayaran\tStatus\tKeluhan\tTanggal Booking\tWaktu Booking\n";

foreach ($bookings as $booking) {
    echo implode("\t", [
        htmlspecialchars($booking['nama_pasien'] ?? '-'),
        htmlspecialchars($booking['jenis_kelamin'] ?? '-'),
        htmlspecialchars($booking['tanggal_lahir'] ?? '-'),
        htmlspecialchars($booking['nomor_wa'] ?? '-'),
        htmlspecialchars($booking['email'] ?? '-'),
        htmlspecialchars($booking['alamat'] ?? '-'),
        htmlspecialchars($booking['keterangan'] ?? '-'),
        htmlspecialchars($booking['nama_produk'] ?? '-'),
        htmlspecialchars($booking['nama_terapis'] ?? '-'),
        htmlspecialchars($booking['nama_cabang'] ?? '-'),
        htmlspecialchars($booking['harga'] ?? '-'),
        htmlspecialchars($booking['kode_promo'] ?? '-'),
        htmlspecialchars($booking['diskon'] ?? '0'),
        "Rp" . number_format($booking['harga_total'] ?? 0, 0, ',', '.'),
        htmlspecialchars($booking['metode_pembayaran'] ?? '-'),
        htmlspecialchars($booking['status'] ?? '-'),
        htmlspecialchars($booking['keluhan'] ?? '-'),
        htmlspecialchars($booking['tanggal_booking'] ?? '-'),
        htmlspecialchars($booking['waktu_booking'] ?? '-')
    ]) . "\n";
}
?>
