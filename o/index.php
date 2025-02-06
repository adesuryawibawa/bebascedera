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
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 30 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}


include '../includes/config.php';

// Format Tanggal
$tanggalHariIni = date("Y-m-d");
$bulanIni = date("Y-m");

// Query Lead Hari Ini
$queryLeadHariIni = "
    SELECT COUNT(DISTINCT id) AS total_lead_hari 
    FROM booking 
    WHERE DATE(tanggal_booking) = ?";
$stmt = $conn->prepare($queryLeadHariIni);
$stmt->bind_param("s", $tanggalHariIni);
$stmt->execute();
$TotalLeadHariIni = $stmt->get_result()->fetch_assoc()['total_lead_hari'] ?? 0;


// Query Customer Hari Ini
$queryCustomerHariIni = "
    SELECT COUNT(DISTINCT id) AS total_customer_hari 
    FROM booking 
    WHERE status IN ('Confirmed','Completed') AND DATE(tanggal_booking) = ?";
$stmt = $conn->prepare($queryCustomerHariIni);
$stmt->bind_param("s", $tanggalHariIni);
$stmt->execute();
$totalCustomerHariIni = $stmt->get_result()->fetch_assoc()['total_customer_hari'] ?? 0;

// Query Omset Hari Ini
$queryOmsetHariIni = "
    SELECT SUM(harga_total) AS total_omset_hari 
    FROM booking 
    WHERE  status IN ('Confirmed','Completed') AND DATE(tanggal_booking) = ?";
$stmt = $conn->prepare($queryOmsetHariIni);
$stmt->bind_param("s", $tanggalHariIni);
$stmt->execute();
$totalOmsetHariIni = $stmt->get_result()->fetch_assoc()['total_omset_hari'] ?? 0;

// Query Lead Bulan Ini
$queryLeadBulanIni = "
    SELECT COUNT(DISTINCT id) AS total_lead_bulan 
    FROM booking 
    WHERE DATE_FORMAT(tanggal_booking, '%Y-%m') = ?";
$stmt = $conn->prepare($queryLeadBulanIni);
$stmt->bind_param("s", $bulanIni);
$stmt->execute();
$totalLeadBulanIni = $stmt->get_result()->fetch_assoc()['total_lead_bulan'] ?? 0;


// Query Customer Bulan Ini
$queryCustomerBulanIni = "
    SELECT COUNT(DISTINCT id) AS total_customer_bulan 
    FROM booking 
    WHERE status IN ('Confirmed','Completed') AND DATE_FORMAT(tanggal_booking, '%Y-%m') = ?";
$stmt = $conn->prepare($queryCustomerBulanIni);
$stmt->bind_param("s",  $bulanIni);
$stmt->execute();
$totalCustomerBulanIni = $stmt->get_result()->fetch_assoc()['total_customer_bulan'] ?? 0;

// Query Omset Bulan Ini
$queryOmsetBulanIni = "
    SELECT SUM(harga_total) AS total_omset_bulan 
    FROM booking 
    WHERE status IN ('Confirmed','Completed') AND DATE_FORMAT(tanggal_booking, '%Y-%m') = ?";
$stmt = $conn->prepare($queryOmsetBulanIni);
$stmt->bind_param("s",$bulanIni);
$stmt->execute();
$totalOmsetBulanIni = $stmt->get_result()->fetch_assoc()['total_omset_bulan'] ?? 0;

// Fungsi untuk mendapatkan ketersediaan slot
function getKetersediaanSlot($conn, $id_cabang, $tanggal) {
    $query = "
        SELECT jam, SUM(kapasitas_terpakai) AS kapasitas_terpakai
        FROM booking_kapasitas
        WHERE tanggal = ?
        GROUP BY jam
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s",  $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    $slotTerpakai = [];
    while ($row = $result->fetch_assoc()) {
        $slotTerpakai[$row['jam']] = (int)$row['kapasitas_terpakai'];
    }
    return $slotTerpakai;
}

