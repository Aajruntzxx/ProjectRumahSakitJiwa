<?php
// Mulai session
session_start();

// Panggil koneksi database
include "koneksi.php"; // Pastikan file ini berisi variabel $koneksi (mysqli_connect)

// Contoh: Mengecek apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RS Jiwa Kenangan</title>
  <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg"
    type="image/png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap"
    rel="stylesheet">

  <style>
    body {
      background-color: #f7fafc;
      font-family: 'Nunito Sans', sans-serif;
      color: #2d2d2d;
      scroll-behavior: smooth;
    }

    .navbar {
      transition: all 0.3s ease;
      background-color: #fff;
      padding: 14px 0;
      border-bottom: 4px solid #b3e5fc;
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      text-decoration: none;
    }

    .navbar-brand img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 10px;
      margin-right: 10px;
    }

    .navbar-brand h1 {
      font-size: 26px;
      font-weight: 700;
      color: #c44d3e;
      margin: 0;
    }

    .navbar-brand small {
      display: block;
      color: #666;
      font-size: 14px;
      margin-top: 2px;
    }

    .navbar-nav {
      gap: 10px;
    }

    .navbar-nav .nav-link {
      font-weight: 600;
      color: #333 !important;
      font-size: 16px;
      transition: color 0.3s ease;
    }

    .navbar-nav .nav-link.active,
    .navbar-nav .nav-link:hover {
      color: #c44d3e !important;
    }

    .bg-header {
      background: linear-gradient(135deg, #009999, #00b3b3);
      color: white;
      text-align: center;
      padding: 70px 20px;
      border-bottom-left-radius: 50px;
      border-bottom-right-radius: 50px;
    }

    .bg-header h1 {
      font-weight: 700;
      font-size: 2.2rem;
    }

    .bg-header p {
      font-size: 1.1rem;
      margin-top: 10px;
      color: #e0f7fa;
    }

    .card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 5px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-7px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card img {
      width: 80%;
      margin: 20px auto 0;
    }

    .card-title {
      color: #009999;
    }

    .card-text {
      font-size: 15px;
    }

    .btn-theme {
      background-color: #009999;
      color: #fff;
      border-radius: 25px;
      padding: 8px 20px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-theme:hover {
      background-color: #007a7a;
      color: #fff;
    }

    footer {
      background: #2d3947;
      color: #ccc;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: 50px;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="rsj.php">
        <img
          src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ="
          alt="Logo RS Jiwa Kenangan">
        <div>
          <h1 class="mb-0">RS Jiwa Kenangan</h1>
          <small>Jl. Kesumayudha No.29, Bangli</small>
        </div>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav fw-semibold">
          <li class="nav-item"><a class="nav-link active" href="rsj.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="jadwaldokter.php">Jadwal Dokter</a></li>
          <li class="nav-item"><a class="nav-link" href="pendaftaran.php">Pendaftaran</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HEADER -->
  <header class="bg-header mb-5">
    <h1>Pendaftaran Online RS Jiwa Kenangan</h1>
    <p>Silakan pilih jenis pendaftaran yang sesuai dengan kebutuhan Anda</p>
  </header>

  <!-- CONTENT -->
  <div class="container mb-5">
    <div class="row g-4 justify-content-center">
      <!-- Pasien Baru -->
      <div class="col-md-6 col-lg-3">
        <div class="card text-center h-100">
          <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Pasien Baru">
          <div class="card-body">
            <h5 class="card-title fw-bold">Pasien Baru</h5>
            <p class="card-text text-secondary">Pendaftaran bagi pasien yang belum pernah berobat di RS Jiwa Kenangan.</p>
            <a href="pasienbaru.php" class="btn btn-theme">Daftar Sekarang</a>
          </div>
        </div>
      </div>

      <!-- Pasien Lama -->
      <div class="col-md-6 col-lg-3">
        <div class="card text-center h-100">
          <img src="https://cdn-icons-png.flaticon.com/512/3135/3135679.png" alt="Pasien Lama">
          <div class="card-body">
            <h5 class="card-title fw-bold">Pasien Lama</h5>
            <p class="card-text text-secondary">Login menggunakan nomor rekam medis yang telah terdaftar.</p>
            <a href="pasienlama.php" class="btn btn-theme">Login</a>
          </div>
        </div>
      </div>

      <!-- Informasi Antrian -->
      <div class="col-md-6 col-lg-3">
        <div class="card text-center h-100">
          <img src="https://cdn-icons-png.flaticon.com/512/906/906334.png" alt="Informasi Antrian">
          <div class="card-body">
            <h5 class="card-title fw-bold">Informasi Antrian</h5>
            <p class="card-text text-secondary">Lihat nomor antrian dan jadwal pemeriksaan Anda secara real-time.</p>
            <a href="antrian.php" class="btn btn-theme">Lihat Antrian</a>
          </div>
        </div>
      </div>

      <!-- Pendaftaran Antrian Online -->
      <div class="col-md-6 col-lg-3">
        <div class="card text-center h-100">
          <img src="https://cdn-icons-png.flaticon.com/512/4205/4205906.png" alt="Pendaftaran Antrian Online">
          <div class="card-body">
            <h5 class="card-title fw-bold">Pendaftaran Antrian Online</h5>
            <p class="card-text text-secondary">Layanan untuk mencari nomor antrian dan mendaftar antrian</p>
            <a href="pendaftaran_antrian.php" class="btn btn-theme">Login Khusus</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <p class="mb-0">© 2025 RS Jiwa Kenangan — Semua Hak Dilindungi</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
