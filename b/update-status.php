<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek level akses
if ($_SESSION['level'] !== 'cabang') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../login.php");
    exit;
}

// Cek CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
        header("Location: booking-data.php");
        exit;
    }
} else {
    $_SESSION['error_message'] = "Akses tidak valid.";
    header("Location: booking-data.php");
    exit;
}

// Koneksi ke database
include '../includes/config.php';
include '../includes/api-wa-old.php';
require '../includes/librarysmtp/autoload.php'; // PHPMailer autoload

// Validasi input
$id_booking = $_POST['id_booking'] ?? null;
$id_cabang = $_POST['idCabang'] ?? 0;
$status = $_POST['status'] ?? null;
$wa = $_POST['wa'] ?? null;
$email = $_POST['email'] ?? null;
$nama_pasien = $_POST['nama'] ?? null;
$tanggalBooking = $_POST['tanggalBooking'] ?? null;
$waktu_booking = $_POST['waktuBooking'] ?? null;
$bookingDate = date('d-M-Y', strtotime($tanggalBooking));
$pesan = "Mohon agar dapat datang tepat waktu sesuai jadwal treatment.";
$harga_total = $_POST['hargaTotal'] ?? 0; 
$user_input = $_SESSION['username'];

if (!$id_booking || !$status) {
    $_SESSION['error_message'] = "Parameter tidak lengkap.";
    header("Location: booking-data.php");
    exit;
}

// Sanitasi input
$id_booking = filter_var($id_booking, FILTER_VALIDATE_INT);
$status = htmlspecialchars(trim($status), ENT_QUOTES, 'UTF-8');
$contactNumber = $wa;

// Ambil data cabang berdasarkan id_cabang dari sesi
$queryCabang = "SELECT nama_cabang, alamat, link_google_map FROM cabang WHERE id_cabang = ?";
$stmtCabang = $conn->prepare($queryCabang);
$stmtCabang->bind_param("i", $id_cabang);
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();

if ($resultCabang->num_rows > 0) {
	$cabang = $resultCabang->fetch_assoc();
	$nama_cabang = $cabang['nama_cabang'];
	$alamat_cabang = $cabang['alamat'];
	$link_google_map = $cabang['link_google_map'];
} else {
	$nama_cabang = "Cabang tidak ditemukan";
	$alamat_cabang = "-";
	$link_google_map = "#";
}

$allowed_status = ['Confirmed', 'Pending', 'Cancelled']; // Status yang diizinkan
if (!in_array($status, $allowed_status)) {
    $_SESSION['error_message'] = "Status tidak valid.";
    header("Location: booking-data.php");
    exit;
}

if($status=="Confirmed"){
	$message = "Hai K {$nama_pasien} \n\nTerimakasih telah melakukan booking di *{$nama_cabang}*. \n\nBerikut detail booking nya : \nNomor Invoice : *INV00{$id_booking}*\nTanggal Treatment : *{$bookingDate}* \nJam : *{$waktu_booking}* \nStatus Pembayaran: *Lunas* \nTotal : *Rp.{$harga_total}* \n\nMohon agar dapat hadir tepat waktu sesuai jadwal treatment di *{$nama_cabang}*\n\n{$alamat_cabang}\n\nGoogle Map : {$link_google_map}. \n\nSalam Hangat,\n*Klinik Bebas Cedera*";
}elseif($status=="Pending"){
	$message = "Hai K {$nama_pasien} \n\nTerimakasih telah melakukan booking di *{$nama_cabang}*. \n\nBerikut detail booking nya : \nNomor Invoice : *INV00{$id_booking}*\nTanggal Treatment : *{$bookingDate}* \nJam : *{$waktu_booking}* \nTotal : *Rp.{$harga_total}* \n\nMohon untuk segera menyelesaikan pembayarannya agar dapat kami lanjutkan ke proses berikutnya\n\nSalam Hangat,\n*Klinik Bebas Cedera*";
}elseif($status=="Cancelled"){
	$message = "Hai K {$nama_pasien} \n\nOrder booking di *{$nama_cabang}*. \n\nDetail booking  : \nNomor Invoice : *INV00{$id_booking}*\nTanggal Treatment : *{$bookingDate}* \nJam : *{$waktu_booking}* \nTotal : *Rp.{$harga_total}* \n\nTelah kami BATALKAN \n\nSalam Hangat,\n*Klinik Bebas Cedera*";
}else{
	$message = "Hai K {$nama_pasien} \n\nTerimakasih telah melakukan booking di *{$nama_cabang}*. \n\nBerikut detail booking nya : \nNomor Invoice : *INV00{$id_booking}*\nTanggal Treatment : *{$bookingDate}* \nJam : *{$waktu_booking}* \nStatus Pembayaran: *Lunas* \nTotal : *Rp.{$harga_total}* \n\nMohon agar dapat hadir tepat waktu sesuai jadwal treatment di *{$nama_cabang}*\n\n{$alamat_cabang}\n\nGoogle Map : {$link_google_map}. \n\nSalam Hangat,\n*Klinik Bebas Cedera*";
}

