<?php
// koneksi.php
$host = "localhost";
$user = "root";      // ubah jika user MySQL kamu berbeda
$pass = "";          // isi password MySQL kamu
$db   = "db_rsj";    // nama database kamu

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
