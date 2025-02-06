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

include '../includes/config.php';

// Ambil id_cabang dari session
$id_cabang = $_SESSION['cabang'];

// Ambil parameter tanggal
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Query untuk mengambil data pengeluaran
$query = "
    SELECT e.id, c.nama_cabang, ec.name AS category_name, e.description, e.amount, e.transaction_date, u.username AS user_input
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN cabang c ON e.id_cabang = c.id_cabang
    WHERE e.id_cabang = ?
";

if ($start_date && $end_date) {
    $query .= " AND e.transaction_date BETWEEN ? AND ?";
}

$query .= " ORDER BY e.transaction_date DESC";

$stmt = $conn->prepare($query);

if ($start_date && $end_date) {
    $stmt->bind_param("iss", $id_cabang, $start_date, $end_date);
} else {
    $stmt->bind_param("i", $id_cabang);
}

$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);

// Set nama file export
$filename = "Report_Expenses_" . ($start_date ?: 'ALL') . "_to_" . ($end_date ?: 'ALL') . ".xls";

// Header untuk export Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");

// Header kolom Excel
echo "Cabang\tKategori\tDeskripsi\tJumlah\tTanggal\tUser\n";

// Data export
foreach ($expenses as $expense) {
    echo implode("\t", [
        htmlspecialchars($expense['nama_cabang'] ?? '-'),
        htmlspecialchars($expense['category_name'] ?? '-'),
        htmlspecialchars($expense['description'] ?? '-'),
        "Rp" . number_format($expense['amount'], 0, ',', '.'),
        htmlspecialchars($expense['transaction_date'] ?? '-'),
        htmlspecialchars($expense['user_input'] ?? '-')
    ]) . "\n";
}

exit;
?>