try {
    // Query update status booking
    $query = "UPDATE booking SET status = ? WHERE id = ? AND id_cabang = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $status, $id_booking, $_SESSION['cabang']);

    if ($stmt->execute()) {
        // Jika status adalah Cancelled, hapus data dari tabel booking_kapasitas
        if ($status === 'Cancelled') 
		{
			// STEP: Hapus data di tabel booking_addons berdasarkan booking_id
			$queryDeleteAddons = "DELETE FROM booking_addons WHERE booking_id = ?";
			$stmtDeleteAddons = $conn->prepare($queryDeleteAddons);
			$stmtDeleteAddons->bind_param("i", $id_booking);

			if ($stmtDeleteAddons->execute()) {
				logActivity($_SESSION['username'], $id_booking, 'Deleted booking_addons');
			} else {
				throw new Exception("Gagal menghapus data dari tabel booking_addons.");
			}

			$stmtDeleteAddons->close();

			// STEP 1: Cek kapasitas_terpakai di tabel booking_kapasitas
			$querySlot = "
				SELECT kapasitas_terpakai 
				FROM booking_kapasitas 
				WHERE id_cabang = ? AND tanggal = ? AND jam = ?
			";
			$stmtSlot = $conn->prepare($querySlot);
			$stmtSlot->bind_param("iss", $id_cabang, $tanggalBooking, $waktu_booking);
			$stmtSlot->execute();
			$resultSlot = $stmtSlot->get_result();

			if ($resultSlot->num_rows >= 1) {
				// STEP 2: Hitung jumlah baris di tabel absensi_pasien berdasarkan booking_id
				$queryCountAbsensi = "
					SELECT COUNT(*) AS total_absensi 
					FROM absensi_pasien 
					WHERE booking_id = ?
				";
				$stmtCountAbsensi = $conn->prepare($queryCountAbsensi);
				$stmtCountAbsensi->bind_param("i", $id_booking);
				$stmtCountAbsensi->execute();
				$resultCountAbsensi = $stmtCountAbsensi->get_result();

				if ($resultCountAbsensi->num_rows > 0) {
					$row = $resultCountAbsensi->fetch_assoc();
					$total_absensi = $row['total_absensi']; // Jumlah baris di tabel absensi_pasien

					// STEP 3: Update kapasitas_terpakai di tabel booking_kapasitas
					$queryUpdateSlot = "
						UPDATE booking_kapasitas 
						SET kapasitas_terpakai = kapasitas_terpakai - ?, user_input = ? 
						WHERE id_cabang = ? AND tanggal = ? AND jam = ?
					";
					$stmtUpdateSlot = $conn->prepare($queryUpdateSlot);
					$stmtUpdateSlot->bind_param("isiss", $total_absensi, $user_input, $id_cabang, $tanggalBooking, $waktu_booking);
					$stmtUpdateSlot->execute();
				} else {
					throw new Exception("Gagal menghitung jumlah absensi.");
				}
				
			} else {
				// Jika tidak ada data di booking_kapasitas, hapus baris berdasarkan id_booking
				$queryDeleteSlot = "
					DELETE FROM booking_kapasitas 
					WHERE id_booking = ?
				";
				$stmtDeleteSlot = $conn->prepare($queryDeleteSlot);
				$stmtDeleteSlot->bind_param("i", $id_booking);
				$stmtDeleteSlot->execute();
			}
			
			// STEP 4: Hapus semua data di tabel absensi_pasien berdasarkan booking_id
			$queryDeleteAbsensi = "
				DELETE FROM absensi_pasien 
				WHERE booking_id = ?
			";
			$stmtDeleteAbsensi = $conn->prepare($queryDeleteAbsensi);
			$stmtDeleteAbsensi->bind_param("i", $id_booking);
			$stmtDeleteAbsensi->execute();
		}
	
        // Log aktivitas
        logActivity($_SESSION['username'], $id_booking, $status);
		
		 // Kirim email notifikasi
		$mail = new PHPMailer(true);

		try {
			// Konfigurasi server email
			$mail->isSMTP();
			$mail->Host = 'sangihe.iixcp.rumahweb.net';
			$mail->SMTPAuth = true;
			$mail->Username = 'admin@bebascedera.com';
			$mail->Password = '*123#sukses';
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = 465;

			// Konfigurasi pengirim dan penerima
			$mail->setFrom('admin@bebascedera.com', 'Klinik Bebas Cedera');
			$mail->addAddress($email, $nama_pasien);

			// Konten email
			$mail->isHTML(true);
			$mail->Subject = 'Konfirmasi Booking Anda';
			$mail->Body = "
				<p>Yth. {$nama_pasien},</p>
				<p>Terima kasih telah melakukan booking di Klinik Bebas Cedera.</p>
				<p>Detail Booking:</p>
				<ul>
				<li>Nomor Invoice : INV00{$id_booking}</li>
                <li>Tanggal Treatment : {$bookingDate}</li>
                <li>Jam : {$waktu_booking}</li>
				<li>Cabang : {$nama_cabang}</li>
				<li>Alamat : {$alamat_cabang}</li>
				<li>Maps : {$link_google_map}</li>
                <li>Status Pembayaran: {$statusNoted}</li>
                <li>Harga Total: Rp.{$harga_total}</li>
				</ul>
				<p>{$pesan}</p>
				<p>Salam Hangat,<br>Klinik Bebas Cedera</p>
			";

			$mail->send();
		} catch (Exception $e) {
			error_log("Gagal mengirim email: {$mail->ErrorInfo}");
		}

		$message;
		
		if ($contactNumber) {
			$response = sendWhatsAppMessage($contactNumber, $message);
			if ($response) {
				$_SESSION['success_message'] = "Pesan via WhatsApp telah dikirim ke pelanggan";
			} else {
				$_SESSION['success_error'] = "Pesan via WhatsApp gagal dikirim ke pelanggan";
			}
		}	

        $_SESSION['success_message'] = "Status booking berhasil diperbarui.";
    } else {
        throw new Exception("Gagal memperbarui status booking.");
    }

    $stmt->close();
    $conn->close();

    header("Location: booking-data.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: booking-data.php");
    exit;
}

/**
 * Fungsi untuk mencatat aktivitas ke log file.
 */
function logActivity($username, $id_booking, $status) {
    $logDir = __DIR__ . '/../logs/';
    $logFile = $logDir . 'booking-activity.log';

    // Pastikan folder log ada, jika tidak maka buat
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Format waktu sekarang
    $currentTime = date("Y-m-d H:i:s");

    // Ambil IP pengguna
    $userIP = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // Buat pesan log
    $logMessage = "[$currentTime] User: $username | ID Booking: $id_booking | Status: $status | IP: $userIP\n";

    // Tulis ke file log
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>
