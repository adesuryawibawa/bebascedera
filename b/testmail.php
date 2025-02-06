<?php
require '../includes/email_helper.php'; // Pastikan path ke PHPMailer benar

// Konfigurasi SMTP untuk Gmail
$smtpConfig = [
    'host' => 'smtp.gmail.com',
    'username' => 'bebas.cedera.official@gmail.com', // Ganti dengan email Gmail Anda
    'password' => '*lu4rb1as4', // Ganti dengan App Password Gmail Anda
    'port' => 465, // Gunakan port 465 untuk SSL
    'from_email' => 'bebas.cedera.official@gmail.com', // Email pengirim
    'from_name' => 'Klinik Bebas Cedera' // Nama pengirim
];

// Data email
$to = 'joe.surya.00@gmail.com'; // Ganti dengan email tujuan
$subject = 'Test Email from Gmail SMTP';
$body = '<p>Ini adalah email test menggunakan Gmail SMTP.</p>';

$result = kirimEmail($to, $subject, $body, $smtpConfig);

if ($result === true) {
    echo 'Email berhasil dikirim.';
} else {
    echo 'Gagal mengirim email. Error: ' . $result;
}
?>
