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
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

// Validasi CSRF token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Akses tidak valid.";
    header("Location: cabang.php");
    exit;
}

// Validasi input
$id_cabang = $_POST['id_cabang'] ?? null;
if (!$id_cabang || !is_numeric($id_cabang)) {
    $_SESSION['error_message'] = "ID cabang tidak valid.";
    header("Location: cabang.php");
    exit;
}

try {
    // Mulai transaksi
    $conn->begin_transaction();

    // Cek apakah cabang memiliki transaksi di tabel booking
    $queryCheckBooking = "SELECT COUNT(*) AS total_booking FROM booking WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryCheckBooking);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalBooking = $result->fetch_assoc()['total_booking'] ?? 0;

    if ($totalBooking > 0) {
        // Jika ada transaksi, batalkan penghapusan
        $_SESSION['error_message'] = "Cabang tidak dapat dihapus karena memiliki transaksi.";
        $conn->rollback();
        header("Location: cabang.php");
        exit;
    }

    // Hapus data terkait cabang (satu per satu query)
    $queryDeletePasien = "DELETE FROM pasien WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeletePasien);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    $queryDeleteProduk = "DELETE FROM produk WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteProduk);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    $queryDeleteTerapis = "DELETE FROM terapis WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteTerapis);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    $queryDeleteKapasitas = "DELETE FROM booking_kapasitas WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteKapasitas);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    $queryDeleteCategories = "DELETE FROM expense_categories WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteCategories);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    $queryDeleteExpenses = "DELETE FROM expenses WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteExpenses);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    // Hapus data cabang
    $queryDeleteCabang = "DELETE FROM cabang WHERE id_cabang = ?";
    $stmt = $conn->prepare($queryDeleteCabang);
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();

    // Commit transaksi
    $conn->commit();

    $_SESSION['success_message'] = "Cabang berhasil dihapus beserta data terkait.";
    header("Location: cabang.php");
    exit;

} catch (Exception $e) {
    // Rollback jika terjadi kesalahan
    $conn->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: cabang.php");
    exit;
}
?>
