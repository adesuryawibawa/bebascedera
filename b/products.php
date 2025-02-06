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

include '_header.php';
include '../includes/config.php'; // Koneksi ke database

// Fetch data produk untuk cabang saat ini
$produk = [];
try {
    $sql = "SELECT id, nama_produk, kategori, deskripsi, harga, images, status 
            FROM produk 
            WHERE id_cabang = ? 
            ORDER BY id DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_cabang);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $produk = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception("Gagal mempersiapkan query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan saat mengambil data produk.";
    header("Location: ../login.php");
    exit;
}
?>

<style>
.card-img-top {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}
.card {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
.card-title {
    font-size: 1rem;
    font-weight: bold;
	
}
.card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.card-body .d-flex {
    margin-top: auto;
}
</style>

<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <h3 class="font-weight-bold">Data Produk</h3>
            <h6 class="font-weight-normal mb-0">
                Daftar produk untuk cabang Anda
                <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">+ Tambah Produk</a>
            </h6>
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

    <div class="row">
        <?php foreach ($produk as $row): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <?php
                    $defaultImage = "../assets/img-products/no-images.png";
                    $imageName = !empty($row['images']) ? $row['images'] : null;
                    $imagePath = "../assets/img-products/" . $imageName;
                    if (empty($imageName) || !file_exists($imagePath)) {
                        $imagePath = $defaultImage;
                    }
                    
                    ?>
                    <img src="<?= htmlspecialchars($imagePath) ?>" class="card-img-top" alt="Gambar Produk" onerror="this.onerror=null; this.src='<?= $defaultImage ?>';">
                    <div class="card-body d-flex flex-column justify-content-between" style="height: 100%;">
                        <div>
                            <h5 class="card-title text-center mb-2" style="text-transform: uppercase;"><?= htmlspecialchars($row['nama_produk']) ?></h5>
                            <p class="text-muted small text-center"><?= htmlspecialchars($row['deskripsi']) ?></p>
                            <h6 class="font-weight-bold text-center">Rp <?= number_format($row['harga'], 0, ',', '.') ?></h6>
                            <p class="text-center text-<?= $row['status'] === 'nonactive' ? 'danger' : 'success' ?>">
                                <?= $row['status'] === 'nonactive' ? 'Nonaktif' : 'Aktif' ?> - <?= htmlspecialchars($row['kategori']) ?> product
                            </p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="#" 
                               data-bs-toggle="modal" 
                               data-bs-target="#editProductModal"
                               data-id="<?= $row['id'] ?>" 
                               data-nama="<?= htmlspecialchars($row['nama_produk']) ?>" 
							   data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                               data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>" 
                               data-harga="<?= $row['harga'] ?>" 
                               data-images="<?= htmlspecialchars($row['images']) ?>"
                               class="text-success text-small"><strong>Edit</strong></a>
                            <a href="#" 
                               data-bs-toggle="modal"
                               data-bs-target="#deleteProductModal"
                               data-id="<?= $row['id'] ?>"
                               data-name="<?= htmlspecialchars($row['nama_produk']) ?>"
                               class="text-danger text-small delete-product-link">
                               <strong>Hapus</strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="product-add.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="productName" name="nama_produk" required>
                    </div>
                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="productDescription" name="deskripsi" rows="3" required></textarea>
                    </div>
					<div class="mb-3">
                        <label for="productCategory" class="form-label">Kategori Produk</label>
                        <select class="form-control" id="productCategory" name="kategori" required>
                            <option value="main" selected>Main Product</option>
                            <option value="addons">Add-Ons</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="productPrice" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="productPrice" name="harga" required>
                    </div>
                    <div class="mb-3">
                        <label for="productImage" class="form-label">Gambar Produk</label>
                        <input type="file" class="form-control" id="productImage" name="image" accept="image/*">
                        <small class="text-muted">Hanya file .jpg, .jpeg, atau .png. Maksimal 2MB.</small>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id_cabang" value="<?= htmlspecialchars($id_cabang) ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="products-update.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editProductName" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="editProductName" name="nama_produk" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProductCategory" class="form-label">Kategori Produk</label>
                        <select class="form-control" id="editProductCategory" name="kategori" required>
                            <option value="main">Main</option>
                            <option value="addons">Addons</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editProductDescription" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="editProductDescription" name="deskripsi" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editProductPrice" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editProductPrice" name="harga" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProductImage" class="form-label">Gambar Produk</label>
                        <input type="file" class="form-control" id="editProductImage" name="image" accept="image/*">
                        <small class="text-muted">Hanya file .jpg, .jpeg, atau .png. Maksimal 2MB.</small>
                    </div>
                    <input type="hidden" id="edit_product_id" name="id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="products-delete.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Hapus Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Apakah Anda yakin ingin menghapus produk ini?</p>
                    <input type="hidden" id="delete_product_id" name="delete_product_id">
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
//Modal Hapus
document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.delete-product-link').forEach(link => {
		link.addEventListener('click', event => {
			const productId = link.getAttribute('data-id');
			const productName = link.getAttribute('data-name');

			// Set nilai ke dalam modal
			document.getElementById('delete_product_id').value = productId;
			document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus produk "${productName}"?`;
		});
	});
});

//Modal edit
document.addEventListener('DOMContentLoaded', () => {
    // Event listener untuk membuka modal edit dan mengisi data
    document.querySelectorAll('a[data-bs-target="#editProductModal"]').forEach(link => {
        link.addEventListener('click', event => {
        const id = link.getAttribute('data-id');
		const nama = link.getAttribute('data-nama');
		const deskripsi = link.getAttribute('data-deskripsi');
		const harga = link.getAttribute('data-harga');
		const images = link.getAttribute('data-images');
		const kategori = link.getAttribute('data-kategori');

		// Isi nilai-nilai ke dalam form modal
		document.getElementById('edit_product_id').value = id;
		document.getElementById('editProductName').value = nama;
		document.getElementById('editProductDescription').value = deskripsi;
		document.getElementById('editProductPrice').value = harga;
		document.getElementById('editProductCategory').value = kategori;

        });
    });
});

</script>

<?php include '_footer.php'; ?>
