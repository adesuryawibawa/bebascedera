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

// Cek level akses
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php';

$id_cabang = $_SESSION['cabang'];

// Ambil filter tanggal
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Query utama
$query = "
    SELECT 
        b.id, b.tanggal_booking, b.waktu_booking, b.metode_pembayaran, b.kode_promo, 
        b.diskon, b.harga_total, b.status, b.keluhan, b.created_at, 
        p.nama AS nama_pasien, pr.nama_produk, t.nama_terapis
    FROM booking b
    LEFT JOIN pasien p ON b.id_pasien = p.id
    LEFT JOIN produk pr ON b.id_produk = pr.id
    LEFT JOIN terapis t ON b.id_terapis = t.id
    WHERE b.id_cabang = ?
";

// Tambahkan filter tanggal jika ada
if ($start_date && $end_date) {
    $query .= " AND b.tanggal_booking BETWEEN ? AND ?";
}

// Urutkan berdasarkan tanggal_booking terbaru
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

include '_header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="font-weight-bold">Report Booking</h3>
        <h6 class="font-weight-normal mb-4">Laporan booking untuk cabang Anda.</h6>
    </div>
</div>

<!-- Filter Tanggal -->
<div class="row mb-4">
    <div class="col-md-12">
        <form method="GET" action="report-booking.php" class="d-flex align-items-center">
            <div class="form-group me-3">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group me-3">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-md ">Filter</button>
            <a href="report-booking-export.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" 
               class="btn btn-success btn-md ms-3">Export Excel</a>
        </form>
    </div>
</div>

<!-- Tabel Booking -->
<div class="row">
    <div class="col-md-12">
        <table class="table table-hover table-striped" id="bookingTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pasien</th>
                    <th>Nama Produk</th>
                    <th>Nama Terapis</th>
                    <th>Tanggal Booking</th>
                    <th>Waktu Booking</th>
                    <th>Metode Pembayaran</th>
                    <th>Harga Total</th>
                    <th>Status</th>
                    <th>Keluhan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($booking['nama_pasien'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($booking['nama_produk'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($booking['nama_terapis'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($booking['tanggal_booking']); ?></td>
                    <td><?= htmlspecialchars($booking['waktu_booking']); ?></td>
                    <td><?= htmlspecialchars($booking['metode_pembayaran']); ?></td>
                    <td>Rp<?= number_format($booking['harga_total'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($booking['status']); ?></td>
                    <td><?= htmlspecialchars($booking['keluhan']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function () {
    jqOld('#bookingTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": true
    });
});
</script>

<?php include '_footer.php'; ?>
