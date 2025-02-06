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

// Filter tanggal (jika ada)
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

include '_header.php';
?>

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">Laporan Pengeluaran</h3>
        <h6 class="font-weight-normal mb-0">Lihat laporan pengeluaran klinik Anda.</h6>
    </div>
</div>

<!-- Filter Tanggal -->
<div class="row mb-4">
    <div class="col-md-12">
        <form method="GET" action="report-expenses.php" class="d-flex align-items-center">
            <div class="form-group me-3">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group me-3">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-md ">Filter</button>
            <a href="report-expenses-export.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" 
               class="btn btn-success btn-md ms-3">Export Excel</a>
        </form>
    </div>
</div>

<!-- Tabel Data -->
<div class="row">
    <div class="col-md-12">
        <table id="expensesTable" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Cabang</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                    <th>Tanggal</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($expense['nama_cabang'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($expense['category_name'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($expense['description'] ?? '-'); ?></td>
                    <td>Rp<?= number_format($expense['amount'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($expense['transaction_date'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($expense['user_input'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function () {
    jqOld('#expensesTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": true
    });
});
</script>

<?php include '_footer.php'; ?>
