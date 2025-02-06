<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/config.php'; // Koneksi ke database

// Validasi apakah pengguna memiliki akses ke cabang tertentu
$id_cabang = $_SESSION['cabang'] ?? 0;

// Ambil parameter DataTables
$draw = $_GET['draw'] ?? 1;
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$searchValue = $_GET['search']['value'] ?? '';

// Query untuk menghitung total data
$queryTotal = "SELECT COUNT(*) AS total FROM pasien WHERE id_cabang = ?";
$stmtTotal = $conn->prepare($queryTotal);
$stmtTotal->bind_param("i", $id_cabang);
$stmtTotal->execute();
$resultTotal = $stmtTotal->get_result();
$totalData = $resultTotal->fetch_assoc()['total'];

// Query untuk data dengan pencarian
$queryFiltered = "SELECT COUNT(*) AS filtered FROM pasien WHERE id_cabang = ? AND (nama LIKE ? OR email LIKE ? OR nomor_wa LIKE ?)";
$stmtFiltered = $conn->prepare($queryFiltered);
$searchQuery = "%$searchValue%";
$stmtFiltered->bind_param("isss", $id_cabang, $searchQuery, $searchQuery, $searchQuery);
$stmtFiltered->execute();
$resultFiltered = $stmtFiltered->get_result();
$totalFiltered = $resultFiltered->fetch_assoc()['filtered'];

// Query untuk data pasien dengan limit dan offset
$queryPasien = "SELECT id, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, nomor_wa, email, alamat, keterangan
                FROM pasien
                WHERE id_cabang = ? AND (nama LIKE ? OR email LIKE ? OR nomor_wa LIKE ?)
                ORDER BY nama ASC
                LIMIT ? OFFSET ?";
$stmtPasien = $conn->prepare($queryPasien);
$stmtPasien->bind_param("isssii", $id_cabang, $searchQuery, $searchQuery, $searchQuery, $length, $start);
$stmtPasien->execute();
$resultPasien = $stmtPasien->get_result();

// Format data pasien untuk DataTables
$data = [];
while ($row = $resultPasien->fetch_assoc()) {
    $data[] = [
        $row['nama'],
        $row['jenis_kelamin'],
        $row['email'],
        $row['nomor_wa'],
        $row['alamat'],
        '<button class="btn btn-primary btn-sm pilih-pasien"
            data-nama="' . htmlspecialchars($row['nama']) . '"
            data-jenis-kelamin="' . htmlspecialchars($row['jenis_kelamin']) . '"
            data-tempat-lahir="' . htmlspecialchars($row['tempat_lahir']) . '"
            data-tanggal-lahir="' . htmlspecialchars($row['tanggal_lahir']) . '"
            data-email="' . htmlspecialchars($row['email']) . '"
            data-nomor-wa="' . htmlspecialchars($row['nomor_wa']) . '"
            data-alamat="' . htmlspecialchars($row['alamat']) . '"
            data-keterangan="' . htmlspecialchars($row['keterangan']) . '">Pilih</button>'
    ];
}

// Kirim data dalam format JSON
$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data,
];
echo json_encode($response);
exit;
