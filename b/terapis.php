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

// Buat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '_header.php';
include '../includes/config.php'; // Koneksi ke database

// Ambil semua data terapis
$terapists = [];
$sql = "SELECT id, id_cabang, id_user, nama_terapis, created_at FROM terapis WHERE id_cabang = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['cabang']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $terapists[] = $row;
}

// Ambil semua data user dengan level 'employee' dari cabang yang sama
$users = [];
$sqlUsers = "SELECT id, fullname FROM users WHERE level IN ('employee','cabang') AND id_cabang = ? ORDER BY username ASC";
$stmtUsers = $conn->prepare($sqlUsers);
$stmtUsers->bind_param("i", $_SESSION['cabang']);
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();

while ($row = $resultUsers->fetch_assoc()) {
    $users[] = $row;
}
?>

<style>
.success {
    color: green !important;
}

.error {
    color: red !important;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Manajemen Terapis</h3>
                    <h6 class="font-weight-normal mb-0">Semua terapis yang terdaftar
                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addTerapisModal">+ Tambah Terapis</a>
                    </h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Tampilkan pesan sukses atau error -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover table-striped" id="terapisTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Terapis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($terapists as $terapis): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($terapis['nama_terapis']); ?></td>
                    <td>
                        <a href="#" 
                           data-bs-toggle="modal" 
                           data-bs-target="#deleteTerapisModal"
                           data-id="<?= $terapis['id']; ?>"
                           data-nama="<?= htmlspecialchars($terapis['nama_terapis']); ?>">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Terapis -->
<div class="modal fade" id="addTerapisModal" tabindex="-1" aria-labelledby="addTerapisModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="terapis-add.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTerapisModalLabel">Tambah Terapis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaTerapis" class="form-label">Nama Terapis</label>
                        <select class="form-control" id="namaTerapis" name="id_user" required>
                            <option value="">-- Pilih User --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['fullname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Tambah Terapis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Terapis -->
<div class="modal fade" id="deleteTerapisModal" tabindex="-1" aria-labelledby="deleteTerapisModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="terapis-delete.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTerapisModalLabel">Hapus Terapis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteTerapisMessage">Apakah Anda yakin ingin menghapus terapis ini?</p>
                    <input type="hidden" id="deleteTerapisId" name="id_terapis">
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
    jqOld('#terapisTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": false
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // Modal Delete
    document.querySelectorAll('a[data-bs-target="#deleteTerapisModal"]').forEach(link => {
        link.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');

            document.getElementById('deleteTerapisId').value = id;
            document.getElementById('deleteTerapisMessage').textContent = 
                `Apakah Anda yakin ingin menghapus terapis "${nama}"?`;
        });
    });
});
</script>

<?php include '_footer.php'; ?>
