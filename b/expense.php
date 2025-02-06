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

include '../includes/config.php'; // Koneksi ke database

$id_cabang = $_SESSION['cabang'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Ambil daftar kategori pengeluaran
$queryCategories = "SELECT id, name FROM expense_categories WHERE id_cabang = '$id_cabang' ORDER BY name ASC";
$resultCategories = $conn->query($queryCategories);

// Ambil 15 transaksi terbaru
$queryExpenses = "
    SELECT e.id, e.amount, e.description, e.receipt_image, e.transaction_date, ec.name AS category_name, u.username 
    FROM expenses e
    JOIN expense_categories ec ON e.category_id = ec.id
    JOIN users u ON e.user_id = u.id
    WHERE e.id_cabang = ?
    ORDER BY e.transaction_date DESC
    LIMIT 15
";
$stmtExpenses = $conn->prepare($queryExpenses);
$stmtExpenses->bind_param("i", $id_cabang);
$stmtExpenses->execute();
$resultExpenses = $stmtExpenses->get_result();

include '_header.php';
?>

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">Manajemen Pengeluaran</h3>
        <h6 class="font-weight-normal mb-0">Kelola pengeluaran cabang Anda.</h6>
    </div>
</div>

<div class="row">
    <!-- Kolom Kiri: Form Tambah Pengeluaran -->
    <div class="col-md-5">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Tambah Pengeluaran</h5>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>

                <form action="expense-add.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select name="category_id" id="category" class="form-control" required>
                            <option value="" disabled selected>Pilih Kategori</option>
                            <?php while ($category = $resultCategories->fetch_assoc()): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Jumlah Pengeluaran (Rp.)</label>
                        <input type="number" name="amount" id="amount" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Tambahkan deskripsi pengeluaran"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="receipt">Upload Bukti Transaksi (Opsional)</label>
                        <input type="file" name="receipt_image" id="receipt" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="transaction_date">Tanggal dan Waktu Transaksi</label>
                        <input type="datetime-local" name="transaction_date" id="transaction_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Tabel Transaksi Terbaru -->
    <div class="col-md-7">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Transaksi Terbaru</h5>
				<div class="table-responsive">
                <table class="table table-hover" id="expenseTable">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Deskripsi</th>
                            <th>Tanggal</th>
                            <th>Bukti</th>
							<th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($expense = $resultExpenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                            <td>Rp. <?php echo number_format($expense['amount'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($expense['transaction_date']))); ?></td>
                            <td>
                                <?php if ($expense['receipt_image']): ?>
                                    <a href="../assets/expenses/<?php echo htmlspecialchars($expense['receipt_image']); ?>" target="_blank">Lihat</a>
                                <?php else: ?>
                                    <span class="text-muted">Tidak Ada</span>
                                <?php endif; ?>
                            </td>
							<td class="text-center">
								<a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $expense['id']; ?>" data-receipt="<?php echo $expense['receipt_image']; ?>">
									<i class="fas fa-trash"></i>
								</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($resultExpenses->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada transaksi.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
				</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Penghapusan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="expense-delete.php" method="POST">
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus data ini?
                    <input type="hidden" id="delete-id" name="id"> <!-- Sesuaikan nama field dengan backend -->
                    <input type="hidden" id="delete-receipt" name="receipt_image"> <!-- Sesuaikan nama field dengan backend -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="confirmDelete">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
    jqOld('#expenseTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": false,
        "ordering": false,
        "searching": false,
        "responsive": false
    });
});

//modal hapus
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget; // Tombol yang memicu modal
    const id = button.getAttribute('data-id'); // Ambil data-id
    const receipt = button.getAttribute('data-receipt'); // Ambil data-receipt

    // Isi data ke dalam modal
    deleteModal.querySelector('#delete-id').value = id;
    deleteModal.querySelector('#delete-receipt').value = receipt; // Gunakan value, bukan textContent
});
</script>
<?php include '_footer.php'; ?>
