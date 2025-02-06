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

// Cek apakah level pengguna adalah Admin CS
if ($_SESSION['level'] !== 'cs') {
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

// Ambil nama cabang berdasarkan session
$id_cabang = $_SESSION['cabang'] ?? 0;
$nama_cabang = '';
$queryCabang = "SELECT nama_cabang FROM cabang WHERE id_cabang = ?";
$stmtCabang = $conn->prepare($queryCabang);
$stmtCabang->bind_param("i", $id_cabang);
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();
if ($rowCabang = $resultCabang->fetch_assoc()) {
    $nama_cabang = $rowCabang['nama_cabang'];
}

// Ambil data produk
$queryProduk = "SELECT id, nama_produk, harga FROM produk WHERE status='active' AND kategori='main' AND id_cabang = ?";
$stmtProduk = $conn->prepare($queryProduk);
$stmtProduk->bind_param("i", $id_cabang);
$stmtProduk->execute();
$resultProduk = $stmtProduk->get_result();

// Ambil data terapis 
$queryTerapis = "SELECT id, nama_terapis FROM terapis WHERE id_cabang = ?";
$stmtTerapis = $conn->prepare($queryTerapis);
$stmtTerapis->bind_param("i", $id_cabang);
$stmtTerapis->execute();
$resultTerapis = $stmtTerapis->get_result();

?>
<link rel="stylesheet" href="../assets/css/custome.css">

<div class="container">
	<div class="row mb-4">
		<div class="col-md-12 grid-margin">
			<h3 class="font-weight-bold">Form Booking</h3>
			<h6 class="font-weight-normal mb-0">Formulir untuk melakukan booking pasien di cabang <?php echo htmlspecialchars($nama_cabang); ?>.</h6>
		</div>
		
		<?php
		if (isset($_SESSION['success_message'])) {
			echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success_message']) . "</div>";
			unset($_SESSION['success_message']);
		}

		if (isset($_SESSION['error_message'])) {
			echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
			unset($_SESSION['error_message']);
		}
		?>

	</div>
	<!--Data Booking-->
	<div class="section-border blue d-flex justify-content-between align-items-center">
		<h2 class="section-title">Informasi Booking</h2>
		<i class="fas fa-sync-alt reload-icon" style="cursor: pointer; font-size: 1rem; color: #3b82f6;" title="Reload"></i>
	</div>
<form action="booking-process.php" method="POST">
	<div class="grid grid-2">
		<div>
			<div class="grid grid-2 mb-2">
				<!-- Produk -->
				<div class="form-group mb-2">
					<label for="produkSelect">Produk</label>
					<select name="id_produk" id="produkSelect" class="form-control" required>
						<option value="" disabled selected>Pilih Produk</option>
						<?php while ($produk = $resultProduk->fetch_assoc()): ?>
							<option value="<?php echo $produk['id']; ?>" data-harga="<?php echo $produk['harga']; ?>">
								<?php echo htmlspecialchars($produk['nama_produk']); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>
				<div class="form-group mb-2">
					<!-- Input harga produk -->
					<label for="hargaProduk">Harga Produk</label>
					<input type="number" id="hargaProduk"  name="hargaProduk" class="form-control" placeholder="Harga" min="0" readonly>
				</div>
				
			</div>
			
			<div class="form-group mb-2" style="width:75%;">
				<label for="kodePromo">Kode Kupon / Affiliate</label>
				<div class="input-group">
					<input type="text" name="kode_promo" id="kodePromo" class="form-control" placeholder="Kode Kupon">
					<button type="button" id="checkPromo" class="btn btn-secondary btn-xs">Cek Kupon</button>
				</div>
				<!-- Elemen untuk hasil promo -->
				<div id="promoResult" class="text-muted mt-2"></div>
			</div>

			<input type="hidden" id="diskonPromo" name="diskonPromo" class="form-control"  readonly>
			<input type="hidden" id="totalHarga" name="totalHarga" class="form-control"  readonly>
			<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

		</div>
		
		<div class="ml-8">
			<div class="form-group row align-items-center">
				<div class="col-md-6">
					<label for="tanggalBooking">Tanggal Booking</label>
					<input type="date" name="tanggal_booking" id="tanggalBooking" class="form-control" required>
				</div>

				<div class="col-md-6">
					<label for="waktuBooking">Jam</label>
					<select name="waktu_booking" id="waktuBooking" class="form-control" required>
						<option value="" disabled selected>Pilih Jam</option>
					</select>
				</div>
			</div>

			<div>
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
	
	</div>
	
	<!--Data Pasien-->
	<div class="section-border green mt-4">
		<h2 class="section-title">Informasi Pasien</h2>
	</div>
	<div class="grid grid-2">
		<div>
			<div class="grid mb-6">
				<div class="form-group">
					<!-- Nama Pasien -->
					<div class="mb-3">
						<label for="namaPasien" class="form-label">Nama Pasien</label>
						<div class="input-group">
							<input type="text" name="nama_pasien" id="namaPasien" class="form-control" placeholder="Masukkan Nama Pasien" required>
							<button type="button" class="btn btn-secondary btn-xs" data-bs-toggle="modal" data-bs-target="#modalCariPasien">Cari Pasien</button>
						</div>
					</div>

					<!-- Email Pasien dan Nomor WhatsApp -->
					<div class="row">
						<div class="col-md-6 mb-3">
							<label for="emailPasien" class="form-label">Email Pasien</label>
							<input type="email" name="email_pasien" id="emailPasien" class="form-control" placeholder="Masukkan Email Pasien">
						</div>
						<div class="col-md-6 mb-3">
							<label for="nomorWa" class="form-label">Nomor WhatsApp</label>
							<input type="tel" name="nomor_wa" id="nomorWa" class="form-control" placeholder="Masukkan Nomor WhatsApp">
						</div>
					</div>
					<div class="row">
						<div class="col-md-6 mb-3">
							<label for="jenisKelamin">Jenis Kelamin</label>
							<select name="jenis_kelamin" id="jenisKelamin" class="form-control" required>
								<option value="" disabled selected>Pilih Jenis Kelamin</option>
								<option value="Laki-laki">Laki-laki</option>
								<option value="Perempuan">Perempuan</option>
							</select>
						</div>
						<div class="col-md-6 mb-3">
							<label for="tanggalLahir">Tanggal Lahir</label>
							<input type="date" name="tanggal_lahir" id="tanggalLahir" class="form-control">
						</div>
					</div>
					  <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea name="alamat" id="alamat" class="form-control" placeholder="Masukkan Alamat Pasien"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Catatan / Keluhan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Masukkan Keluhan Pasien"></textarea>
                    </div>  
				</div>
			</div>
		</div>
	</div>
	
	<!--Produk Add-on-->
	<div class="section-border orange">
		<h2 class="section-title">Produk Add-on</h2>
	</div>
	
	<div id="addonContainer">
		<div class="grid grid-6 addon-row mb-1">
			<div class="col-span-2">
				<select class="form-control addon-select" name="addons[0][id]">
					<option value="" disabled selected>Pilih Produk Add-on</option>
					<?php
					$queryAddons = "SELECT id, nama_produk, harga FROM produk WHERE id_cabang = ? AND kategori = 'addons'";
					$stmtAddons = $conn->prepare($queryAddons);
					$stmtAddons->bind_param("i", $id_cabang);
					$stmtAddons->execute();
					$resultAddons = $stmtAddons->get_result();

					while ($addon = $resultAddons->fetch_assoc()) {
						echo "<option value='{$addon['id']}' data-harga='{$addon['harga']}'>" . htmlspecialchars($addon['nama_produk']) . "</option>";
					}
					?>
				</select>
			</div>
			<div>
				<input type="number" name="addons[0][qty]" placeholder="Qty" class="form-control addon-qty" min="1" >
			</div>
			<div>
				<input type="text" name="addons[0][price]" placeholder="Harga Satuan" class="form-control addon-price" readonly>
			</div>
			<div>
				<input type="number" name="addons[0][discount]" placeholder="Potongan" class="form-control addon-discount" min="0">
			</div>
			<div>
				<input type="text" name="addons[0][total]" placeholder="Total" class="form-control addon-total" readonly>
			</div>
			<div class="flex items-end">
				<button type="button" class="btn btn-secondary btn-xs addon-add">
					<i class="fas fa-plus-circle"></i>
				</button>
				<button type="button" class="btn btn-danger btn-xs addon-delete ml-1">
					<i class="fas fa-trash-alt"></i>
				</button>
			</div>
		</div>
	</div>


	
	<div class="section-border red mt-4">
		<h2 class="section-title">Pembayaran</h2>
	</div>
	<div>
		<div class="summary mb-4" style="margin-top: 30px;">
			<h2 class="summary-title">Total Bayar</h2>
			<p id="summary-amount" class="summary-amount mb-4">Rp. 0</p>
			<input type="hidden" id="hiddenTotalBayar" name="total_bayar" value="0">

			<h4 class="summary-title">Ringkasan Pembayaran</h4>
			<div id="summary-details">
				<!-- Ringkasan Produk Utama -->
				<p id="summary-product">Produk: </p>
				<p id="summary-discount">Diskon: </p>
				<p id="summary-potongan">Potongan Harga: </p>
				<p id="summary-subtotal">Sub Total: </p>

				<!-- Placeholder Add-ons -->
				<div id="summary-addons"></div>
			</div>
		</div>

	</div>
	<div class="grid grid-6 mb-6">
		<div>
			<label for="potonganHarga">Potongan Harga (Rp.)</label>
			<input type="number" id="potonganHarga" name="potonganHarga" class="form-control" placeholder="Rp.0" min="0">
		</div>

		<div>
			<label for="metodePembayaran">Metode Pembayaran</label>
			<select name="metode_pembayaran" id="metodePembayaran" class="form-control" required>
				<option value="" disabled selected>Pilih Metode Pembayaran</option>
				<option value="Cash">Cash</option>
				<option value="Transfer Bank">Transfer Bank</option>
				<option value="Transfer Event">Transfer Event</option>
				<option value="Transfer Homecare">Transfer Homecare</option>
				<option value="E-Wallet">E-Wallet</option>							
				<option value="QRIS">QRIS</option>
				<option value="CC">CC</option>
				<option value="DEBET">Debit Card</option>
			</select>
		</div>
		<div>
			<label for="statusPembayaran">Status Pembayaran</label>
			<select name="status_pembayaran" id="statusPembayaran" class="form-control" required>
				<option value="" disabled selected>Pilih Status Pembayaran</option>
				<option value="Confirmed">CONFIRMED</option>
				<option value="Pending">WAITING PAYMENT (Pending)</option>
			</select>
		</div>
	</div>
	
	<div class="text-left">
		<button class="btn btn-md mt-4"><i class="fas fa-arrow-right"></i> BOOK NOW</button>
	</div>
</div>
</form>
<!-- Modal Cari Pasien -->
<div class="modal fade" id="modalCariPasien" tabindex="-1" aria-labelledby="modalCariPasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCariPasienLabel">Cari Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                   <table class="table table-hover" id="tableCariPasien" style="width: 100%;">
						<thead>
							<tr>
								<th>Nama</th>
								<th>Jenis Kelamin</th>								
								<th>Tanggal Lahir</th>
								<th>Nomor WA</th>
								<th>Email</th>
								<th>Alamat</th>								
								<th>Keterangan</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
const addonContainer = document.getElementById('addonContainer');
const hargaProdukInput = document.getElementById('hargaProduk');
const produkSelect = document.getElementById('produkSelect');
const potonganHargaInput = document.getElementById('potonganHarga');
const diskonPromoInput = document.getElementById('diskonPromo');
const summaryAmount = document.getElementById('summary-amount');
const summaryDetails = document.getElementById('summary-details');
	
//Refresh Halaman
document.addEventListener('DOMContentLoaded', function () {
    const reloadIcon = document.querySelector('.reload-icon');

    // Event: Reload halaman saat ikon diklik
    reloadIcon.addEventListener('click', function () {
        location.reload(); // Reload halaman
    });
});

// Modal data pasien
var jqOld = $.noConflict(true);

jqOld(document).ready(function () {

    // Memastikan elemen tabel tersedia
    if (jqOld('#tableCariPasien').length === 0) {
        console.error('Elemen tabel #tableCariPasien tidak ditemukan di DOM.');
        return;
    }

    // Inisialisasi DataTables
    const tableCariPasien = jqOld('#tableCariPasien').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'get-pasien.php', // Endpoint server untuk data pasien
            type: 'POST',
            dataSrc: function (json) {
                return json.data;
            }
        },
        columns: [
            { title: "Nama", data: 0 },
            { title: "Jenis Kelamin", data: 1 },
            { title: "Tanggal Lahir", data: 2 },
            { title: "Nomor WA", data: 3 },
            { title: "Email", data: 4 },
            { title: "Alamat", data: 5 },
            { title: "Keterangan", data: 6 },
            { title: "Aksi", data: 7 }
        ],
		 dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' + // Menempatkan "Show entries" di kiri dan "Search" di kanan
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-5"i><"col-sm-7"p>>', // Menata "info" dan "pagination"
        pagingType: "full_numbers",
        pageLength: 15,
        lengthChange: true,
        ordering: false,
        searching: true,
        scrollX: true,
        responsive: false
    });

    // Event Pilih Pasien
    jqOld('#tableCariPasien tbody').on('click', '.pilih-pasien', function () {
        const row = jqOld(this).data();

        // Isi data ke form informasi pasien
        jqOld('#namaPasien').val(row.nama);
        jqOld('#jenisKelamin').val(row.jenis_kelamin);		
        jqOld('#tanggalLahir').val(row.tanggal_lahir);
        jqOld('#nomorWa').val(row.nomor_wa);
        jqOld('#emailPasien').val(row.email);
        jqOld('#alamat').val(row.alamat);		
        jqOld('#keterangan').val(row.keterangan);

        // Tutup modal menggunakan Bootstrap 5 API
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCariPasien'));
        modal.hide();

        // Hapus elemen backdrop secara manual
        jqOld('.modal-backdrop').remove();
        jqOld('body').removeClass('modal-open'); // Hapus kelas 'modal-open' dari body
        jqOld('body').css('padding-right', ''); // Reset padding body jika ada
    });

    // Event: Ketika modal ditutup
    jqOld('#modalCariPasien').on('hidden.bs.modal', function () {
        // Hapus elemen backdrop jika ada
        jqOld('.modal-backdrop').remove();
        jqOld('body').removeClass('modal-open');
        jqOld('body').css('padding-right', '');
    });

    // Event: Ketika modal akan dibuka
    jqOld('#modalCariPasien').on('show.bs.modal', function () {
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

// Ketika produk dipilih
document.getElementById('produkSelect').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const hargaProduk = parseFloat(selectedOption.dataset.harga || 0);
	
    document.getElementById('hargaProduk').value = hargaProduk;
});

