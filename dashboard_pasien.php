<?php
session_start();

// Cek apakah pasien sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: pasienlama.php"); // Redirect kembali ke halaman login jika belum login
    exit();
}

$nama_pasien = $_SESSION['nama_pasien'];
$no_rm = $_SESSION['no_rm'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Pasien</title>
    </head>
<body>
    <div class="container mt-5">
        <h1>Selamat Datang, <?php echo htmlspecialchars($nama_pasien); ?>!</h1>
        <p>Nomor Rekam Medis Anda: <b><?php echo htmlspecialchars($no_rm); ?></b></p>
        <p>Anda telah berhasil login. Di sini Anda bisa melihat riwayat kunjungan, janji temu, atau mendaftar antrian.</p>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>