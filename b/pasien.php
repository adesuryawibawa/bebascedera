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

// Ambil id_cabang dari session login
$id_cabang = $_SESSION['cabang'] ?? 0;

// Ambil data pasien
$queryPasien = "SELECT id, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, nomor_wa, email, alamat, keterangan,poc_image, soap_image FROM pasien WHERE id_cabang = ?";
$stmtPasien = $conn->prepare($queryPasien);
$stmtPasien->bind_param("i", $id_cabang);
$stmtPasien->execute();
$resultPasien = $stmtPasien->get_result();
$patients = $resultPasien->fetch_all(MYSQLI_ASSOC);

include '_header.php'; // Header halaman
?>

<div class="row">
	<div class="col-md-12 grid-margin">
		<div class="row">
			<div class="col-12 col-xl-8 mb-4 mb-xl-0">
				<h3 class="font-weight-bold">Manajemen Pasien (Customer)</h3>
				<h6 class="font-weight-normal mb-0">Semua pasien (customer) yang terdaftar
					<a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">+ Tambah Pasien</a>
				</h6>
			</div>
		</div>
	</div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <table class="table table-hover table-striped" id="patientTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Jenis Kelamin</th>
                    <th>Tempat Lahir</th>
                    <th>Tanggal Lahir</th>
					<th>SOAP</th>
                    <th>POC</th>
                    <th>Nomor WA</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($patients as $patient): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($patient['nama'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($patient['jenis_kelamin'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($patient['tempat_lahir'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($patient['tanggal_lahir'] ?? '-'); ?></td>
					<td class="text-center">
						<?php if (!empty($patient['soap_image'])): ?>
							<a href="#" data-bs-toggle="modal" data-bs-target="#viewSOAPModal" data-file="../assets/soap/<?= $patient['soap_image']; ?>">
								<i class="fa fa-file-image-o"></i>
							</a>&nbsp;
							<a href="#" data-bs-toggle="modal" data-bs-target="#editSOAPModal" data-id="<?= $patient['id']; ?>">
								<i class="fa fa-pencil"></i>
							</a>
						<?php else: ?>
							<a href="#" data-bs-toggle="modal" data-bs-target="#uploadSOAPModal" data-id="<?= $patient['id']; ?>">
								<i class="fa fa-cloud-upload"></i>
							</a>
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php if (!empty($patient['poc_image'])): ?>
							<a href="#" data-bs-toggle="modal" data-bs-target="#viewPOCModal" data-file="../assets/poc/<?= $patient['poc_image']; ?>">
								<i class="fa fa-file-image-o"></i>  
							</a>&nbsp;
							<a href="#" data-bs-toggle="modal" data-bs-target="#editPOCModal" data-id="<?= $patient['id']; ?>">
								<i class="fa fa-pencil"></i>
							</a>
						<?php else: ?>
							<a href="#" data-bs-toggle="modal" data-bs-target="#uploadPOCModal" data-id="<?= $patient['id']; ?>">
								<i class="fa fa-cloud-upload"></i>
							</a>
						<?php endif; ?>
					</td>


                    <td><?= htmlspecialchars($patient['nomor_wa'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($patient['email'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($patient['alamat'] ?? '-'); ?></td>
					
                    <td><?= htmlspecialchars($patient['keterangan'] ?? '-'); ?></td>
                    <td><a href="#" class="btn btn-danger btn-sm" 
                           data-bs-toggle="modal" 
                           data-bs-target="#deletePatientModal"
                           data-id="<?= $patient['id']; ?>"
                           data-nama="<?= htmlspecialchars($patient['nama']); ?>">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pasien -->
<div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Tambah Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="pasien-add.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" name="nama" id="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" class="form-control" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nomor_wa">Nomor WA</label>
                        <input type="tel" name="nomor_wa" id="nomor_wa" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea name="alamat" id="alamat" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Pasien -->
<div class="modal fade" id="editPatientModal" tabindex="-1" aria-labelledby="editPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPatientModalLabel">Edit Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="pasien-update.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_nama">Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-control" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_tempat_lahir">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_nomor_wa">Nomor WA</label>
                        <input type="tel" name="nomor_wa" id="edit_nomor_wa" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_alamat">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_keterangan">Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control"></textarea>
                    </div>
              
            </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-warning">Update</button>
			</div>
		  </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Pasien -->
<div class="modal fade" id="deletePatientModal" tabindex="-1" aria-labelledby="deletePatientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePatientModalLabel">Hapus Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pasien ini?</p>
                <form action="pasien-delete.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" id="delete_id" name="id">
                    <p id="delete_nama"></p>
                </form>
            </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-danger">Hapus</button>
			</div>
		</form>
        </div>
    </div>
</div>
<!-- Modal Upload SOAP -->
<div class="modal fade" id="uploadSOAPModal" tabindex="-1" aria-labelledby="uploadSOAPModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="upload-file.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadSOAPModalLabel">Upload SOAP File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pasien" id="soapPatientId">
                    <input type="hidden" name="file_type" value="soap">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="soapFile" class="form-label">Pilih File</label>
                        <input type="file" class="form-control" id="soapFile" name="file" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Upload POC -->
<div class="modal fade" id="uploadPOCModal" tabindex="-1" aria-labelledby="uploadPOCModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="upload-file.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadPOCModalLabel">Upload POC File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pasien" id="pocPatientId">
                    <input type="hidden" name="file_type" value="poc">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="pocFile" class="form-label">Pilih File</label>
                        <input type="file" class="form-control" id="pocFile" name="file" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSOAPModal" tabindex="-1" aria-labelledby="editSOAPModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="upload-file.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSOAPModalLabel">Edit SOAP File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pasien" id="editSOAPPatientId">
                    <input type="hidden" name="file_type" value="soap">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="editSOAPFile" class="form-label">Pilih File Baru</label>
                        <input type="file" class="form-control" id="editSOAPFile" name="file" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editPOCModal" tabindex="-1" aria-labelledby="editPOCModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="upload-file.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPOCModalLabel">Edit POC File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pasien" id="editPOCPatientId">
                    <input type="hidden" name="file_type" value="poc">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="editPOCFile" class="form-label">Pilih File Baru</label>
                        <input type="file" class="form-control" id="editPOCFile" name="file" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update File</button>
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


<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
    jqOld('#patientTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
		"scrollX": true,
        "responsive": false
    });
});

// Tangani pengeditan pasien
const editPatientModal = document.getElementById('editPatientModal');
editPatientModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('edit_nama').value = button.getAttribute('data-nama');
    document.getElementById('edit_jenis_kelamin').value = button.getAttribute('data-jenis-kelamin');
    document.getElementById('edit_tempat_lahir').value = button.getAttribute('data-tempat-lahir');
    document.getElementById('edit_tanggal_lahir').value = button.getAttribute('data-tanggal-lahir');
    document.getElementById('edit_nomor_wa').value = button.getAttribute('data-nomor-wa');
    document.getElementById('edit_email').value = button.getAttribute('data-email');
    document.getElementById('edit_alamat').value = button.getAttribute('data-alamat');
    document.getElementById('edit_keterangan').value = button.getAttribute('data-keterangan');
});

// Tangani penghapusan pasien
const deletePatientModal = document.getElementById('deletePatientModal');

deletePatientModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const patientId = button.getAttribute('data-id');
    const patientName = button.getAttribute('data-nama');

    // Tampilkan data di modal
    document.getElementById('delete_id').value = patientId;
    document.getElementById('delete_nama').textContent = patientName;

    // Tombol hapus
    const deleteButton = deletePatientModal.querySelector('.btn-danger');
    deleteButton.onclick = function () {
        // Kirim permintaan penghapusan menggunakan Fetch API
        fetch('pasien-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
            },
            body: JSON.stringify({ id: patientId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Refresh halaman atau hapus baris dari tabel
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus pasien.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memproses permintaan.');
        });
    };
});

document.addEventListener('DOMContentLoaded', function () {
    // View SOAP Modal
    const viewSOAPModal = document.getElementById('viewSOAPModal');
    viewSOAPModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const file = button.getAttribute('data-file');
        document.getElementById('soapImageView').src = file;
    });

    // View POC Modal
    const viewPOCModal = document.getElementById('viewPOCModal');
    viewPOCModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const file = button.getAttribute('data-file');
        document.getElementById('pocImageView').src = file;
    });

    // Upload SOAP Modal
    const uploadSOAPModal = document.getElementById('uploadSOAPModal');
    uploadSOAPModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const patientId = button.getAttribute('data-id');
        document.getElementById('soapPatientId').value = patientId;
    });

    // Upload POC Modal
    const uploadPOCModal = document.getElementById('uploadPOCModal');
    uploadPOCModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const patientId = button.getAttribute('data-id');
        document.getElementById('pocPatientId').value = patientId;
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Edit SOAP Modal
    const editSOAPModal = document.getElementById('editSOAPModal');
    editSOAPModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const patientId = button.getAttribute('data-id');
        document.getElementById('editSOAPPatientId').value = patientId;
    });

    // Edit POC Modal
    const editPOCModal = document.getElementById('editPOCModal');
    editPOCModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const patientId = button.getAttribute('data-id');
        document.getElementById('editPOCPatientId').value = patientId;
    });
});


</script>

<?php include '_footer.php'; ?>