// Ketika kode promo dicek
document.getElementById('checkPromo').addEventListener('click', function () {
    const kodePromo = document.getElementById('kodePromo').value;
    const produkSelect = document.getElementById('produkSelect');
    const idProduk = produkSelect.value; // Ambil id_produk
    const hargaProduk = parseFloat(produkSelect.options[produkSelect.selectedIndex]?.dataset?.harga || 0);

    const promoResultDiv = document.getElementById('promoResult'); // Div untuk menampilkan hasil

    if (!kodePromo) {
        alert('Silakan masukkan kode promo.');
        return;
    }

    fetch(`validate-promo.php?kode=${kodePromo}&harga=${hargaProduk}&id_produk=${idProduk}&id_cabang=<?php echo $id_cabang; ?>`)
        .then(response => response.json())
        .then(data => {
            const diskonPromoInput = document.getElementById('diskonPromo');
            const totalHargaInput = document.getElementById('totalHarga');

            if (data.valid) {
                const diskon = parseFloat(data.diskon);
                const totalHarga = hargaProduk - diskon;

                // Tampilkan diskon dan total harga sebagai angka
                diskonPromoInput.value = diskon;
                totalHargaInput.value = totalHarga;

                // Tampilkan pesan sukses di div
                promoResultDiv.textContent = `Promo berhasil diterapkan! Diskon: ${diskon}`;
                promoResultDiv.className = 'text-success'; // Warna hijau untuk teks sukses
				
            } else {
                // Isi diskon dan total harga dengan 0 jika tidak valid
                diskonPromoInput.value = 0;
                totalHargaInput.value = hargaProduk;

                // Tampilkan pesan error di div
                promoResultDiv.textContent = 'Kode promo tidak valid.';
                promoResultDiv.className = 'text-danger'; // Warna merah untuk teks kesalahan
            }
        })
        .catch(error => {
            console.error('Error fetching promo:', error);

            // Jika terjadi error, isi diskon dan total harga dengan 0
            const diskonPromoInput = document.getElementById('diskonPromo');
            const totalHargaInput = document.getElementById('totalHarga');
            //diskonPromoInput.value = 0;
            totalHargaInput.value = hargaProduk;

            // Tampilkan error di div
            promoResultDiv.textContent = 'Terjadi kesalahan saat memvalidasi promo.';
            promoResultDiv.className = 'text-danger'; // Warna merah untuk teks error
        });
});

