<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Metode request tidak valid.";
    header("Location: products.php");
    exit;
}

// Validasi CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: products.php");
    exit;
}

// Validasi input
$productId = intval($_POST['delete_product_id'] ?? 0);
if ($productId <= 0) {
    $_SESSION['error_message'] = "ID produk tidak valid.";
    header("Location: products.php");
    exit;
}

include '../includes/config.php'; // Koneksi ke database

try {
    // Periksa apakah produk terkait dengan transaksi (tabel booking)
    $queryCheck = "SELECT COUNT(*) as total FROM booking WHERE id_produk = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("i", $productId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $data = $resultCheck->fetch_assoc();
    $stmtCheck->close();
	
	
    if ($data['total'] > 0) {
        // Produk terkait dengan transaksi, ubah status menjadi nonaktif
        $queryUpdate = "UPDATE produk SET status = 'nonactive' WHERE id = ?";
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->bind_param("i", $productId);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $_SESSION['success_message'] = "Produk berhasil dinonaktifkan karena terkait dengan transaksi.";
    } else {
        // Produk tidak terkait dengan transaksi, hapus dari database dan file gambar
        $queryImage = "SELECT images FROM produk WHERE id = ?";
        $stmtImage = $conn->prepare($queryImage);
        $stmtImage->bind_param("i", $productId);
        $stmtImage->execute();
        $resultImage = $stmtImage->get_result();
        $imageData = $resultImage->fetch_assoc();
        $stmtImage->close();

        // Hapus file gambar jika ada
        if (!empty($imageData['images'])) {
            $imagePath = "../assets/img-products/" . $imageData['images'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Hapus produk dari database
        $queryDelete = "DELETE FROM produk WHERE id = ?";
        $stmtDelete = $conn->prepare($queryDelete);
        $stmtDelete->bind_param("i", $productId);
        $stmtDelete->execute();
        $stmtDelete->close();

        $_SESSION['success_message'] = "Produk berhasil dihapus.";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan saat memproses penghapusan produk.";
}

header("Location: products.php");
exit;
