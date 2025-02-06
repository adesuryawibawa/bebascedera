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

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 120 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}


include '../includes/config.php';

// Ambil id_cabang dari session login
$id_cabang = $_SESSION['cabang'] ?? 0;

// Format Tanggal
$tanggalHariIni = date("Y-m-d");
$bulanIni = date("Y-m");

// Query Lead Hari Ini
$queryLeadHariIni = "
    SELECT COUNT(DISTINCT id) AS total_lead_hari 
    FROM booking 
    WHERE id_cabang = ? AND DATE(tanggal_booking) = ?";
$stmt = $conn->prepare($queryLeadHariIni);
$stmt->bind_param("is", $id_cabang, $tanggalHariIni);
$stmt->execute();
$TotalLeadHariIni = $stmt->get_result()->fetch_assoc()['total_lead_hari'] ?? 0;


// Query Customer Hari Ini
$queryCustomerHariIni = "
    SELECT COUNT(DISTINCT id) AS total_customer_hari 
    FROM booking 
    WHERE id_cabang = ? AND status IN ('Confirmed','Completed') AND DATE(tanggal_booking) = ?";
$stmt = $conn->prepare($queryCustomerHariIni);
$stmt->bind_param("is", $id_cabang, $tanggalHariIni);
$stmt->execute();
$totalCustomerHariIni = $stmt->get_result()->fetch_assoc()['total_customer_hari'] ?? 0;

// Query Omset Hari Ini
$queryOmsetHariIni = "
    SELECT 
		SUM(b.harga_total + COALESCE(ba.total_addons, 0)) AS total_omset_hari
	FROM booking AS b
	LEFT JOIN (
		-- Subquery untuk menjumlahkan harga_total dari booking_addons berdasarkan booking_id
		SELECT booking_id, SUM(harga_total) AS total_addons
		FROM booking_addons
		GROUP BY booking_id
	) AS ba ON b.id = ba.booking_id
	WHERE b.id_cabang = ? 
	AND b.status IN ('Confirmed', 'Completed') 
	AND DATE(b.tanggal_booking) = ?
	";
$stmt = $conn->prepare($queryOmsetHariIni);
$stmt->bind_param("is", $id_cabang, $tanggalHariIni);
$stmt->execute();
$totalOmsetHariIni = $stmt->get_result()->fetch_assoc()['total_omset_hari'] ?? 0;

// Query Lead Bulan Ini
$queryLeadBulanIni = "
    SELECT COUNT(DISTINCT id) AS total_lead_bulan 
    FROM booking 
    WHERE id_cabang = ? AND DATE_FORMAT(tanggal_booking, '%Y-%m') = ?";
$stmt = $conn->prepare($queryLeadBulanIni);
$stmt->bind_param("is", $id_cabang, $bulanIni);
$stmt->execute();
$totalLeadBulanIni = $stmt->get_result()->fetch_assoc()['total_lead_bulan'] ?? 0;


// Query Customer Bulan Ini
$queryCustomerBulanIni = "
    SELECT COUNT(DISTINCT id) AS total_customer_bulan 
    FROM booking 
    WHERE id_cabang = ? AND status IN ('Confirmed','Completed') AND DATE_FORMAT(tanggal_booking, '%Y-%m') = ?";
$stmt = $conn->prepare($queryCustomerBulanIni);
$stmt->bind_param("is", $id_cabang, $bulanIni);
$stmt->execute();
$totalCustomerBulanIni = $stmt->get_result()->fetch_assoc()['total_customer_bulan'] ?? 0;

// Query Omset Bulan Ini
$queryOmsetBulanIni = "
    SELECT 
		SUM(b.harga_total + COALESCE(ba.total_addons, 0)) AS total_omset_bulan
	FROM booking AS b
	LEFT JOIN (
		-- Subquery untuk menjumlahkan harga_total dari booking_addons berdasarkan booking_id
		SELECT booking_id, SUM(harga_total) AS total_addons
		FROM booking_addons
		GROUP BY booking_id
	) AS ba ON b.id = ba.booking_id
	WHERE b.id_cabang = ? 
	AND b.status IN ('Confirmed', 'Completed') 
	AND DATE_FORMAT(b.tanggal_booking, '%Y-%m') = ?";

$stmt = $conn->prepare($queryOmsetBulanIni);
$stmt->bind_param("is", $id_cabang, $bulanIni);
$stmt->execute();
$totalOmsetBulanIni = $stmt->get_result()->fetch_assoc()['total_omset_bulan'] ?? 0;

