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

// Cek apakah level pengguna adalah "cabang"
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Ambil id_cabang dari session
$id_cabang = $_SESSION['cabang'] ?? null;
if (!$id_cabang) {
    $_SESSION['error_message'] = "Cabang tidak ditemukan dalam sesi Anda.";
    header("Location: ../login.php");
    exit;
}

// Buat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


include '_header.php';
include '../includes/config.php'; // Koneksi ke database

// Ambil data booking dari database
$bookings = [];
$sql = "
  SELECT 
    b.id AS booking_id, 
    b.tanggal_booking, 
    b.waktu_booking, 
    p.nama AS nama_pasien, 
    p.soap_image,
    p.poc_image,
    p.nomor_wa,
    p.email,
    t.nama_terapis AS nama_terapis,
    b.harga_total, 
    COALESCE(SUM(ba.harga_total), 0) AS total_harga_addons, -- Menjumlahkan harga addons
    (b.harga_total + COALESCE(SUM(ba.harga_total), 0)) AS grand_total, -- Total booking + addons
    b.status,
    b.kode_promo,
    pro.nama_produk
FROM booking AS b
LEFT JOIN pasien AS p ON b.id_pasien = p.id
LEFT JOIN terapis AS t ON b.id_terapis = t.id
LEFT JOIN produk AS pro ON b.id_produk = pro.id
LEFT JOIN booking_addons AS ba ON b.id = ba.booking_id -- JOIN dengan booking_addons
WHERE b.id_cabang = ?
GROUP BY 
    b.id, b.tanggal_booking, b.waktu_booking, p.nama, p.soap_image, p.poc_image, 
    p.nomor_wa, p.email, t.nama_terapis, b.harga_total, b.status, b.kode_promo, pro.nama_produk
ORDER BY 
    CASE 
        WHEN DATE(b.tanggal_booking) = CURDATE() THEN 1  -- Tanggal hari ini diletakkan paling atas
        ELSE 2                                           -- Tanggal lainnya setelahnya
    END,
    b.tanggal_booking DESC,  -- Tanggal terbaru
    b.waktu_booking ASC;     -- Waktu booking dari pagi ke malam
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cabang);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
$stmt->close();

// Ambil data terapis 
$queryTerapis = "SELECT id, nama_terapis FROM terapis WHERE id_cabang = ?";
$stmtTerapis = $conn->prepare($queryTerapis);
$stmtTerapis->bind_param("i", $id_cabang);
$stmtTerapis->execute();
$resultTerapis = $stmtTerapis->get_result();
?>

<style>
.success {
    color: green !important;
}

.error {
    color: red !important;
}

.badge-status {
    font-size: 10px;
    padding: 2px 5px;
    border-radius: 8px;
    text-transform: uppercase;
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}

.badge-warning {
    background-color: #ffc107;
    color: #fff;
}

.badge-danger {
    background-color: #dc3545;
    color: #fff;
}

.uppercase {
    text-transform: uppercase;
}

/* CSS untuk mengatur tabel memenuhi lebar modal */
#absensiModal .table {
    width: 100%; /* Tabel memenuhi lebar parent */
}

#absensiModal .table th,
#absensiModal .table td {
    padding: 8px 12px; /* Sesuaikan padding */
    white-space: nowrap; /* Mencegah teks wrap */
}

#absensiModal .table thead th {
    background-color: #e32d00; /* Warna latar header */
    color: white; /* Warna teks header */
    font-weight: bold; /* Tebalkan teks header */
    text-align: center; /* Pusatkan teks header */
}

#absensiModal .table tbody td {
    vertical-align: middle; /* Pusatkan konten secara vertikal */
}

#absensiModal .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05); /* Warna strip pada baris ganjil */
}

