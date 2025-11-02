<?php
// Mulai session
session_start();

// Hapus semua variabel sesi yang terdaftar
session_unset();

// Hancurkan sesi di server
session_destroy();

// Redirect pengguna kembali ke halaman login pasien lama
header("Location: pasienlama.php");
exit();
?>