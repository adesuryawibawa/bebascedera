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

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token CSRF tidak valid.";
    header("Location: booking-add.php");
    exit;
}

// Koneksi ke database
include '../includes/config.php';
require '../includes/librarysmtp/autoload.php'; // PHPMailer autoload
include '../includes/api-wa-old.php';

$conn->begin_transaction(); // Mulai transaksi

try {
    // Keamanan CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Token CSRF tidak valid.");
    }

    // Keamanan double-submit
    if (isset($_SESSION['last_submit_time']) && time() - $_SESSION['last_submit_time'] < 5) {
        throw new Exception("Terlalu cepat mengirimkan formulir, coba lagi.");
    }
    $_SESSION['last_submit_time'] = time();

    // Data dari frontend
    $id_cabang = $_SESSION['cabang'];
    $id_produk = (int) $_POST['id_produk'];
    $id_terapis = (int) $_POST['id_terapis'];
    $tanggal_booking = $_POST['tanggal_booking'];
    $waktu_booking = $_POST['waktu_booking'];
	$paymentMethod = ['Cash','Transfer Bank','E-Wallet','QRIS','CC','DEBET','Transfer Event','Transfer Homecare'];
    $metode_pembayaran = in_array($_POST['metode_pembayaran'], $paymentMethod) ? $_POST['metode_pembayaran'] : 'Cash';

	$harga_per_item = (float) $_POST['hargaProduk'];
    $kode_promo = $_POST['kode_promo'] ?? null;
    $diskon = (float) $_POST['diskonPromo'];
    $potongan_harga = (float) $_POST['potonganHarga'];
    $harga_total = (float) $_POST['total_bayar'];
	
    $keluhan = !empty($_POST['keterangan']) ? $_POST['keterangan'] : null;
    $addon_data = $_POST['addons'] ?? []; // Array produk add-ons
    $user_input = $_SESSION['username'];
	// Daftar nilai status yang valid
	$validStatuses = ['Pending', 'Confirmed', 'Cancelled'];
	// Validasi input status
	$status = in_array($_POST['status_pembayaran'], $validStatuses) ? $_POST['status_pembayaran'] : 'Pending';
	$bookingDate = date('d-M-Y', strtotime($tanggal_booking));
	
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

	
	//Cek status pembayaran
	if ($status=="Confirmed"){
		$statusNoted = "Lunas";
	}else{
		$statusNoted = "Menunggu Pembayaran";
	}
	// Ekstrak waktu awal dari rentang waktu
	if (strpos($waktu_booking, ' - ') !== false) {
		$waktu_booking = explode(' - ', $waktu_booking)[0];
	} else {
		$errors[] = "Format waktu booking tidak valid.";
	}
	
    // STEP 1: Insert atau update data pasien
	$nama_pasien = $_POST['nama_pasien'];
	$jenis_kelamin = $_POST['jenis_kelamin'];
	$tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
	$nomor_wa = $_POST['nomor_wa'];
	$email_pasien = !empty($_POST['email_pasien']) ? $_POST['email_pasien'] : null;
	$alamat = !empty($_POST['alamat']) ? $_POST['alamat'] : null;
	$contactNumber = $_POST['nomor_wa'];

	// 1. Cek apakah pasien sudah ada berdasarkan nama, nomor WA, dan email
	$queryCheckPasien = "
		SELECT id FROM pasien 
		WHERE nama = ? AND nomor_wa = ? AND email = ?
	";
	$stmtCheckPasien = $conn->prepare($queryCheckPasien);
	$stmtCheckPasien->bind_param("sss", $nama_pasien, $nomor_wa, $email_pasien);
	$stmtCheckPasien->execute();
	$resultCheckPasien = $stmtCheckPasien->get_result();

	if ($resultCheckPasien->num_rows > 0) {
		// Pasien sudah ada, lakukan update
		$row = $resultCheckPasien->fetch_assoc();
		$id_pasien = $row['id'];

		$queryUpdatePasien = "
			UPDATE pasien 
			SET id_cabang = ?, jenis_kelamin = ?, tempat_lahir = ?, tanggal_lahir = ?, alamat = ?, user_input = ?
			WHERE id = ?
		";
		$stmtUpdatePasien = $conn->prepare($queryUpdatePasien);
		$stmtUpdatePasien->bind_param(
			"isssssi",
			$id_cabang,
			$jenis_kelamin,
			$tempat_lahir,
			$tanggal_lahir,
			$alamat,
			$user_input,
			$id_pasien
		);
		$stmtUpdatePasien->execute();
	} else {
		// Pasien belum ada, lakukan insert
		$queryInsertPasien = "
			INSERT INTO pasien (id_cabang, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, nomor_wa, email, alamat, user_input, created_at) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
		";
		$stmtInsertPasien = $conn->prepare($queryInsertPasien);
		$stmtInsertPasien->bind_param(
			"issssssss",
			$id_cabang,
			$nama_pasien,
			$jenis_kelamin,
			$tempat_lahir,
			$tanggal_lahir,
			$nomor_wa,
			$email_pasien,
			$alamat,
			$user_input
		);
		$stmtInsertPasien->execute();
		$id_pasien = $stmtInsertPasien->insert_id;
	}

		
	// STEP 2: Insert data ke tabel booking
	 $queryInsertBooking = "
        INSERT INTO booking (
            id_cabang, id_produk, id_pasien, tanggal_booking, waktu_booking, metode_pembayaran, harga_per_item, kode_promo, 
            diskon, potongan_harga, keluhan, status, user_input, id_terapis
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtInsertBooking = $conn->prepare($queryInsertBooking);
	$stmtInsertBooking->bind_param(
		"iiisssdsddsssi", // Format parameter (i = integer, s = string, d = double)
		$id_cabang,        // Integer: ID cabang
		$id_produk,        // Integer: ID produk
		$id_pasien,        // Integer: ID pasien
		$tanggal_booking,  // String: Tanggal booking (format 'Y-m-d')
		$waktu_booking,    // String: Waktu booking (format 'H:i')
		$metode_pembayaran,// String: Metode pembayaran (e.g., 'Cash', 'Transfer')
		$harga_per_item,   // Double: Harga per item produk utama
		$kode_promo,       // String: Kode promo (nullable)
		$diskon,           // Double: Jumlah diskon (nullable)
		$potongan_harga,   // Double: Potongan harga tambahan (nullable)
		$keluhan,          // String: Keluhan atau keterangan tambahan (nullable)
		$status,           // String: Status booking ('Pending', 'Confirmed', 'Cancelled')
		$user_input,       // String: User yang memasukkan data
		$id_terapis        // Integer: ID terapis yang ditugaskan
	);
    $stmtInsertBooking->execute();
    $id_booking = $stmtInsertBooking->insert_id;
	
	// STEP 3: Insert data ke tabel booking_addons (jika ada)
	if (isset($_POST['addons']) && is_array($_POST['addons'])) {
		foreach ($_POST['addons'] as $addon) {
			// Validasi data Add-on
			$addonId = isset($addon['id']) ? (int)$addon['id'] : null;
			$addonQty = isset($addon['qty']) ? (int)$addon['qty'] : 0;
			$addonPrice = isset($addon['price']) ? (float)$addon['price'] : 0;
			$addonDiscount = isset($addon['discount']) ? (float)$addon['discount'] : 0;
			
			if ($addonId && $addonQty > 0) {
				// Masukkan data ke tabel booking_addons tanpa harga total
				$stmtAddons = $conn->prepare("INSERT INTO booking_addons (booking_id, id_produk, id_cabang, jumlah, harga_per_item, potongan, created_at, user) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
				$stmtAddons->bind_param("iiiidis", $id_booking, $addonId, $id_cabang, $addonQty, $addonPrice, $addonDiscount, $user_input);
				$stmtAddons->execute();
			}
		}
	}

    // STEP 4: Insert atau update data ke tabel booking_kapasitas
    $querySlot = "
        SELECT kapasitas_terpakai 
        FROM booking_kapasitas 
        WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
    $stmtSlot = $conn->prepare($querySlot);
    $stmtSlot->bind_param("iss", $id_cabang, $tanggal_booking, $waktu_booking);
    $stmtSlot->execute();
    $resultSlot = $stmtSlot->get_result();

    if ($resultSlot->num_rows > 0) {
        $queryUpdateSlot = "
            UPDATE booking_kapasitas 
            SET kapasitas_terpakai = kapasitas_terpakai + 1, user_input = ? 
            WHERE id_cabang = ? AND tanggal = ? AND jam = ?";
        $stmtUpdateSlot = $conn->prepare($queryUpdateSlot);
        $stmtUpdateSlot->bind_param("siss", $user_input, $id_cabang, $tanggal_booking, $waktu_booking);
        $stmtUpdateSlot->execute();
    } else {
        $queryInsertSlot = "
            INSERT INTO booking_kapasitas (id_booking, id_cabang, tanggal, jam, kapasitas_terpakai, user_input) 
            VALUES (?, ?, ?, ?, 1, ?)";
        $stmtInsertSlot = $conn->prepare($queryInsertSlot);
        $stmtInsertSlot->bind_param("iisss", $id_booking, $id_cabang, $tanggal_booking, $waktu_booking, $user_input);
        $stmtInsertSlot->execute();
    }
	
	// STEP 5: Insert ke table absensi pasien
	$queryInsertAbsensi = "
		INSERT INTO absensi_pasien (booking_id, tanggal_booking, waktu_booking, id_terapis, created_by) 
		VALUES (?, ?, ?, ?, ?)
	";

	// Persiapkan statement
	$stmtInsertAbsensi = $conn->prepare($queryInsertAbsensi);

	// Bind parameter
	$stmtInsertAbsensi->bind_param(
		"issss", 
		$id_booking,
		$tanggal_booking,
		$waktu_booking,
		$id_terapis,
		$user_input
	);
	$stmtInsertAbsensi->execute();
	
    // STEP 6: Commit transaksi
    $conn->commit();

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
        $mail->addAddress($email_pasien, $nama_pasien);

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
                <li>Metode Pembayaran: {$metode_pembayaran} - {$statusNoted}</li>
                <li>Harga Total: Rp " . number_format($harga_total, 0, ',', '.') . "</li>
            </ul>
			<p>Mohon datang tepat waktu sesuai jadwal treatment.</p>
            <p>Salam Hangat,<br>Klinik Bebas Cedera</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Gagal mengirim email: {$mail->ErrorInfo}");
    }

    // STEP 7: Kirim WhatsApp notifikasi (non-blocking)
	$message = "Hai K {$nama_pasien} \n\nTerimakasih telah melakukan booking di *{$nama_cabang}*. \n\nBerikut detail booking nya : \nNomor Invoice : *INV00{$id_booking}*\nTanggal Treatment : *{$bookingDate}* \nJam : *{$waktu_booking}* \nMetode Pembayaran: *{$metode_pembayaran} - {$statusNoted}* \nTotal : *Rp. ".number_format($harga_total)."* \n\nMohon agar dapat hadir tepat waktu sesuai jadwal. Jika terlambat akan mengurangi durasi terapi.\n\nTreatment di *{$nama_cabang}*\n\n{$alamat_cabang}\n\nGoogle Map : {$link_google_map}. \n\nSalam Hangat,\n*Klinik Bebas Cedera*";
	if ($contactNumber) {
		$response = sendWhatsAppMessage($contactNumber, $message);
		if ($response) {
			error_log("Pesan WhatsApp berhasil dikirim.");
		} else {
			error_log("Pesan WhatsApp gagal dikirim.");
		}
	}
	
    // STEP 8: Simpan pesan ke session dan redirect
    $_SESSION['success_message'] = "Booking berhasil dibuat.";
    header("Location: booking-add.php");
    exit;

} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();

    // Simpan pesan error ke session
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: booking-add.php");
    exit;
}
?>
