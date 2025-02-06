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

// Ambil semua data promo
$promos = [];
$sql = "SELECT p.*, 
               c.nama_cabang,
               pr.nama_produk
        FROM promo p 
        LEFT JOIN cabang c ON p.id_cabang = c.id_cabang
        LEFT JOIN produk pr ON p.id_produk = pr.id
        WHERE p.id_cabang = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['cabang']); // Ambil id_cabang dari session
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $promos[] = $row;
}

// Ambil daftar produk untuk combo box
$produk = [];
$sqlProduk = "SELECT id, nama_produk FROM produk WHERE id_cabang = ? AND status = 'active' ORDER BY nama_produk ASC";
$stmtProduk = $conn->prepare($sqlProduk);
$stmtProduk->bind_param("i", $_SESSION['cabang']);
$stmtProduk->execute();
$resultProduk = $stmtProduk->get_result();

while ($row = $resultProduk->fetch_assoc()) {
    $produk[] = $row;
}
$stmtProduk->close();
?>

<style>
.success {
    color: green !important; /* Warna hijau untuk pesan sukses */
}

.error {
    color: red !important; /* Warna merah untuk pesan error */
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Manajemen Promo</h3>
                    <h6 class="font-weight-normal mb-0">Semua promo yang tersedia di cabang Anda
                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal">+ Tambah Promo</a>
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
        <table class="table table-hover table-striped" id="promoTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Promo</th>
                    <th>Deskripsi</th>
                    <th>Produk</th>
                    <th>Diskon</th>
                    <th>Tipe Diskon</th>
                    <th>Mulai</th>
                    <th>Berakhir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
				<?php $no = 1; foreach ($promos as $promo): ?>
				<tr>
					<td><?= $no++; ?></td>
					<td><?= htmlspecialchars($promo['kode_promo']); ?></td>
					<td><?= htmlspecialchars($promo['deskripsi']); ?></td>
					<td><?= htmlspecialchars($promo['nama_produk'] ?? 'Tidak ada produk'); ?></td>
					<td><?= htmlspecialchars($promo['nilai_diskon']); ?></td>
					<td><?= htmlspecialchars($promo['tipe_diskon'] === 'persen' ? 'Persen' : 'Nominal'); ?></td>
					<td><?= htmlspecialchars($promo['berlaku_mulai']); ?></td>
					<td><?= htmlspecialchars($promo['berlaku_sampai']); ?></td>
					<td>
						<a href="#" data-bs-toggle="modal" data-bs-target="#deletePromoModal"
						   data-id="<?= $promo['id']; ?>"
						   data-kode="<?= htmlspecialchars($promo['kode_promo']); ?>">Hapus</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>

        </table>
    </div>
</div>

<!-- Modal Tambah Promo -->
<div class="modal fade" id="addPromoModal" tabindex="-1" aria-labelledby="addPromoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="coupon-add.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPromoModalLabel">Tambah Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kodePromo" class="form-label">Kode Promo</label>
                        <input type="text" class="form-control" id="kodePromo" name="kode_promo" required>
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="diskon" class="form-label">Diskon</label>
                        <input type="number" class="form-control" id="diskon" name="diskon" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipeDiskon" class="form-label">Tipe Diskon</label>
                        <select class="form-control" id="tipeDiskon" name="tipe_diskon" required>
                            <option value="persen">Persen</option>
                            <option value="nominal">Nominal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="produk" class="form-label">Produk</label>
                        <select class="form-control" id="produk" name="id_produk">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($produk as $item): ?>
                                <option value="<?= $item['id']; ?>"><?= htmlspecialchars($item['nama_produk']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
						<label for="mulai" class="form-label">Tanggal Mulai</label>
						<input type="datetime-local" class="form-control" id="mulai" name="mulai" required>
					</div>
					<div class="mb-3">
						<label for="berakhir" class="form-label">Tanggal Berakhir</label>
						<input type="datetime-local" class="form-control" id="berakhir" name="berakhir" required>
					</div>

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Tambah Promo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Promo -->
<div class="modal fade" id="deletePromoModal" tabindex="-1" aria-labelledby="deletePromoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="coupon-delete.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePromoModalLabel">Hapus Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deletePromoMessage">Apakah Anda yakin ingin menghapus promo ini?</p>
                    <input type="hidden" id="deletePromoId" name="id_promo">
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
    jqOld('#promoTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": true
    });
});


document.addEventListener('DOMContentLoaded', () => {
	// Untuk Delete Modal
	document.querySelectorAll('a[data-bs-target="#deletePromoModal"]').forEach(link => {
		link.addEventListener('click', function () {
			const promoId = this.getAttribute('data-id');
			const kodePromo = this.getAttribute('data-kode');

			document.getElementById('deletePromoId').value = promoId;
			document.getElementById('deletePromoMessage').textContent = `Apakah Anda yakin ingin menghapus promo "${kodePromo}"?`;
		});
	});
});
</script>

<?php include '_footer.php'; ?>
