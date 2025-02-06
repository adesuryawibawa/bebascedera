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
header('Content-Type: application/json');

include '../includes/config.php'; // Koneksi ke database

$id_cabang = $_SESSION['cabang'] ?? 0; // Ambil ID Cabang dari session

// Konfigurasi server-side processing DataTables
$start = (int) ($_POST['start'] ?? 0);
$length = (int) ($_POST['length'] ?? 10);
$searchValue = $_POST['search']['value'] ?? '';

// Hitung total data
$totalQuery = "SELECT COUNT(*) as total FROM pasien WHERE id_cabang = ?";
$stmtTotal = $conn->prepare($totalQuery);
$stmtTotal->bind_param("i", $id_cabang);
$stmtTotal->execute();
$totalData = $stmtTotal->get_result()->fetch_assoc()['total'];

// Hitung data setelah pencarian
$searchQuery = "SELECT COUNT(*) as total FROM pasien WHERE id_cabang = ? AND (nama LIKE ? OR nomor_wa LIKE ?)";
$stmtSearch = $conn->prepare($searchQuery);
$searchTerm = "%$searchValue%";
$stmtSearch->bind_param("iss", $id_cabang, $searchTerm, $searchTerm);
$stmtSearch->execute();
$totalFiltered = $stmtSearch->get_result()->fetch_assoc()['total'];

// Ambil data pasien
$dataQuery = "SELECT id, nama, jenis_kelamin, tanggal_lahir, nomor_wa, email, alamat, keterangan FROM pasien 
              WHERE id_cabang = ? AND (nama LIKE ? OR nomor_wa LIKE ?) 
              ORDER BY nama ASC LIMIT ?, ?";
$stmtData = $conn->prepare($dataQuery);
$stmtData->bind_param("issii", $id_cabang, $searchTerm, $searchTerm, $start, $length);
$stmtData->execute();
$result = $stmtData->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        htmlspecialchars($row['nama'] ?? ''), // Jika null, jadikan string kosong
        htmlspecialchars($row['jenis_kelamin'] ?? ''),
        htmlspecialchars($row['tanggal_lahir'] ?? ''),
        htmlspecialchars($row['nomor_wa'] ?? ''),
        htmlspecialchars($row['email'] ?? ''),
        htmlspecialchars($row['alamat'] ?? ''),
        htmlspecialchars($row['keterangan'] ?? ''),
        "<button class='btn btn-primary btn-sm pilih-pasien' 
            data-nama='" . htmlspecialchars($row['nama'] ?? '') . "' 
            data-jenis_kelamin='" . htmlspecialchars($row['jenis_kelamin'] ?? '') . "'
            data-tanggal_lahir='" . htmlspecialchars($row['tanggal_lahir'] ?? '') . "'
            data-nomor_wa='" . htmlspecialchars($row['nomor_wa'] ?? '') . "'
            data-email='" . htmlspecialchars($row['email'] ?? '') . "'
            data-alamat='" . htmlspecialchars($row['alamat'] ?? '') . "'
            data-keterangan='" . htmlspecialchars($row['keterangan'] ?? '') . "'>Pilih</button>"
    ];
}

// Output JSON untuk DataTables
echo json_encode([
    "draw" => (int) ($_POST['draw'] ?? 1),
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalFiltered,
    "data" => $data,
]);