#absensiModal .table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075); /* Efek hover pada baris */
}
</style>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="font-weight-bold">Data Booking</h3>
            <h6 class="font-weight-normal">Menampilkan semua data booking untuk cabang Anda.</h6>
        </div>
    </div>

    <!-- Tampilkan pesan sukses atau error -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Tabel Data Booking -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped" id="bookingTable">
            <thead class="bg-success text-white">
                <tr>
                    <th width="5%">No</th>
					<th width="5%">NO INV.</th>
                    <th>Tanggal Booking</th>
                    <th class="text-center">Waktu Booking</th>					
                    <th>Status</th>
                    <th>Nama Pasien</th>
					<th>Absensi</th> 
                    <th>SOAP</th>
                    <th>POC</th>
					<th>Nama Produk</th>
                    <th>Terapis</th>
                    <th>Promo</th>
                    <th>Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bookings)): ?>
                    <?php $no = 1; foreach ($bookings as $booking): ?>
                        <tr class="uppercase">
                            <td><?= $no++; ?></td>
                            <td>INV00<?= htmlspecialchars($booking['booking_id'] ?? '') ?> 
							<?php 
							if($booking['status'] === 'Cancelled'){
								echo "";
							}else{
							?>
							<!-- Update Status -->
							<a href="#" 
							   data-bs-toggle="modal" 
							   data-bs-target="#detailModal" 
							   data-id="<?= htmlspecialchars($booking['booking_id'] ?? '') ?>" 
							   data-tanggal="<?= htmlspecialchars($booking['tanggal_booking'] ?? '') ?>" 
							   data-waktu="<?= htmlspecialchars($booking['waktu_booking'] ?? '') ?>" 
							   data-nama="<?= htmlspecialchars($booking['nama_pasien'] ?? 'Tidak Diketahui') ?>" 
							   data-terapis="<?= htmlspecialchars($booking['nama_terapis'] ?? '-') ?>" 
							   data-promo="<?= htmlspecialchars($booking['kode_promo'] ?? '') ?>" 
							   data-harga="<?= number_format($booking['grand_total'] ?? 0, 0, ',', '.') ?>" 
							   data-status="<?= htmlspecialchars($booking['status'] ?? '-') ?>" 
							   data-wa="<?= htmlspecialchars($booking['nomor_wa'] ?? '082114084575') ?>" 
							   data-idcabang="<?= htmlspecialchars($id_cabang ?? '-') ?>" 
							   data-email="<?= htmlspecialchars($booking['email'] ?? 'noreply@bebascedera.com') ?>" 
							   class="text-primary">
							   <i class="fa fa-edit"></i>
							</a>
							<?php } ?>
							</td>
                            <td><?= date('d-M-Y', strtotime($booking['tanggal_booking'])); ?> &nbsp;</td>
                            <td class="text-center"><?= htmlspecialchars($booking['waktu_booking']); ?></td>
							 <td>
                                <?php if ($booking['status'] === 'Confirmed'): ?>
                                    <span class="badge-status badge-success">Confirmed</span>
                                <?php elseif ($booking['status'] === 'Completed'): ?>
                                    <span class="badge-status badge-success">Completed</span>
								<?php elseif ($booking['status'] === 'Pending'): ?>
                                    <span class="badge-status badge-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge-status badge-danger">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td ><?= htmlspecialchars($booking['nama_pasien'] ?? 'N/A'); ?></td>
							<td class="text-center">
								<?php 
								if($booking['status'] === 'Cancelled'){
									echo "-";
								}else{
								?>
								<a href="#" 
								   data-bs-toggle="modal" 
								   data-bs-target="#absensiModal" 
								   data-booking-id="<?= htmlspecialchars($booking['booking_id'] ?? '') ?>"
								   data-cabang-id="<?= htmlspecialchars($id_cabang ?? '-') ?>">
									<i class="fa fa-clock-o"></i> <!-- Ikon jam -->
								</a>
								<?php } ?>
							</td>
							<?php
								// SOAP Image
								$soapImage = !empty($booking['soap_image']) && file_exists("../assets/soap/" . $booking['soap_image']) 
									? "../assets/soap/" . $booking['soap_image'] 
									: "../assets/images/no-images.png";

								// POC Image
								$pocImage = !empty($booking['poc_image']) && file_exists("../assets/poc/" . $booking['poc_image']) 
									? "../assets/poc/" . $booking['poc_image'] 
									: "../assets/images/no-images.png";
							?>
							<td class="text-center">
								<a href="#" data-bs-toggle="modal" data-bs-target="#viewSOAPModal" data-file="<?= $soapImage; ?>">
									<i class="fa fa-file-image-o"></i>
								</a>
							</td>
							<td class="text-center">
								<a href="#" data-bs-toggle="modal" data-bs-target="#viewPOCModal" data-file="<?= $pocImage; ?>">
									<i class="fa fa-file-image-o"></i>
								</a>
							</td>
							<td><?= htmlspecialchars($booking['nama_produk']); ?></td>
                            <td><?= htmlspecialchars($booking['nama_terapis'] ?? 'N/A'); ?></td>
                            <td>
								<?php if (!empty($booking['kode_promo'])): ?>
									<span class="badge-status badge-info text-white">
										<?= htmlspecialchars($booking['kode_promo']); ?>
									</span>
								<?php else: ?>
									<!-- Tidak menampilkan apapun jika kode_promo kosong -->
								<?php endif; ?>
							</td>
                            <td>Rp <?= number_format($booking['grand_total'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="text-center">Belum ada data booking untuk cabang ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Status Booking -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="update-status.php" method="POST">
				<!-- CSRF Token -->
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
				
				<!-- ID Booking -->
				<input type="hidden" name="id_booking" id="modalBookingId" value="">
				<input type="hidden" name="wa" id="wa" value="">
				<input type="hidden" name="email" id="email" value="">
				<input type="hidden" name="idCabang" id="idCabang" value="">
				
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
						<div class="col-md-6">
							<label for="modalTanggal" class="form-label">Tanggal Booking</label>
							<input type="text" class="form-control" id="modalTanggal" name="tanggalBooking" readonly>
						</div>
						<div class="col-md-6">
							<label for="modalWaktu" class="form-label">Waktu Booking</label>
							<input type="text" class="form-control" id="modalWaktu" name="waktuBooking" readonly>
						</div>
					</div>

                    <div class="mb-3">
                        <label for="modalNama" class="form-label">Nama Pasien</label>
                        <input type="text" class="form-control" id="modalNama" name="nama" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="modalTerapis" class="form-label">Terapis</label>
                        <input type="text" class="form-control" id="modalTerapis" readonly>
                    </div>
                   <div class="row mb-3">
						<div class="col-md-6">
							<label for="modalPromo" class="form-label">Kode Promo</label>
							<input type="text" class="form-control" id="modalPromo" readonly>
						</div>
						<div class="col-md-6">
							<label for="modalHarga" class="form-label">Total Harga + Add-ons</label>
							<input type="text" class="form-control" id="modalHarga" name="hargaTotal" readonly>
						</div>
					</div>

                     <!-- Dropdown Status -->
					<div class="mb-3">
						<label for="status" class="form-label">Status Booking</label>
						<select name="status" id="statusBooking" class="form-control" required>
							<option value="" disabled>Pilih Status</option>	
							<option value="Confirmed">Confirmed</option>
							<option value="Pending">Pending</option>
							<option value="Cancelled">Cancelled</option>
						</select>
					</div>
                </div>
				<div class="mb-3">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Nama Produk</th>
								<th>Jumlah</th>
								<th>Sub Total</th>
							</tr>
						</thead>
						<tbody id="modalAddons">
							<!-- Data produk add-ons akan dimasukkan di sini -->
						</tbody>
					</table>
				</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View SOAP -->
<div class="modal fade" id="viewSOAPModal" tabindex="-1" aria-labelledby="viewSOAPModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSOAPModalLabel">SOAP File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="soapImageView" src="" alt="SOAP File" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<!-- Modal View POC -->
<div class="modal fade" id="viewPOCModal" tabindex="-1" aria-labelledby="viewPOCModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPOCModalLabel">POC File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="pocImageView" src="" alt="POC File" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Absensi -->
<div class="modal fade" id="absensiModal" tabindex="-1" aria-labelledby="absensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="proses-absensi.php" method="POST"> <!-- Form untuk mengirim data -->
				
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
				<!-- Input Hidden untuk ID Booking dan ID Cabang -->
				<input type="hidden" name="id_booking" id="modalIdBooking">
				<input type="hidden" name="id_cabang" id="modalIdCabang">
				
                <div class="modal-header">
                    <h5 class="modal-title" id="absensiModalLabel">Data Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Slot Availability -->
                    <div class="mb-4">
                        <div class="form-group row align-items-center">
                            <div class="col-md-6">
                                <label for="tanggalBooking">Tanggal Kedatangan</label>
                                <input type="date" name="tanggal_booking" id="tanggalBooking" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label for="waktuBooking">Jam</label>
                                <select name="waktu_booking" id="waktuBooking" class="form-control" required>
                                    <option value="" disabled selected>Pilih Jam</option>
                                    <!-- Opsi jam akan diisi secara dinamis -->
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="id_terapis">Terapis</label>
                            <select name="id_terapis" id="id_terapis" class="form-control" required>
                                <option value="" disabled selected>Pilih Terapis</option>
                                <?php while ($terapis = $resultTerapis->fetch_assoc()): ?>
                                    <option value="<?php echo $terapis['id']; ?>">
                                        <?php echo htmlspecialchars($terapis['nama_terapis']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Judul "Kehadiran Pasien" -->
                    <h5 class="mb-3">Kehadiran Pasien</h5>

                    <!-- Tabel Absensi -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Sesi</th>
                                    <th>Tanggal Treatment</th>
                                    <th>Jam</th>
                                    <th>Terapis</th>
									<th>Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="absensiData">
                                <!-- Data absensi akan dimuat di sini -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Submit</button> <!-- Tombol Submit -->
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
if (jqOld('#bookingTable').length) {
	jqOld('#bookingTable').DataTable({
		responsive: false, // Responsivitas tabel
		paging: true, // Pagination
		searching: true, // Pencarian
		ordering: false, // Pengurutan
		info: true, // Informasi tabel
		lengthMenu: [15, 25, 50, 100], // Jumlah baris per halaman
		language: {
			search: "Cari:",
			lengthMenu: "Tampilkan _MENU_ ",
			info: "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri.",
			paginate: {
				first: "Pertama",
				last: "Terakhir",
				next: "Berikutnya",
				previous: "Sebelumnya"
			},
			zeroRecords: "Tidak ada data yang cocok ditemukan"
		}
	});
}
});



// Modal Edit Status
document.addEventListener('DOMContentLoaded', function () {
    const detailModal = document.getElementById('detailModal');

    detailModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        // Ambil data dari atribut data-*
        const bookingId = button.getAttribute('data-id') || 'N/A';
        const tanggal = button.getAttribute('data-tanggal') || 'N/A';
        const waktu = button.getAttribute('data-waktu') || 'N/A';
        const nama = button.getAttribute('data-nama') || 'Tidak Diketahui';
        const terapis = button.getAttribute('data-terapis') || '-';
        const promo = button.getAttribute('data-promo') || '-';
        const harga = button.getAttribute('data-harga') || '0';
        const status = button.getAttribute('data-status') || 'N/A';		
        const wa = button.getAttribute('data-wa') || '082114084575';
        const idcabang = button.getAttribute('data-idcabang') || '082114084575';
        const email = button.getAttribute('data-email') || 'noreply@bebascedera.com';

        // Periksa elemen modal
        const modalBookingId = document.getElementById('modalBookingId');
        if (!modalBookingId) {
            return;
        }

        // Set data ke dalam input modal
        document.getElementById('modalTanggal').value = tanggal;
        document.getElementById('modalWaktu').value = waktu;
        document.getElementById('modalNama').value = nama;
        document.getElementById('modalTerapis').value = terapis;
        document.getElementById('modalPromo').value = promo;
        document.getElementById('modalHarga').value = `Rp ${harga}`;
        document.getElementById('modalBookingId').value = bookingId;
        document.getElementById('statusBooking').value = status;		
        document.getElementById('wa').value = wa;	
        document.getElementById('idCabang').value = idcabang;
        document.getElementById('email').value = email;

        // Kosongkan tabel Add-ons sebelum memuat data baru
        const modalAddons = document.getElementById('modalAddons');
        modalAddons.innerHTML = '<tr><td colspan="3" class="text-center">Memuat...</td></tr>';

        // AJAX untuk mengambil data booking_addons dari server
        fetch('get_booking_details.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_booking=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            // Kosongkan tabel sebelum menambahkan data baru
            modalAddons.innerHTML = '';

            if (data.addons.length > 0) {
                data.addons.forEach(addon => {
                    modalAddons.innerHTML += `
                        <tr>
                            <td>${addon.nama_produk}</td>
                            <td>${addon.jumlah}</td>
                            <td>Rp ${parseFloat(addon.harga_total).toLocaleString()}</td>
                        </tr>
                    `;
                });
            } else {
                modalAddons.innerHTML = '<tr><td colspan="3" class="text-center">Tidak ada produk add-ons</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalAddons.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data</td></tr>';
        });
    });
});


