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

// Cek apakah level pengguna adalah "BOD"
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; 
    header("Location: ../login.php"); 
    exit;
}

// Buat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '_header.php';
include '../includes/config.php'; // Koneksi ke database

// Fetch data cabang
$cabang = [];
$sql = "SELECT * FROM cabang ORDER BY id_cabang DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cabang[] = $row;
    }
}
?>


<div class="container">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Data Cabang</h3>
                    <h6 class="font-weight-normal mb-0">Daftar cabang yang terdaftar di sistem
                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addCabangModal">+ Tambah Cabang</a>
                    </h6>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover table-striped" id="cabangTable">
            <thead>
                <tr>
					<th>No.</th>
                    <th>Nama Cabang</th>
                    <th>Kota</th>
                    <th>Link Google Map</th>
                    <th>Jam Buka</th>
                    <th>Jam Tutup</th>
                    <th>Kapasitas Bed</th>
                    <th>Kontak Cabang</th>					
                    <th>Alamat</th>
                    <th>PIC</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($cabang as $row): ?>
                    <tr>
						<td data-label="No."><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_cabang']) ?></td>
                        <td><?= htmlspecialchars($row['kota']) ?></td>
                        <td><a href="<?= htmlspecialchars($row['link_google_map']) ?>" target="_blank">View Map</a></td>
                        <td><?= htmlspecialchars($row['jam_buka']) ?></td>
                        <td><?= htmlspecialchars($row['jam_tutup']) ?></td>
                        <td><?= htmlspecialchars($row['kapasitas_bed']) ?></td>
                        <td><?= htmlspecialchars($row['kontak_cabang']) ?></td>
						
                        <td><?= htmlspecialchars($row['alamat']) ?></td>
                        <td><?= htmlspecialchars($row['pic']) ?></td>
                        <td data-label="Aksi">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#editCabangModal" 
                               data-id="<?= $row['id_cabang'] ?>"
                               data-nama="<?= htmlspecialchars($row['nama_cabang']) ?>"
                               data-alamat="<?= htmlspecialchars($row['alamat']) ?>"
                               data-kota="<?= htmlspecialchars($row['kota']) ?>"
                               data-link="<?= htmlspecialchars($row['link_google_map']) ?>"
                               data-jam_buka="<?= htmlspecialchars($row['jam_buka']) ?>"
                               data-jam_tutup="<?= htmlspecialchars($row['jam_tutup']) ?>"
                               data-kapasitas="<?= htmlspecialchars($row['kapasitas_bed']) ?>"
                               data-kontak="<?= htmlspecialchars($row['kontak_cabang']) ?>"
                               data-pic="<?= htmlspecialchars($row['pic']) ?>">
                                Edit</a> |
                            <a href="#" 
							   data-bs-toggle="modal" 
							   data-bs-target="#deleteCabangModal" 
							   data-id="<?= $row['id_cabang'] ?>" 
							   data-name="<?= htmlspecialchars($row['nama_cabang']) ?>">Hapus</a>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Cabang -->