// Fungsi untuk mendapatkan ketersediaan slot
function getKetersediaanSlot($conn, $id_cabang, $tanggal) {
    $query = "
        SELECT jam, SUM(kapasitas_terpakai) AS kapasitas_terpakai
        FROM booking_kapasitas
        WHERE id_cabang = ? AND tanggal = ?
        GROUP BY jam
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $id_cabang, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    $slotTerpakai = [];
    while ($row = $result->fetch_assoc()) {
        $slotTerpakai[$row['jam']] = (int)$row['kapasitas_terpakai'];
    }
    return $slotTerpakai;
}

// Ketersediaan default hari ini
$tanggalHariIni = date("Y-m-d");
$slotHariIni = getKetersediaanSlot($conn, $id_cabang, $tanggalHariIni);

// Query Pengeluaran Bulan Ini
$queryExpenses = "
    SELECT e.amount, ec.name AS category_name, e.transaction_date 
    FROM expenses e
    JOIN expense_categories ec ON e.category_id = ec.id
    WHERE e.id_cabang = ? AND DATE_FORMAT(e.transaction_date, '%Y-%m') = ?
    ORDER BY e.transaction_date DESC
    LIMIT 15
";
$stmtExpenses = $conn->prepare($queryExpenses);
$stmtExpenses->bind_param("is", $id_cabang, $bulanIni);
$stmtExpenses->execute();
$resultExpenses = $stmtExpenses->get_result();
$expenses = $resultExpenses->fetch_all(MYSQLI_ASSOC);

// Hitung Total Pengeluaran
$totalExpenses = 0;
foreach ($expenses as $expense) {
    $totalExpenses += $expense['amount'];
}

include '_header.php'; // Header halaman
?>

<style>
.card-body h6 {
    font-size: 0.9rem; /* Sesuaikan ukuran font untuk judul kecil */
}

.card-body h3 {
    font-size: 1.2rem; /* Sesuaikan ukuran font untuk angka */
}

</style>

<div class="row">
	<div class="col-md-12 grid-margin">
		<div class="row">
			<div class="col-12 col-xl-6">
				<h3 class="font-weight-bold">Dashboard</h3>
				<h6 class="font-weight-normal mb-0">All systems are running smoothly.</h6>
			</div>
			<div class="col-12 col-xl-6 text-right d-none d-xl-block">
				<!-- Tombol Form Booking -->
				<a href="booking-add.php" class="btn btn-success btn-sm mx-1" style="display: inline-flex; align-items: center; border-radius: 5px;">
					<i class="fas fa-calendar-plus mr-1"></i> FORM BOOKING
				</a>
				<!-- Tombol Expenses -->
				<a href="expense.php" class="btn btn-warning btn-sm mr-4" style="display: inline-flex; align-items: center; border-radius: 5px;">
					<i class="fas fa-wallet mr-1"></i> EXPENSES
				</a>
			</div>

		</div>
	</div>

    <div class="row">
		<!-- Lead Hari Ini -->
		<div class="col-md-4 mb-2">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #6c757dad; padding: 15px; border-radius: 10px;">
						<i class="fa fa-group fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Lead Hari Ini</h6>
						<h3><?php echo number_format($TotalLeadHariIni); ?></h3>
					</div>
				</div>
			</div>
		</div>
		<!-- Customer Hari Ini -->
		<div class="col-md-4 mb-2">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #ffc100ab; padding: 15px; border-radius: 10px;">
						<i class="fa fa-cart-plus fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Sales Hari Ini</h6>
						<h3><?php echo number_format($totalCustomerHariIni); ?></h3>
					</div>
				</div>
			</div>
		</div>
	
		<!-- Omset Hari Ini -->
		<div class="col-md-4 ">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #66bb6ac7; padding: 15px; border-radius: 10px;">
						<i class="fa fa-store fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Omset Hari Ini</h6>
						<h3>Rp <?php echo number_format($totalOmsetHariIni, 0, ',', '.'); ?></h3>
					</div>
				</div>
			</div>
		</div>

        <!-- Customer Bulan Ini -->
		<div class="col-md-4 mt-2">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #6c757d; padding: 15px; border-radius: 10px;">
						<i class="fa fa-group fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Lead Bulan Ini</h6>
						<h3><?php echo number_format($totalLeadBulanIni); ?></h3>
					</div>
				</div>
			</div>
		</div>
		
		<div class="col-md-4 mt-2">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #ffc100; padding: 15px; border-radius: 10px;">
						<i class="fa fa-cart-plus fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Sales Bulan Ini</h6>
						<h3><?php echo number_format($totalCustomerBulanIni); ?></h3>
					</div>
				</div>
			</div>
		</div>
		
        <!-- Omset Bulan Ini -->
		<div class="col-md-4 mt-3">
			<div class="card text-center" style="border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
				<div class="card-body" style="display: flex; align-items: center;">
					<div style="background-color: #57b657; padding: 15px; border-radius: 10px;">
						<i class="fa fa-store fa-2x text-white"></i>
					</div>
					<div class="text-left ml-3">
						<h6 class="text-muted mb-1">Omset Bulan Ini</h6>
						<h3>Rp <?php echo number_format($totalOmsetBulanIni, 0, ',', '.'); ?></h3>
					</div>
				</div>
			</div>
		</div>
    </div>