//Modal Absensi
document.addEventListener('DOMContentLoaded', function () {
    const absensiModal = document.getElementById('absensiModal');

    absensiModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const bookingId = button.getAttribute('data-booking-id');

        // Kosongkan tabel sebelum memuat data baru
        const absensiData = document.getElementById('absensiData');
        absensiData.innerHTML = '<tr><td colspan="5" class="text-center">Memuat...</td></tr>';

        // AJAX untuk mengambil data absensi dari server
        fetch('get_absensi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_booking=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            // Kosongkan tabel sebelum menambahkan data baru
            absensiData.innerHTML = '';

            if (data.length > 0) {
                data.forEach((absensi, index) => {
                    // Tambahkan tombol hapus hanya jika bukan baris pertama
                    const tombolHapus = index === 0 
                        ? '' // Baris pertama, tidak ada tombol hapus
                        : `<a href="hapus_absensi.php?id=${absensi.id}&cabang=${absensi.id_cabang}&idbooking=${absensi.booking_id}&tanggal=${absensi.tanggal_treatment}&jam=${absensi.waktu_booking}">
                                   <i class="fa fa-trash text-danger"></i>
                               </a>`;

                    absensiData.innerHTML += `
                        <tr>
                            <td class="text-center">${index + 1}</td> <!-- Nomor urut -->
                            <td class="text-center">${absensi.tanggal_treatment}</td>
                            <td class="text-center">${absensi.waktu_booking}</td>
                            <td class="text-left">${absensi.nama_terapis || 'N/A'}</td>
							<td class="text-left"> ${tombolHapus} <!-- Kolom aksi (tombol hapus) --></td>
                           
                        </tr>
                    `;
                });
            } else {
                absensiData.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data absensi</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            absensiData.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>';
        });
    });
});