// Query untuk cabang terbaik
$queryCabangTerbaik = "
    SELECT c.nama_cabang, SUM(b.harga_total) AS total_omset
    FROM booking b
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE MONTH(b.tanggal_booking) = MONTH(CURRENT_DATE()) AND YEAR(b.tanggal_booking) = YEAR(CURRENT_DATE())
    GROUP BY b.id_cabang
    ORDER BY total_omset DESC
    LIMIT 1;
";
$stmtCabangTerbaik = $conn->prepare($queryCabangTerbaik);
$stmtCabangTerbaik->execute();
$resultCabangTerbaik = $stmtCabangTerbaik->get_result();
$cabangTerbaik = $resultCabangTerbaik->fetch_assoc();

// Query untuk terapis terbaik
$queryTerapisTerbaik = "
    SELECT t.nama_terapis, COUNT(b.id) AS total_booking
    FROM booking b
    JOIN terapis t ON b.id_terapis = t.id
    WHERE MONTH(b.tanggal_booking) = MONTH(CURRENT_DATE()) AND YEAR(b.tanggal_booking) = YEAR(CURRENT_DATE())
    GROUP BY b.id_terapis
    ORDER BY total_booking DESC
    LIMIT 1;
";
$stmtTerapisTerbaik = $conn->prepare($queryTerapisTerbaik);
$stmtTerapisTerbaik->execute();
$resultTerapisTerbaik = $stmtTerapisTerbaik->get_result();
$terapisTerbaik = $resultTerapisTerbaik->fetch_assoc();


// Query untuk penjualan semua cabang
$queryCabangPenjualan = "
    SELECT c.nama_cabang, SUM(b.harga_total) AS total_omset
    FROM booking b
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE MONTH(b.tanggal_booking) = MONTH(CURRENT_DATE()) AND YEAR(b.tanggal_booking) = YEAR(CURRENT_DATE())
    GROUP BY b.id_cabang
    ORDER BY total_omset DESC;
";
$stmtCabangPenjualan = $conn->prepare($queryCabangPenjualan);
$stmtCabangPenjualan->execute();
$resultCabangPenjualan = $stmtCabangPenjualan->get_result();

// Query untuk terapis terlaris
$queryTerapisTerlaris = "
    SELECT t.nama_terapis, COUNT(b.id) AS total_booking
    FROM booking b
    JOIN terapis t ON b.id_terapis = t.id
    WHERE MONTH(b.tanggal_booking) = MONTH(CURRENT_DATE()) AND YEAR(b.tanggal_booking) = YEAR(CURRENT_DATE())
    GROUP BY b.id_terapis
    ORDER BY total_booking DESC;
";
$stmtTerapisTerlaris = $conn->prepare($queryTerapisTerlaris);
$stmtTerapisTerlaris->execute();
$resultTerapisTerlaris = $stmtTerapisTerlaris->get_result();

include '_header.php'; // Header halaman
?>

<div class="row mt-4">
	  <div class="col-md-12 grid-margin">
		<div class="row">
		  <div class="col-12 col-xl-8 mb-4 mb-xl-0">
			<h3 class="font-weight-bold">Dashboard</h3>
			<h6 class="font-weight-normal mb-0">All systems are running smoothly.</h6>
		  </div>
		</div>
	  </div>
		<!-- Lead Hari Ini -->
		<div class="col-md-4">
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
		<div class="col-md-4">
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
		<div class="col-md-4">
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
		<div class="col-md-4 mt-3">
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
		
		<div class="col-md-4 mt-3">
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

<?php 
// Mendapatkan bulan dan tahun saat ini
$currentMonth = date('m');
$currentYear = date('Y');

// Query untuk mendapatkan data penjualan harian
$query = "SELECT DATE(tanggal_booking) AS tanggal, SUM(harga_total) AS total_penjualan
          FROM booking
          WHERE MONTH(tanggal_booking) = ? AND YEAR(tanggal_booking) = ? AND status IN ('Completed','Confirmed')
          GROUP BY DATE(tanggal_booking)
          ORDER BY DATE(tanggal_booking) ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

// Memproses hasil query
$penjualanHarian = [];
while ($row = $result->fetch_assoc()) {
    $penjualanHarian[$row['tanggal']] = $row['total_penjualan'];
}

// Menutup koneksi
$stmt->close();
$conn->close();

