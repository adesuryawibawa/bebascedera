<?php

/*
$db_host = 'localhost';     
$db_user = 'bebp2811_adesuryawibawa';  
$db_pass = 'Jur4g4nCl1nics#99Bismillah!';   
$db_name = 'bebp2811_clinic_app'; 
*/

$db_host = 'localhost';     
$db_user = 'root';  
$db_pass = '';   
$db_name = 'klinik_bebascedera_com'; 

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

?>
