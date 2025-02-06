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

// Cek apakah level pengguna adalah Admin Cabang
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php';
$id_cabang = $_SESSION['cabang'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Ambil semua kategori dari database
$query = "SELECT * FROM expense_categories WHERE id_cabang = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['cabang']);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);

include '_header.php';
?>

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">Manajemen Kategori Pengeluaran</h3>
        <h6 class="font-weight-normal mb-0">Tambah dan kelola kategori pengeluaran untuk cabang Anda.</h6>
    </div>
</div>

<div class="row">
    <!-- Pesan Sukses/Error -->
    <div class="col-md-12">
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
    </div>
</div>

<div class="row">
    <!-- Form Tambah Kategori -->
    <div class="col-md-5">
	 <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Tambah Kategori</h5>
                <form action="expense-category-add.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="kategori">Nama Kategori</label>
                        <input type="text" id="kategori" name="kategori" class="form-control" placeholder="Contoh: Air, Listrik" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Daftar Kategori -->
    <div class="col-md-7">
		<div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Daftar Kategori</h5>
                <div class="table-responsive">
                    <table class="table table-hover" id="expenseCategoryTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td>
                                    <button 
                                        class="btn btn-danger btn-sm delete-category" 
                                        data-id="<?php echo $category['id']; ?>"
                                        data-kategori="<?php echo htmlspecialchars($category['name']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteCategoryModal">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Kategori -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Hapus Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="expense-category-delete.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" id="delete-id" name="id">
                    <p>Apakah Anda yakin ingin menghapus kategori <strong id="delete-kategori"></strong>?</p>
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
    jqOld('#expenseCategoryTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": false,
        "ordering": false,
        "searching": false,
        "responsive": false
    });
});

// Populasi data modal hapus kategori
const deleteCategoryModal = document.getElementById('deleteCategoryModal');
deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const kategori = button.getAttribute('data-kategori');
    deleteCategoryModal.querySelector('#delete-id').value = id;
    deleteCategoryModal.querySelector('#delete-kategori').textContent = kategori;
});
</script>

<?php include '_footer.php'; ?>
