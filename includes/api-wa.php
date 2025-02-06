<?php

// Fungsi untuk mengirim pesan WhatsApp
function sendWhatsAppMessage($contactNumber, $message) {
    // Kunci API dan URL API WhatsApp
    $key = 'd9e13b452c7a2c60f5aa4c52914f5e5f';
    $url = 'https://notifapi.com/send_message';
    
    // Data yang akan dikirim
    $data = array(
        "phone_no"  => $contactNumber,
        "key"       => $key,
        "message"   => $message,
        "skip_link" => true // Opsi untuk melewati snapshot dari link dalam pesan
    );
  
    // Konversi data ke format JSON
    $data_string = json_encode($data);

    // Inisialisasi cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Menangkap respons
    curl_setopt($ch, CURLOPT_VERBOSE, true);  // Aktifkan output verbose untuk debug
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);  // Sesuaikan timeout menjadi 5 detik
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ));

    // Eksekusi cURL dan tangkap respons
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Ambil kode status HTTP

    // Tangkap informasi kesalahan jika terjadi
    if (curl_errno($ch)) {
        // Menangkap dan menampilkan pesan kesalahan cURL
        $error_msg = curl_error($ch);
        error_log("cURL Error: " . $error_msg);  // Tulis kesalahan ke log
        error_log("Response Code: " . $http_code);  // Tulis kode status ke log
        return false;  // Gagal mengirim pesan
    }

    // Log respons dari API WhatsApp
    error_log("WhatsApp API Response: " . $response);
    error_log("Response Code: " . $http_code);

    // Tutup cURL
    curl_close($ch);

    // Kembalikan respons API
    return $response;
}

?>

