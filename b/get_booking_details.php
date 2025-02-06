<?php
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_booking'])) {
    $id_booking = (int) $_POST['id_booking'];

    // Query untuk mendapatkan detail booking
    $queryBooking = "
        SELECT 
            b.id AS booking_id, 
            b.tanggal_booking, 
            b.waktu_booking, 
            p.nama AS nama_pasien, 
            t.nama_terapis AS nama_terapis, 
            b.harga_total, 
            b.status,
            b.kode_promo
        FROM booking AS b
        LEFT JOIN pasien AS p ON b.id_pasien = p.id
        LEFT JOIN terapis AS t ON b.id_terapis = t.id
        WHERE b.id = ?
    ";

    $stmtBooking = $conn->prepare($queryBooking);
    $stmtBooking->bind_param("i", $id_booking);
    $stmtBooking->execute();
    $resultBooking = $stmtBooking->get_result();
    $bookingData = $resultBooking->fetch_assoc();
    $stmtBooking->close();

    // Query untuk mendapatkan produk add-ons
    $queryAddons = "
        SELECT pro.nama_produk, ba.jumlah, ba.harga_total 
        FROM booking_addons AS ba
        LEFT JOIN produk AS pro ON ba.id_produk = pro.id
        WHERE ba.booking_id = ?
    ";
    $stmtAddons = $conn->prepare($queryAddons);
    $stmtAddons->bind_param("i", $id_booking);
    $stmtAddons->execute();
    $resultAddons = $stmtAddons->get_result();

    $addons = [];
    while ($row = $resultAddons->fetch_assoc()) {
        $addons[] = $row;
    }
    $stmtAddons->close();

    // Gabungkan data booking dan add-ons
    $bookingData['addons'] = $addons;

    echo json_encode($bookingData);
    exit;
}
?>