<div class="row mt-3">
    <!-- Ketersediaan Berdasarkan Tanggal -->
    <div class="col-md-4 mb-2">
         <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Ketersediaan Slot</h5>
				<label for="tanggalBooking">Tanggal Booking</label>
				<input type="date" name="tanggal_booking" id="tanggalBooking" class="form-control"><br/>
                <div id="availabilitySection">
                    <p class="text-muted">Silakan pilih tanggal untuk melihat ketersediaan slot.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pengeluaran Bulan Ini -->
   <div class="col-md-8">
    <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        <div class="card-body">
            <h5 class="card-title">Pengeluaran Bulan Ini</h5>
            <div class="table-responsive d-none d-md-block"> <!-- Tabel hanya di layar besar -->
                <table class="table table-striped" style="white-space: nowrap;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($expense['transaction_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                    <td>Rp <?php echo number_format($expense['amount'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong>Rp <?php echo number_format($totalExpenses, 0, ',', '.'); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data pengeluaran untuk bulan ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Kartu Vertikal di Layar Kecil -->
            <div class="d-block d-md-none">
                <?php if (!empty($expenses)): ?>
                    <?php foreach ($expenses as $expense): ?>
                        <div class="border rounded p-2 mb-2">
                            <p><strong>Tanggal:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($expense['transaction_date']))); ?></p>
                            <p><strong>Kategori:</strong> <?php echo htmlspecialchars($expense['category_name']); ?></p>
                            <p><strong>Jumlah:</strong> Rp. <?php echo number_format($expense['amount'], 0, ',', '.'); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <div class="border rounded p-2">
                        <p><strong>Total:</strong> Rp. <?php echo number_format($totalExpenses, 0, ',', '.'); ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Belum ada data pengeluaran untuk bulan ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<script>

var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
    jqOld('#expenseTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 50,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": true
    });
});
// Fungsi untuk memuat ketersediaan slot
document.getElementById('tanggalBooking').addEventListener('change', function () {
    const selectedDate = this.value;

    // Memuat data ketersediaan slot
    fetch(`get-availability.php?tanggal=${selectedDate}&id_cabang=<?php echo $id_cabang; ?>`)
        .then(response => response.json())
        .then(data => {
            const availabilitySection = document.getElementById('availabilitySection');
            if (data.success) {
		        let availabilityHTML = `<p><strong style="color: green;">${data.tanggal}</strong></p>`;
                availabilityHTML += `<p>Cabang: ${data.nama_cabang}</p>`;
                availabilityHTML += `<p>Jam Operasional: ${data.jam_operasional}</p>`;
                availabilityHTML += '<p><b>Slot Available:</b></p><ul>';

                data.slots.forEach(slot => {
                    // Jika status slot adalah Full, tambahkan efek tulisan strike
                    if (slot.status === 'Full') {
                        availabilityHTML += `<li><span style="text-decoration: line-through; color: red;">${slot.jam} (${slot.status})</span></li>`;
                    } else {
                        availabilityHTML += `<li>${slot.jam} <strong style="color: green;">(${slot.status})</strong></li>`;
                    }
                });

                availabilityHTML += '</ul>';
                availabilityHTML += `<p>Total Pasien: <b>${data.total_pasien}</b> Pasien</p>`;
                availabilityHTML += `<p>Total Kapasitas: <b>${data.total_kapasitas}</b> Pasien</p>`;
                availabilitySection.innerHTML = availabilityHTML;
            } else {
                availabilitySection.innerHTML = `<p class="text-danger">${data.message || 'Tidak ada data ketersediaan untuk tanggal ini.'}</p>`;
            }
        })
        .catch(error => {
            console.error('Error fetching availability:', error);
            document.getElementById('availabilitySection').innerHTML = '<p class="text-danger">Gagal memuat ketersediaan slot.</p>';
        });
});
</script>
<?php include '_footer.php'; // Footer halaman ?>
