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

// Ambil informasi cabang
$queryCabang = "SELECT nama_cabang, alamat, kota, link_google_map, jam_buka, jam_tutup, kapasitas_bed, kontak_cabang, pic, status FROM cabang WHERE id_cabang = ?";
$stmtCabang = $conn->prepare($queryCabang);
$stmtCabang->bind_param("i", $id_cabang);
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();
$cabang = $resultCabang->fetch_assoc();

// Hitung total pasien
$queryTotalPasien = "SELECT COUNT(*) AS total_pasien FROM pasien WHERE id_cabang = ?";
$stmtTotalPasien = $conn->prepare($queryTotalPasien);
$stmtTotalPasien->bind_param("i", $id_cabang);
$stmtTotalPasien->execute();
$resultTotalPasien = $stmtTotalPasien->get_result();
$totalPasien = $resultTotalPasien->fetch_assoc()['total_pasien'] ?? 0;

// Hitung total terapis
$queryTotalTerapis = "SELECT COUNT(*) AS total_terapis FROM terapis WHERE id_cabang = ?";
$stmtTotalTerapis = $conn->prepare($queryTotalTerapis);
$stmtTotalTerapis->bind_param("i", $id_cabang);
$stmtTotalTerapis->execute();
$resultTotalTerapis = $stmtTotalTerapis->get_result();
$totalTerapis = $resultTotalTerapis->fetch_assoc()['total_terapis'] ?? 0;

include '_header.php'; // Header halaman


?>


<div class="row">
  <div class="col-md-12 grid-margin">
	<div class="row">
	  <div class="col-12 col-xl-8 mb-4 mb-xl-0">
		<h3 class="font-weight-bold">Settings</h3>
		<h6 class="font-weight-normal mb-0">Informasi lengkap cabang.</h6>
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
  <!-- Informasi Cabang -->
  <div class="col-md-6">
    <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
      <div class="card-body">
        <h5 class="card-title">Informasi Cabang</h5>
        <p><strong>Nama Cabang:</strong> <?php echo htmlspecialchars($cabang['nama_cabang']); ?></p>
        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($cabang['alamat']); ?></p>
        <p><strong>Kota:</strong> <?php echo htmlspecialchars($cabang['kota']); ?></p>
        <p><strong>Link Google Map:</strong> <a href="<?php echo htmlspecialchars($cabang['link_google_map']); ?>" target="_blank">Lihat di Google Map</a></p>
        <p><strong>Jam Operasional:</strong> <?php echo htmlspecialchars($cabang['jam_buka']); ?> - <?php echo htmlspecialchars($cabang['jam_tutup']); ?></p>
        <p><strong>Kapasitas Bed:</strong> <?php echo htmlspecialchars($cabang['kapasitas_bed']); ?></p>
        <p><strong>Kontak Cabang:</strong> <?php echo htmlspecialchars($cabang['kontak_cabang']); ?></p>
        <p><strong>PIC:</strong> <?php echo htmlspecialchars($cabang['pic']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($cabang['status']); ?></p>
		
		 <!-- Tombol Edit -->
        <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalEditAvailability"><i class="fa fa-calendar"></i> Edit Availability Slot</button>
        <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalEditHours"><i class="fa fa-clock"></i> Edit Jam Operasional</button>
        <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalEditBeds"><i class="fa fa-bed"></i> Edit Kapasitas Bed</button>
      
      </div>
    </div>
  </div>

  <!-- Informasi Statistik -->
  <div class="col-md-6">
    <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
      <div class="card-body">
        <h5 class="card-title">Statistik Cabang</h5>
        <p><strong>Total Pasien Cabang Ini:</strong> <?php echo htmlspecialchars($totalPasien); ?></p>
        <p><strong>Total Terapis:</strong> <?php echo htmlspecialchars($totalTerapis); ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Availability -->
<div class="modal fade" id="modalEditAvailability" tabindex="-1" aria-labelledby="modalEditAvailabilityLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditAvailabilityLabel">Edit Availability Slot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditAvailability">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <div class="form-group">
            <label for="editTanggal">Tanggal</label>
            <input type="date" id="editTanggal" name="tanggal" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
          </div>
           <div class="form-group" style="margin-left: 20px;">
            <input type="checkbox" id="isHoliday" name="is_holiday" class="form-check-input" value="1">
            <label class="form-check-label" for="isHoliday">Tandai Hari Libur</label>
          </div>
		  <div id="slotDetails">
            <div class="form-group mt-3">
              <label for="editJam">Jam</label>
              <input type="time" id="editJam" name="jam" class="form-control">
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-4">Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Modal Edit Hours -->
<div class="modal fade" id="modalEditHours" tabindex="-1" aria-labelledby="modalEditHoursLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditHoursLabel">Edit Jam Operasional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="formEditHours" action="setting-jam-process.php" method="POST">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
				<div class="form-group">
					<label for="jamBuka">Jam Buka</label>
					<input type="time" id="jamBuka" name="jam_buka" class="form-control" value="<?php echo htmlspecialchars(substr($cabang['jam_buka'], 0, 5)); ?>" required>
				</div>
				<div class="form-group mt-3">
					<label for="jamTutup">Jam Tutup</label>
					<input type="time" id="jamTutup" name="jam_tutup" class="form-control" value="<?php echo htmlspecialchars(substr($cabang['jam_tutup'], 0, 5)); ?>" required>
				</div>
				<button type="submit" class="btn btn-primary mt-4">Simpan Perubahan</button>
			</form>

            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Beds -->
<div class="modal fade" id="modalEditBeds" tabindex="-1" aria-labelledby="modalEditBedsLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditBedsLabel">Edit Kapasitas Bed</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditBeds">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <div class="form-group">
            <label for="kapasitasBed">Kapasitas Bed</label>
            <input type="number" id="kapasitasBed" name="kapasitas_bed" class="form-control" value="<?php echo htmlspecialchars($cabang['kapasitas_bed']); ?>" required>
          </div>
          <button type="submit" class="btn btn-primary mt-4">Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
document.getElementById('formEditHours').addEventListener('submit', function (e) {
    e.preventDefault(); // Hentikan submit default

    const formData = new FormData(this); // Ambil data dari form

    fetch('setting-jam-process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text); // Coba parsing JSON
            } catch (e) {
                throw new Error('Parsing Error: ' + e.message + ' | Raw Response: ' + text);
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert(data.message); // Tampilkan pesan sukses
            window.location.reload(); // Refresh halaman
        } else {
            alert(data.message || 'Terjadi kesalahan.'); // Tampilkan pesan error
        }
    })
    .catch(error => {
        console.error('Error:', error); // Log error yang terjadi
        alert('Gagal memproses permintaan. Silakan coba lagi.');
    });
});

