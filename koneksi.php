<?php
// Pengaturan koneksi database
$dbHost     = "localhost"; // Ganti jika host database Anda berbeda
$dbUser     = "root";      // Ganti dengan username database Anda
$dbPass     = "";          // Ganti dengan password database Anda
$dbName     = "rsjiwa_antrian"; // Nama database

// Membuat koneksi
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set karakter set ke utf8mb4 (disarankan)
mysqli_set_charset($conn, "utf8mb4");

// Fungsi untuk membersihkan input (menggunakan mysqli_real_escape_string)
function escape_input($conn, $data) {
    return mysqli_real_escape_string($conn, $data);
}
?>