// Penanganan Add-ons
document.addEventListener('DOMContentLoaded', function () {
    const addonContainer = document.getElementById('addonContainer');

    // Fungsi untuk menghitung total
    function calculateTotal(row) {
        const qty = parseFloat(row.querySelector('.addon-qty').value) || 0;
        const price = parseFloat(row.querySelector('.addon-price').value) || 0;
        const discount = parseFloat(row.querySelector('.addon-discount').value) || 0;
        const total = qty * price - discount;

        row.querySelector('.addon-total').value = total > 0 ? total.toFixed(2) : 0;
    }

    // Fungsi untuk memperbarui atribut 'name' Add-on agar memiliki indeks unik
    function updateAddonIndices() {
        const rows = addonContainer.querySelectorAll('.addon-row');
        rows.forEach((row, index) => {
            row.querySelector('.addon-select').setAttribute('name', `addons[${index}][id]`);
            row.querySelector('.addon-qty').setAttribute('name', `addons[${index}][qty]`);
            row.querySelector('.addon-price').setAttribute('name', `addons[${index}][price]`);
            row.querySelector('.addon-discount').setAttribute('name', `addons[${index}][discount]`);
            row.querySelector('.addon-total').setAttribute('name', `addons[${index}][total]`);
        });
    }

    // Event: Perubahan pada combo box "Pilih Produk Add-on"
    addonContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('addon-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = parseFloat(selectedOption.dataset.harga || 0);
            const row = e.target.closest('.addon-row');

            // Isi harga satuan
            row.querySelector('.addon-price').value = price.toFixed(2);

            // Hitung ulang total
            calculateTotal(row);
        }
    });

    // Event: Perubahan Qty atau Potongan
    addonContainer.addEventListener('input', function (e) {
        if (e.target.classList.contains('addon-qty') || e.target.classList.contains('addon-discount')) {
            const row = e.target.closest('.addon-row');
            calculateTotal(row);
        }
    });

    // Event: Tambahkan baris baru
    addonContainer.addEventListener('click', function (e) {
        if (e.target.closest('.addon-add')) {
            const newRow = addonContainer.querySelector('.addon-row').cloneNode(true);

            // Reset nilai pada baris baru
            newRow.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            newRow.querySelector('select').selectedIndex = 0;

            // Tambahkan baris baru ke container
            addonContainer.appendChild(newRow);

            // Perbarui indeks Add-on
            updateAddonIndices();
        }
    });

    // Event: Hapus baris atau clear baris pertama
    addonContainer.addEventListener('click', function (e) {
        if (e.target.closest('.addon-delete')) {
            const row = e.target.closest('.addon-row');
            const allRows = addonContainer.querySelectorAll('.addon-row');

            // Jika hanya ada satu baris, lakukan reset
            if (allRows.length === 1) {
                row.querySelector('.addon-select').selectedIndex = 0; // Reset dropdown
                row.querySelector('.addon-qty').value = ''; // Kosongkan input Qty
                row.querySelector('.addon-price').value = ''; // Kosongkan Harga Satuan
                row.querySelector('.addon-discount').value = ''; // Kosongkan Potongan
                row.querySelector('.addon-total').value = ''; // Kosongkan Total

                // Perbarui summary
                calculateSummary();

                alert('Produk Add-on telah dikosongkan.');
            } else {
                // Jika lebih dari satu baris, hapus baris tersebut
                row.remove();

                // Perbarui indeks Add-on
                updateAddonIndices();

                // Perbarui summary
                calculateSummary();
            }
        }
    });

    // Memperbarui indeks saat halaman dimuat
    updateAddonIndices();
});



