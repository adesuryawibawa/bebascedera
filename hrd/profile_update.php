<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Nonaktifkan laporan kesalahan MySQLi bawaan
mysqli_report(MYSQLI_REPORT_OFF);

try {
    // Cek apakah pengguna sudah login
	if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
		$_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; // Simpan pesan error dalam session
		header("Location: ../login.php"); // Redirect ke halaman login jika belum login
		exit;
	}

	// Cek apakah level pengguna adalah "hrd"
	if ($_SESSION['level'] !== 'hrd') {
		$_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
		header("Location: ../login.php"); // Redirect ke halaman login jika bukan hrd
		exit;
	}

    // Cek apakah sesi sudah timeout (tidak ada aktivitas selama 30 menit)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset(); // Hapus semua variabel sesi
        session_destroy(); // Hancurkan sesi
        session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
        $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali.";
        header("Location: ../login.php");
        exit;
    }

    require '../includes/config.php';

    // Fungsi untuk sanitasi input di sisi server
    function sanitizeInput($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    // Fungsi untuk resize dan konversi gambar ke .webp
    function convertToWebp($source, $destination, $new_width, $new_height) {
        list($width, $height, $type) = getimagesize($source);

        // Buat sumber gambar berdasarkan tipe file
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($source);
                break;
            default:
                return false; // Format gambar tidak didukung
        }

        // Buat gambar baru dengan ukuran yang diinginkan
        $new_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Simpan gambar dalam format .webp
        $result = imagewebp($new_image, $destination);

        // Hapus gambar dari memori
        imagedestroy($image);
        imagedestroy($new_image);

        return $result;
    }

    // Ambil input dari form
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $about = isset($_POST['about']) ? sanitizeInput($_POST['about']) : '';
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $picture = $_FILES['picture'];

    // Validasi input - semua field wajib kecuali about, picture, password
    if (empty($fullname) || empty($email) || empty($phone)) {
        throw new Exception("Full Name, Email, dan Phone harus diisi.");
    }

    // Validasi email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Format email tidak valid.");
    }

    // Validasi phone (hanya angka)
    if (!preg_match('/^[0-9]+$/', $phone) || strlen($phone) < 10) {
        throw new Exception("Nomor telepon tidak valid. Hanya angka dan minimal 10 digit.");
    }

    // Validasi file gambar (optional)
    if (!empty($picture['name'])) {
        $targetDir = "../assets/images/faces/";
        $imageFileType = strtolower(pathinfo($picture['name'], PATHINFO_EXTENSION));

        // Pastikan file yang diunggah adalah gambar dengan format jpg, jpeg, atau png
        $allowedFormats = ['jpg', 'jpeg', 'png'];
        if (!in_array($imageFileType, $allowedFormats)) {
            throw new Exception("Hanya file gambar JPG, JPEG, atau PNG yang diizinkan.");
        }

        // Penamaan acak untuk file gambar menggunakan uniqid() agar tidak duplikat
        $randomName = uniqid('profile_', true) . ".webp";
        $targetFile = $targetDir . $randomName;

        // Pindahkan dan konversi gambar ke .webp dengan ukuran 150x150px
        if (convertToWebp($picture['tmp_name'], $targetFile, 150, 150)) {
            // Jika ada gambar lama, hapus dari server
            if (!empty($_SESSION['picture']) && file_exists($targetDir . $_SESSION['picture']) && $_SESSION['picture'] !== 'no-images.png') {
                unlink($targetDir . $_SESSION['picture']);
            }
            $_SESSION['picture'] = $randomName; // Simpan nama file acak .webp di session
        } else {
            throw new Exception("Gagal mengonversi gambar ke format .webp.");
        }
    } else {
        // Jika tidak ada gambar diunggah, gunakan gambar yang sudah ada di session
        if (!isset($_SESSION['picture']) || empty($_SESSION['picture'])) {
            $_SESSION['picture'] = 'no-images.png'; // Gambar default jika tidak ada gambar
        }
    }

    // Query Update ke Database
    if ($password) {
        // Jika password diisi, update semua termasuk password
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, picture = ?, about = ?, password = ? WHERE username = ?");
        $stmt->bind_param("sssssss", $fullname, $email, $phone, $_SESSION['picture'], $about, $password, $_SESSION['username']);
    } else {
        // Jika password tidak diisi, update field lainnya tanpa password
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, picture = ?, about = ? WHERE username = ?");
        $stmt->bind_param("ssssss", $fullname, $email, $phone, $_SESSION['picture'], $about, $_SESSION['username']);
    }

    // Eksekusi query dan cek keberhasilan
    if ($stmt->execute()) {
        // Update sesi dengan data baru
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $_SESSION['about'] = $about;

        $_SESSION['success_message'] = "Profil Anda berhasil diperbarui.";
        header("Location: profile.php?success=1");
    } else {
        throw new Exception("Gagal memperbarui profil. Silakan coba lagi.");
    }

    // Menutup statement dan koneksi
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Logging kesalahan ke file log
    error_log($e->getMessage());

    // Simpan pesan error dalam session dan redirect
    $_SESSION['error_message'] = "Terjadi kesalahan, silakan coba lagi.";
    header("Location: profile.php");
    exit;
}
?>