// Menyiapkan data untuk Chart.js
$tanggalArray = [];
$penjualanArray = [];
for ($i = 1; $i <= date('t'); $i++) {
    $tanggal = sprintf("%s-%s-%02d", $currentYear, $currentMonth, $i);
    $tanggalArray[] = $tanggal;
    $penjualanArray[] = $penjualanHarian[$tanggal] ?? 0; // Nilai 0 jika tidak ada data pada tanggal tertentu
}
?>


<div class="row mt-4">
    <!-- Grafik Penjualan -->
    <div class="col-md-8">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);">
            <div class="card-body">
                <canvas id="chartPenjualan"></canvas>
            </div>
        </div>
    </div>

    <!-- Cabang Terbaik dan Terapis Terbaik -->
    <div class="col-md-4">
        <!-- Cabang Terbaik -->
        <div class="card mb-4" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);">
            <div class="card-body">
                <h5 class="card-title">Cabang Terbaik Bulan Ini</h5>
                <p>Nama Cabang: <strong><?php echo htmlspecialchars($cabangTerbaik['nama_cabang'] ?? '-'); ?></strong></p>
                <p>Total Omset: <strong>Rp<?php echo number_format($cabangTerbaik['total_omset'] ?? 0, 0, ',', '.'); ?></strong></p>
            </div>
        </div>
        <!-- Terapis Terbaik -->
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);">
            <div class="card-body">
                <h5 class="card-title">Terapis Terbaik Bulan Ini</h5>
                <p>Nama Terapis: <strong><?php echo htmlspecialchars($terapisTerbaik['nama_terapis'] ?? '-'); ?></strong></p>
                <p>Total Booking: <strong><?php echo htmlspecialchars($terapisTerbaik['total_booking'] ?? 0); ?></strong></p>
            </div>
        </div>
    </div>
</div>




<!-- Tampilkan Data -->
<div class="row mt-4">
    <!-- Penjualan Semua Cabang -->
    <div class="col-md-6">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Penjualan Semua Cabang Bulan Ini</h5>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Cabang</th>
                            <th>Total Omset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        while ($cabang = $resultCabangPenjualan->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($cabang['nama_cabang']); ?></td>
                                <td>Rp<?= number_format($cabang['total_omset'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Terapis Terlaris -->
    <div class="col-md-6">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Data Terapis Bulan Ini</h5>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Terapis</th>
                            <th>Total Booking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        while ($terapis = $resultTerapisTerlaris->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($terapis['nama_terapis']); ?></td>
                                <td><?= htmlspecialchars($terapis['total_booking']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
	.chart-container {
		width: 100%;
		max-width: 100%; /* Maksimal lebar mengikuti kontainer */
		height: auto; /* Tinggi akan menyesuaikan */
	}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.js"></script>
<script>
// Data untuk grafik
const labels = <?= json_encode($tanggalArray); ?>;
const data = {
	labels: labels,
	datasets: [{
		label: 'Penjualan Harian (Rp)',
		data: <?= json_encode($penjualanArray); ?>,
		borderColor: 'rgba(75, 192, 192, 1)',
		backgroundColor: 'rgba(75, 192, 192, 0.2)',
		fill: true,
		tension: 0.3,
		pointStyle: 'circle',
		pointRadius: 5,
		pointHoverRadius: 7,
	}]
};

// Konfigurasi Chart.js
const config = {
	type: 'line',
	data: data,
	options: {
		responsive: true,
		plugins: {
			legend: {
				display: true,
				position: 'top',
			}
		},
		scales: {
			x: {
                title: {
                    display: true,
                    text: 'Tanggal'
                },
            },
            y: {
                title: {
                    display: true,
                    text: 'Omset (Rp)'
                },
                ticks: {
                    padding: 2, // Kurangi jarak padding
                    font: {
                        size: 12 // Perkecil ukuran font
                    }
                },
				beginAtZero: true
			}
		}
	}
};

// Render grafik
const ctx = document.getElementById('chartPenjualan').getContext('2d');
const chartPenjualan = new Chart(ctx, config);
</script>
<?php include '_footer.php'; // Footer halaman ?>
