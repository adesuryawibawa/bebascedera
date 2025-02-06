<?php

function sendWhatsAppMessage($contactNumber, $message){
	
	//$key='adpc4a7257a-23b2-431b-880e-3949744a7ac8'; 
	$key='b38d9b4afded3957e9bd0f405da2dccd';
	$url='https://notifapi.com/send_message';
	//$url='http://116.203.191.58/api/send_message';
	$data = array(
	  "phone_no"  => $contactNumber,
	  "key"       => $key,
	  "message"   => $message,
	  "skip_link" => True // This optional for skip snapshot of link in message
	);
	$data_string = json_encode($data);


	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 360);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	  'Content-Type: application/json',
	  'Content-Length: ' . strlen($data_string))
	);

	curl_exec($ch);
	curl_close($ch);
	
	 // Ambil kode HTTP dari respons
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Periksa apakah responsnya sukses (misalnya kode 200)
    if ($http_code == 200) {
        return true; // Pesan berhasil dikirim
    } else {
        error_log('WhatsApp API returned HTTP code ' . $http_code); // Log jika gagal
        return false; // Pesan gagal dikirim
    }
}

?>