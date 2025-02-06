<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika belum login
    exit;
}

// Cek apakah level pengguna adalah "Admin Cabang"
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan Admin Cabang
    exit;
}

include '../includes/config.php';

$kodePromo = $_GET['kode'] ?? '';
$hargaProduk = floatval($_GET['harga'] ?? 0);
$idCabang = intval($_GET['id_cabang'] ?? 0);
$idProduk = $_GET['id_produk'] ?? 0;

if (empty($kodePromo) || empty($hargaProduk) || empty($idCabang)) {
    echo json_encode(['valid' => false, 'message' => 'Parameter tidak lengkap.']);
    exit;
}

$sql = "SELECT tipe_diskon, nilai_diskon, berlaku_mulai, berlaku_sampai 
        FROM promo 
        WHERE kode_promo = ? AND id_produk = ? AND id_cabang = ? AND NOW() BETWEEN berlaku_mulai AND berlaku_sampai";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $kodePromo, $idProduk, $idCabang);
$stmt->execute();
$result = $stmt->get_result();
$promo = $result->fetch_assoc();

if ($promo) {
    $diskon = $promo['tipe_diskon'] === 'persen'
        ? ($hargaProduk * ($promo['nilai_diskon'] / 100))
        : $promo['nilai_diskon'];

    $diskon = round($diskon, 2);
    $hargaAkhir = max(0, $hargaProduk - $diskon);

    echo json_encode([
        'valid' => true,
        'diskon' => $diskon,
        'harga_akhir' => $hargaAkhir,
        'message' => 'Kode promo valid.'
    ]);
} else {
    echo json_encode([
        'valid' => false,
        'message' => 'Kode promo tidak ditemukan.'
    ]);
}

$stmt->close();
$conn->close();