// Fungsi untuk memuat waktu booking yang tersedia
document.getElementById('tanggalBooking').addEventListener('change', function () {
    const selectedDate = this.value;
    const waktuBookingSelect = document.getElementById('waktuBooking');

    // Set default loading state
    waktuBookingSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';

    // Fetch available times
    fetch(`get-available-times.php?tanggal=${selectedDate}&id_cabang=<?php echo $id_cabang; ?>`)
        .then(response => response.json())
        .then(data => {

            // Clear previous options
            waktuBookingSelect.innerHTML = '<option value="" disabled selected>Pilih Waktu Booking</option>';

            if (data.success && data.debug.slots) {
                // Iterate over slots
                data.debug.slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.jam; // Set value to jam
                    option.textContent = `${slot.jam} (${slot.status})`; // Display status
                    option.disabled = !slot.available; // Disable if not available
                    waktuBookingSelect.appendChild(option);
                });
            } else {
                alert(data.message || 'Gagal memuat waktu booking.');
            }
        })
        .catch(error => console.error('Error:', error));
});

document.addEventListener('DOMContentLoaded', function () {
    const absensiModal = document.getElementById('absensiModal');

    // Event listener saat modal akan ditampilkan
    absensiModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Tombol yang memicu modal

        // Ambil data dari atribut data-*
        const idBooking = button.getAttribute('data-booking-id');
        const idCabang = button.getAttribute('data-cabang-id');

        // Set nilai input hidden di modal
        document.getElementById('modalIdBooking').value = idBooking;
        document.getElementById('modalIdCabang').value = idCabang;
    });
});
</script>

<?php include '_footer.php'; ?>
