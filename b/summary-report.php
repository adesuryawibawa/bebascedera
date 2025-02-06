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

// Ambil bulan dan tahun sekarang
$current_month = date('m');
$current_year = date('Y');

// Query data untuk grafik line chart (pergerakan per tanggal)
$querySales = "
    SELECT DATE(tanggal_booking) AS tanggal, SUM(harga_total) AS total_booking
    FROM booking
    WHERE id_cabang = ? AND MONTH(tanggal_booking) = ? AND YEAR(tanggal_booking) = ?
    GROUP BY DATE(tanggal_booking)
    ORDER BY tanggal ASC
";
$stmtSales = $conn->prepare($querySales);
$stmtSales->bind_param("iii", $id_cabang, $current_month, $current_year);
$stmtSales->execute();
$resultSales = $stmtSales->get_result();
$salesData = $resultSales->fetch_all(MYSQLI_ASSOC);

$queryExpenses = "
    SELECT DATE(transaction_date) AS tanggal, SUM(amount) AS total_expense
    FROM expenses
    WHERE id_cabang = ? AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?
    GROUP BY DATE(transaction_date)
    ORDER BY tanggal ASC
";
$stmtExpenses = $conn->prepare($queryExpenses);
$stmtExpenses->bind_param("iii", $id_cabang, $current_month, $current_year);
$stmtExpenses->execute();
$resultExpenses = $stmtExpenses->get_result();
$expensesData = $resultExpenses->fetch_all(MYSQLI_ASSOC);

// Query untuk pie chart (produk dan penjualan)
$queryProductSales = "
    SELECT p.nama_produk, SUM(b.harga_total) AS total
    FROM booking b
    JOIN produk p ON b.id_produk = p.id
    WHERE b.id_cabang = ? AND MONTH(b.tanggal_booking) = ? AND YEAR(b.tanggal_booking) = ?
    GROUP BY p.nama_produk
";
$stmtProductSales = $conn->prepare($queryProductSales);
$stmtProductSales->bind_param("iii", $id_cabang, $current_month, $current_year);
$stmtProductSales->execute();
$resultProductSales = $stmtProductSales->get_result();
$productSalesData = $resultProductSales->fetch_all(MYSQLI_ASSOC);

// Query untuk pie chart (kategori expense)
$queryExpenseCategories = "
    SELECT ec.name AS category_name, SUM(e.amount) AS total
    FROM expenses e
    JOIN expense_categories ec ON e.category_id = ec.id
    WHERE e.id_cabang = ? AND MONTH(e.transaction_date) = ? AND YEAR(e.transaction_date) = ?
    GROUP BY ec.name
";
$stmtExpenseCategories = $conn->prepare($queryExpenseCategories);
$stmtExpenseCategories->bind_param("iii", $id_cabang, $current_month, $current_year);
$stmtExpenseCategories->execute();
$resultExpenseCategories = $stmtExpenseCategories->get_result();
$expenseCategoriesData = $resultExpenseCategories->fetch_all(MYSQLI_ASSOC);

include '_header.php';
?>

<div class="row">
    <div class="col-md-12 grid-margin">
        <h3 class="font-weight-bold">Laporan Ringkasan</h3>
        <h6 class="font-weight-normal mb-0">Perbandingan Penjualan dan Pengeluaran (Bulan Ini).</h6>
    </div>
</div>

<!-- Grafik Line Chart -->
<div class="row">
    <div class="col-md-12">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Pergerakan Penjualan vs Pengeluaran (Per Tanggal)</h5>
                <canvas id="lineChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Pie Charts -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Penjualan per Produk (Bulan Ini)</h5>
                <canvas id="productSalesPieChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <div class="card-body">
                <h5 class="card-title">Pengeluaran per Kategori (Bulan Ini)</h5>
                <canvas id="expenseCategoryPieChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Data untuk Line Chart
const salesData = <?= json_encode($salesData); ?>;
const expensesData = <?= json_encode($expensesData); ?>;

const lineChartLabels = [];
const lineChartSales = [];
const lineChartExpenses = [];

salesData.forEach(item => {
    lineChartLabels.push(item.tanggal);
    lineChartSales.push(item.total_booking);
});

expensesData.forEach(item => {
    if (!lineChartLabels.includes(item.tanggal)) {
        lineChartLabels.push(item.tanggal);
    }
    lineChartExpenses.push(item.total_expense);
});

const lineChartCtx = document.getElementById('lineChart').getContext('2d');
new Chart(lineChartCtx, {
    type: 'line',
    data: {
        labels: lineChartLabels,
        datasets: [
            {
                label: 'Penjualan',
                data: lineChartSales,
                borderColor: 'rgba(54, 162, 235, 1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Pengeluaran',
                data: lineChartExpenses,
                borderColor: 'rgba(255, 99, 132, 1)',
                fill: false,
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Data untuk Pie Chart Penjualan per Produk
const productSalesData = <?= json_encode($productSalesData); ?>;
const productSalesLabels = productSalesData.map(item => item.nama_produk);
const productSalesValues = productSalesData.map(item => item.total);

const productSalesPieCtx = document.getElementById('productSalesPieChart').getContext('2d');
new Chart(productSalesPieCtx, {
    type: 'pie',
    data: {
        labels: productSalesLabels,
        datasets: [{
            data: productSalesValues,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
        }]
    },
    options: {
        responsive: true
    }
});

// Data untuk Pie Chart Pengeluaran per Kategori
const expenseCategoriesData = <?= json_encode($expenseCategoriesData); ?>;
const expenseCategoryLabels = expenseCategoriesData.map(item => item.category_name);
const expenseCategoryValues = expenseCategoriesData.map(item => item.total);

const expenseCategoryPieCtx = document.getElementById('expenseCategoryPieChart').getContext('2d');
new Chart(expenseCategoryPieCtx, {
    type: 'pie',
    data: {
        labels: expenseCategoryLabels,
        datasets: [{
            data: expenseCategoryValues,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
        }]
    },
    options: {
        responsive: true
    }
});
</script>

<?php include '_footer.php'; ?>
