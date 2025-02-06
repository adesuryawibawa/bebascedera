<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/config.php'; // Koneksi ke database

// Periksa apakah pengguna sudah login dan memiliki level "cabang"
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Validasi input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['nama_produk'], $_POST['deskripsi'], $_POST['harga'], $_POST['csrf_token'])) {
    
	$id = intval($_POST['id']);
	$namaProduk = trim($_POST['nama_produk']);
	$deskripsi = trim($_POST['deskripsi']);
	$harga = floatval($_POST['harga']);
	$kategori = trim($_POST['kategori']);
	$idCabang = $_SESSION['cabang'];
	$csrfToken = $_POST['csrf_token'];

    // Periksa CSRF token
    if ($csrfToken !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
        header("Location: products.php");
        exit;
    }

    // Periksa apakah nama produk baru sudah ada di database
    try {
        $sqlCheck = "SELECT id FROM produk WHERE nama_produk = ? AND id_cabang = ? AND id != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("sii", $namaProduk, $idCabang, $id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        
        if ($resultCheck->num_rows > 0) {
            $_SESSION['error_message'] = "Nama produk sudah digunakan.";
            header("Location: products.php");
            exit;
        }
        $stmtCheck->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat memeriksa nama produk.";
        header("Location: products.php");
        exit;
    }

    // Inisialisasi untuk upload gambar
    $uploadDir = "../assets/img-products/";
    $uploadedImage = $_FILES['image'] ?? null;
    $imageName = null;

    if ($uploadedImage && $uploadedImage['error'] === UPLOAD_ERR_OK) {
        // Validasi file gambar
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($uploadedImage['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error_message'] = "Format file tidak valid. Hanya mendukung JPG, PNG, dan WEBP.";
            header("Location: products.php");
            exit;
        }

        // Proses unggah dan konversi gambar ke .webp
        $imageTmpPath = $uploadedImage['tmp_name'];
        $imageName = uniqid('product_', true) . '.webp';
        $imageDestPath = $uploadDir . $imageName;

        // Buat gambar baru dengan background putih jika memiliki transparansi
        list($width, $height) = getimagesize($imageTmpPath);
        $newImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        if ($fileType === 'image/png') {
            $sourceImage = imagecreatefrompng($imageTmpPath);
        } elseif ($fileType === 'image/jpeg') {
            $sourceImage = imagecreatefromjpeg($imageTmpPath);
        } elseif ($fileType === 'image/webp') {
            $sourceImage = imagecreatefromwebp($imageTmpPath);
        }

        imagecopy($newImage, $sourceImage, 0, 0, 0, 0, $width, $height);
        imagewebp($newImage, $imageDestPath, 80); // Simpan sebagai .webp dengan kualitas 80
        imagedestroy($newImage);
        imagedestroy($sourceImage);
    }

    // Periksa apakah gambar tidak diunggah, gunakan gambar sebelumnya
    if (!$imageName) {
        try {
            $sql = "SELECT images FROM produk WHERE id = ? AND id_cabang = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $idCabang);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $imageName = $row['images'];
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error_message'] = "Terjadi kesalahan saat mengambil gambar produk.";
            header("Location: products.php");
            exit;
        }
    }

    // Update data ke database
    try {
       $sqlUpdate = "UPDATE produk SET nama_produk = ?, deskripsi = ?, harga = ?, images = ?, kategori = ? WHERE id = ? AND id_cabang = ?";
		$stmtUpdate = $conn->prepare($sqlUpdate);
		$stmtUpdate->bind_param("ssdsssi", $namaProduk, $deskripsi, $harga, $imageName, $kategori, $id, $idCabang);
		if ($stmtUpdate->execute()) {
			$_SESSION['success_message'] = "Produk berhasil diperbarui.";
		} else {
			throw new Exception("Gagal memperbarui produk: " . $stmtUpdate->error);
		}
		$stmtUpdate->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui produk.";
    }

    header("Location: products.php");
    exit;
} else {
    $_SESSION['error_message'] = "Permintaan tidak valid.";
    header("Location: products.php");
    exit;
}
