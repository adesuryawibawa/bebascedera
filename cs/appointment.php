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
if ($_SESSION['level'] !== 'cs') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 120 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}

include '../includes/config.php';

// Query untuk mengambil data cabang yang aktif
$queryCabang = "SELECT * FROM cabang WHERE status = 'active' AND id_cabang != 0";
$resultCabang = $conn->query($queryCabang);

include '_header.php'; // Header halaman
?>

<style>
.card-body h6 {
    font-size: 0.9rem; /* Sesuaikan ukuran font untuk judul kecil */
}

.card-body h3 {
    font-size: 1.2rem; /* Sesuaikan ukuran font untuk angka */
}

.card-link {
    position: absolute;
    bottom: 10px;
    right: 10px;
    color: #007bff;
    text-decoration: none;
}

.card-link:hover {
    text-decoration: underline;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    margin-bottom: 20px;
}

.card-body {
    display: flex;
    align-items: center;
    padding: 15px;
}

.icon-container {
    background-color: #6c757dad;
    padding: 15px;
    border-radius: 10px;
    margin-right: 15px;
}

.icon-container i {
    color: white;
    font-size: 24px;
}

.text-container {
    flex: 1;
}

.text-container h6 {
    margin-bottom: 5px;
    color: #6c757d;
}

.text-container h3 {
    margin-bottom: 0;
    color: #343a40;
}
</style>

<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="row">
            <div class="col-12 col-xl-6">
                <h3 class="font-weight-bold">Appointment</h3>
                <h6 class="font-weight-normal mb-0">Daftar cabang yang tersedia untuk appointment.</h6>
            </div>
       
        </div>
    </div>
</div>

<div class="row">
    <?php if ($resultCabang->num_rows > 0): ?>
        <?php while ($cabang = $resultCabang->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="icon-container">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="text-container">
                            <h6><?php echo htmlspecialchars($cabang['nama_cabang']); ?></h6>
                        </div>
                        <a href="appointment-add.php?id_cabang=<?php echo $cabang['id_cabang']; ?>" class="card-link">Buat Appointment</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-warning" role="alert">
                Tidak ada cabang yang aktif.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '_footer.php'; // Footer halaman ?>