// Kalkulasi summary 
document.addEventListener('DOMContentLoaded', function () {
	const diskonPromoInput = document.getElementById('diskonPromo');

    // Event: Tombol promo di-submit
    document.getElementById('checkPromo').addEventListener('click', function () {
        const kodePromo = document.getElementById('kodePromo').value;

        fetch(`validate-promo.php?kode=${kodePromo}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    const diskon = parseFloat(data.diskon) || 0;
                    diskonPromoInput.value = diskon;

                } else {
                    //diskonPromoInput.value = 0;
                }

                // Hitung ulang ringkasan setelah diskon diperbarui
                calculateSummary();
            })
            .catch(error => console.error('Error fetching promo:', error));
    });

    // Event: Perbarui ringkasan ketika produk dipilih
    produkSelect.addEventListener('change', function () {
        const selectedOption = produkSelect.options[produkSelect.selectedIndex];
        const hargaProduk = parseFloat(selectedOption.dataset.harga || 0);

        // Isi harga produk
        hargaProdukInput.value = hargaProduk;

        // Set default potongan harga jika kosong
        if (!potonganHargaInput.value) {
            potonganHargaInput.value = 0;
        }

        // Hitung ringkasan
        calculateSummary();
    });

    // Event: Perbarui ringkasan saat potongan harga berubah
    potonganHargaInput.addEventListener('input', function () {
        if (!potonganHargaInput.value) {
            potonganHargaInput.value = 0;
        }
        calculateSummary();
    });

    // Event: Perbarui ringkasan untuk Add-ons
    addonContainer.addEventListener('input', function (e) {
        if (e.target.classList.contains('addon-qty') || e.target.classList.contains('addon-discount')) {
            calculateSummary();
        }
    });

    addonContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('addon-select')) {
            calculateSummary();
        }
    });

	try {
    console.log('Memulai observer untuk diskonPromoInput...');

    // Pastikan diskonPromoInput ditemukan
    if (!diskonPromoInput) {
        console.error('diskonPromoInput tidak ditemukan di DOM.');
        return;
    }

    // Membuat observer untuk memantau perubahan atribut value
    const observer = new MutationObserver((mutationsList) => {
        mutationsList.forEach((mutation) => {
            if (mutation.attributeName === 'value') {
                const diskonPromo = parseFloat(diskonPromoInput.value) || 0;
                console.log('Observer mendeteksi perubahan diskonPromoInput. Nilai saat ini:', diskonPromo);

                // Hitung ulang summary
                calculateSummary();
            }
        });
    });

    // Mendaftarkan observer
    observer.observe(diskonPromoInput, { attributes: true, attributeFilter: ['value'] });
    console.log('Observer berhasil didaftarkan untuk diskonPromoInput.');

} catch (error) {
    console.error('Error saat mengatur observer untuk diskonPromoInput:', error);
}

		
});

// Fungsi calculateSummary untuk memastikan nilai diskon digunakan
function calculateSummary() {
    console.log('Memulai calculateSummary...');
    
    // Ambil nilai dari elemen yang relevan
    const hargaProduk = parseFloat(hargaProdukInput.value) || 0;
    const potonganHarga = parseFloat(potonganHargaInput?.value || 0); // Default 0 jika kosong
    const diskonPromo = parseFloat(diskonPromoInput?.value || 0); // Ambil langsung dari input hidden

    // Debugging nilai
    console.log('Nilai hargaProduk:', hargaProduk);
    console.log('Nilai potonganHarga:', potonganHarga);
    console.log('Nilai diskonPromo:', diskonPromo);

    // Subtotal utama
    const subtotalUtama = hargaProduk - diskonPromo - potonganHarga;

    console.log('Subtotal utama:', subtotalUtama);

    // Perhitungan untuk Add-ons
    let totalAddon = 0;
    const addonRows = addonContainer.querySelectorAll('.addon-row');
    let addonDetails = '';

    addonRows.forEach((row, index) => {
        const addonSelect = row.querySelector('.addon-select');
        const addonName = addonSelect.options[addonSelect.selectedIndex]?.text || `Add-on ${index + 1}`;
        const addonDiscount = parseFloat(row.querySelector('.addon-discount').value) || 0;
        const addonTotal = parseFloat(row.querySelector('.addon-total').value) || 0;

        totalAddon += addonTotal;

        addonDetails += `
            <p class="mt-4">${addonName}</p>
            <p>Diskon: Rp. ${addonDiscount.toLocaleString()}</p>
            <p>Sub Total: Rp. ${addonTotal.toLocaleString()}</p>
        `;
    });

    // Total keseluruhan
    const totalBayar = subtotalUtama + totalAddon;

    console.log('Total bayar:', totalBayar);
	
	// Perbarui elemen hidden dengan nilai total bayar
	const hiddenTotalBayar = document.getElementById('hiddenTotalBayar');
	hiddenTotalBayar.value = totalBayar;

    // Perbarui Summary
    summaryAmount.textContent = `Rp. ${totalBayar.toLocaleString()}`;
    summaryDetails.innerHTML = `
        <p>${produkSelect.options[produkSelect.selectedIndex]?.text || 'Produk Utama'} Rp. ${hargaProduk.toLocaleString()}</p>
        <p id="summary-discount">Diskon: Rp. ${diskonPromo.toLocaleString()}</p>
        <p>Potongan Harga: Rp. ${potonganHarga.toLocaleString()}</p>
        <p>Sub Total: Rp. ${subtotalUtama.toLocaleString()}</p>
        ${addonDetails}
    `;
}

</script>
<?php include '_footer.php'; ?>