<div class="modal fade" id="addCabangModal" tabindex="-1" aria-labelledby="addCabangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="cabang-process.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCabangModalLabel">Tambah Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
					<div class="row">
						<div class="col-md-8">
							<div class="mb-3">
								 <label for="namaCabang" class="form-label">Nama Cabang</label>
								<input type="text" class="form-control" id="namaCabang" name="nama_cabang" required>
							</div>
						</div>
                    </div>
                    <div class="mb-3">
                        <label for="alamatCabang" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamatCabang" name="alamat" required></textarea>
                    </div>
					<div class="row">
						<div class="col-md-8">
						<div class="mb-3">
							<label for="kotaCabang" class="form-label">Kota</label>
							<input type="text" class="form-control" id="kotaCabang" name="kota" required>
						</div>
						</div>
                    </div>
                    <div class="mb-3">
                        <label for="linkGoogleMap" class="form-label">Link Google Map</label>
                        <input type="text" class="form-control" id="linkGoogleMap" name="link_google_map">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="jamBuka" class="form-label">Jam Buka</label>
                                <input type="time" class="form-control" id="jamBuka" name="jam_buka" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="jamTutup" class="form-label">Jam Tutup</label>
                                <input type="time" class="form-control" id="jamTutup" name="jam_tutup" required>
                            </div>
                        </div>
                    </div>
					<div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="kapasitasBed" class="form-label">Kapasitas Bed</label>
								<input type="number" class="form-control" id="kapasitasBed" name="kapasitas_bed" required>
                            </div>
                        </div>
                    </div>
					<div class="row">
						<div class="col-md-5">
						<div class="mb-3">
							<label for="kontakCabang" class="form-label">Kontak Cabang</label>
							<input type="number" class="form-control" id="kontakCabang" name="kontak_cabang">
						</div>
                        </div>
						
						<div class="col-md-7">
						<div class="mb-3">
							<label for="picCabang" class="form-label">PIC</label>
							<input type="text" class="form-control" id="picCabang" name="pic">
						</div>
						</div>
                    </div>
                  
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Cabang -->
<div class="modal fade" id="editCabangModal" tabindex="-1" aria-labelledby="editCabangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="cabang-update.php" method="POST" id="editCabangForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCabangModalLabel">Edit Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCabangId" name="id_cabang">
                    <div id="editCabangFeedback" class="text-danger mb-2" style="display:none;"></div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="editNamaCabang" class="form-label">Nama Cabang</label>
                                <input type="text" class="form-control" id="editNamaCabang" name="nama_cabang" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editAlamatCabang" class="form-label">Alamat</label>
                        <textarea class="form-control" id="editAlamatCabang" name="alamat" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="editKotaCabang" class="form-label">Kota</label>
                                <input type="text" class="form-control" id="editKotaCabang" name="kota" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editLinkGoogleMap" class="form-label">Link Google Map</label>
                        <input type="text" class="form-control" id="editLinkGoogleMap" name="link_google_map">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editJamBuka" class="form-label">Jam Buka</label>
                                <input type="time" class="form-control" id="editJamBuka" name="jam_buka" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editJamTutup" class="form-label">Jam Tutup</label>
                                <input type="time" class="form-control" id="editJamTutup" name="jam_tutup" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editKapasitasBed" class="form-label">Kapasitas Bed</label>
                                <input type="number" class="form-control" id="editKapasitasBed" name="kapasitas_bed" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="editKontakCabang" class="form-label">Kontak Cabang</label>
                                <input type="text" class="form-control" id="editKontakCabang" name="kontak_cabang">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="editPicCabang" class="form-label">PIC</label>
                                <input type="text" class="form-control" id="editPicCabang" name="pic">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Cabang -->
<div class="modal fade" id="deleteCabangModal" tabindex="-1" aria-labelledby="deleteCabangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="cabang-delete.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCabangModalLabel">Konfirmasi Hapus Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus cabang <strong id="deleteCabangName"></strong>?</p>
                    <input type="hidden" name="id_cabang" id="deleteCabangId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
if (jqOld('#cabangTable').length) {
	jqOld('#cabangTable').DataTable({
		responsive: true, // Responsivitas tabel
		paging: true, // Pagination
		searching: true, // Pencarian
		ordering: false, // Pengurutan
		info: true, // Informasi tabel
		lengthMenu: [5, 10, 25, 50], // Jumlah baris per halaman
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


document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editCabangModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Tombol yang diklik untuk membuka modal
        const cabangData = {
            id: button.getAttribute('data-id'),
            nama: button.getAttribute('data-nama'),
            alamat: button.getAttribute('data-alamat'),
            kota: button.getAttribute('data-kota'),
            link: button.getAttribute('data-link'),
            jam_buka: button.getAttribute('data-jam_buka'),
            jam_tutup: button.getAttribute('data-jam_tutup'),
            kapasitas: button.getAttribute('data-kapasitas'),
            kontak: button.getAttribute('data-kontak'),
            pic: button.getAttribute('data-pic')
        };

        // Isi form dengan data dari atribut
        document.getElementById('editCabangId').value = cabangData.id;
        document.getElementById('editNamaCabang').value = cabangData.nama;
        document.getElementById('editAlamatCabang').value = cabangData.alamat;
        document.getElementById('editKotaCabang').value = cabangData.kota;
        document.getElementById('editLinkGoogleMap').value = cabangData.link;
        document.getElementById('editJamBuka').value = cabangData.jam_buka;
        document.getElementById('editJamTutup').value = cabangData.jam_tutup;
        document.getElementById('editKapasitasBed').value = cabangData.kapasitas;
        document.getElementById('editKontakCabang').value = cabangData.kontak;
        document.getElementById('editPicCabang').value = cabangData.pic;
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const deleteCabangModal = document.getElementById('deleteCabangModal');
    deleteCabangModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const cabangId = button.getAttribute('data-id');
        const cabangName = button.getAttribute('data-name');

        const deleteCabangIdInput = document.getElementById('deleteCabangId');
        const deleteCabangName = document.getElementById('deleteCabangName');

        deleteCabangIdInput.value = cabangId;
        deleteCabangName.textContent = cabangName;
    });
});

</script>

<?php include '_footer.php'; ?>