document.getElementById('formEditBeds').addEventListener('submit', function (e) {
    e.preventDefault(); // Hentikan submit default

    const formData = new FormData(this); // Ambil data dari form

    fetch('setting-bed-process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text); // Parsing JSON
            } catch (e) {
                throw new Error('Parsing Error: ' + e.message + ' | Raw Response: ' + text);
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert(data.message); // Tampilkan pesan sukses
            window.location.reload(); // Refresh halaman
        } else {
            alert(data.message || 'Terjadi kesalahan.'); // Tampilkan pesan error
        }
    })
    .catch(error => {
        console.error('Error:', error); // Log error yang terjadi
        alert('Gagal memproses permintaan. Silakan coba lagi.');
    });
});


document.getElementById('isHoliday').addEventListener('change', function () {
    const slotDetails = document.getElementById('slotDetails');
    if (this.checked) {
        slotDetails.style.display = 'none'; // Sembunyikan detail slot jika hari libur
    } else {
        slotDetails.style.display = 'block'; // Tampilkan detail slot jika bukan hari libur
    }
});

document.getElementById('formEditAvailability').addEventListener('submit', function (e) {
    e.preventDefault(); // Hentikan submit default

    const formData = new FormData(this); // Ambil data dari form

    fetch('setting-availability-process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text); // Parsing JSON
        } catch (e) {
            throw new Error('Parsing Error: ' + e.message + ' | Raw Response: ' + text);
        }
    }))
    .then(data => {
        if (data.success) {
            alert(data.message); // Tampilkan pesan sukses
            window.location.reload(); // Refresh halaman
        } else {
            alert(data.message || 'Terjadi kesalahan.'); // Tampilkan pesan error
        }
    })
    .catch(error => {
        console.error('Error:', error); // Log error yang terjadi
        alert('Gagal memproses permintaan. Silakan coba lagi.');
    });
});



 document.getElementById('editJam').addEventListener('input', function (e) {
      // Ambil nilai waktu
      let time = e.target.value;
      
      // Pastikan menit dan detik diatur ke :00
      if (time) {
          const [hour, minute] = time.split(':');
          e.target.value = `${hour}:00`; // Setel menit menjadi 00
      }
  });
  
document.getElementById('jamBuka').addEventListener('input', function (e) {
  // Ambil nilai waktu
  let time = e.target.value;
  
  // Pastikan menit dan detik diatur ke :00
  if (time) {
	  const [hour, minute] = time.split(':');
	  e.target.value = `${hour}:00`; // Setel menit menjadi 00
  }
});

document.getElementById('jamTutup').addEventListener('input', function (e) {
  // Ambil nilai waktu
  let time = e.target.value;
  
  // Pastikan menit dan detik diatur ke :00
  if (time) {
	  const [hour, minute] = time.split(':');
	  e.target.value = `${hour}:00`; // Setel menit menjadi 00
  }
});
</script>

<?php include '_footer.php'; // Footer halaman ?>
