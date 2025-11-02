<?php
include "koneksi.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RS Jiwa Kenangan</title>
  <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

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

    .hero {
      background: linear-gradient(120deg, #4fa9c6, #5cbecf, #78c3e0);
      color: #fff;
      padding: 100px 0;
      border-bottom-left-radius: 80px;
      border-bottom-right-radius: 80px;
    }

    .btn-custom {
      background-color: #f8b25c;
      color: #fff;
      font-weight: 700;
      border-radius: 30px;
      transition: all 0.3s ease;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-custom:hover {
      background-color: #eaa244;
      transform: translateY(-2px);
      color: #fff;
    }

    .info-section .card {
      border-radius: 18px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-section .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 22px rgba(0, 0, 0, 0.1);
    }

    .register-section {
      background: linear-gradient(90deg, #c44d3e, #a23b31);
      color: #fff;
      text-align: center;
      padding: 100px 20px;
      border-top-left-radius: 80px;
      border-top-right-radius: 80px;
    }

    .btn-register {
      background: linear-gradient(90deg, #f8b25c, #eaa244);
      color: #fff;
      border: none;
      border-radius: 30px;
      font-weight: 700;
      padding: 12px 35px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    }

    .footer {
      background-color: #3f5163;
      color: #fff;
      padding: 70px 6%;
    }

    footer {
      background: #2d3947;
      color: #ccc;
      text-align: center;
      padding: 15px;
      font-size: 14px;
    }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="rsj.php">
        <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ=" alt="Logo RS Jiwa Kenangan">
        <div>
          <h1 class="mb-0">RS Jiwa Kenangan</h1>
          <small>Jl. Kaliurang No.12, Sleman, Yogyakarta</small>
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
          <li class="nav-item"><a class="nav-link" href="riwayat.php">Data Pasien</a></li>
          <li class="nav-item"><a class="nav-link" href="konsultasi.php">Konsultasi Online</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero text-center text-md-start">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between">
      <div class="hero-text mb-4 mb-md-0">
        <h2 class="fw-bold display-5">Selamat Datang</h2>
        <p class="fs-5">Selamat datang di layanan pendaftaran online <strong>Rumah Sakit Jiwa Kenangan Yogyakarta</strong>.<br>Pasien dapat melakukan pendaftaran layanan secara mudah melalui situs ini.</p>
        <a href="pendaftaran.php" class="btn btn-custom px-4 py-2 mt-3 me-2">Daftar Sekarang</a>
        <a href="konsultasi.php" class="btn btn-outline-light px-4 py-2 mt-3">Konsultasi Online</a>
      </div>
      <img src="https://tirta.co.id/yoi/assets/img/healthcare-characters-001.png" class="img-fluid" width="400" alt="Ilustrasi RS">
    </div>
  </section>

  <!-- INFORMASI -->
  <section class="info-section py-5 bg-white text-center">
    <div class="container">
      <h2>Informasi Layanan RS Jiwa Kenangan</h2>
      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/3845/3845823.png" width="65" class="mx-auto mb-3" alt="Pendaftaran Umum">
            <p>Pendaftaran online melalui website ini ditujukan untuk pasien umum maupun peserta BPJS.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/3845/3845835.png" width="65" class="mx-auto mb-3" alt="Surat Keterangan">
            <p>Pendaftaran surat keterangan dapat dilakukan secara online dan akan menghasilkan nomor konfirmasi.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/3845/3845812.png" width="65" class="mx-auto mb-3" alt="Antrian Tes">
            <p>Antrian pemeriksaan dibagi sebagai berikut: No. 01–25 (07.30), No. 26–50 (09.00), No. 51–75 (10.00), No. 76–100 (11.00) WIB.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/3845/3845832.png" width="65" class="mx-auto mb-3" alt="Tes NAPZA">
            <p>Untuk konfirmasi jenis tes NAPZA (4, 6, atau 7 parameter), hubungi hotline kami di 0812-3456-7890.</p>
          </div>
        </div>

        <!-- FITUR BARU -->
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/4149/4149645.png" width="65" class="mx-auto mb-3" alt="Akses Data Pasien">
            <p><strong>Akses Data Pasien:</strong> Pasien dapat melihat riwayat kunjungan, hasil laboratorium, dan radiologi secara online dengan aman.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card h-100 border-0 shadow-sm p-4">
            <img src="https://cdn-icons-png.flaticon.com/512/4712/4712109.png" width="65" class="mx-auto mb-3" alt="Konsultasi Online">
            <p><strong>Konsultasi Online:</strong> Fitur chat atau pesan langsung untuk berkomunikasi dengan konselor jiwa secara daring.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PENDAFTARAN -->
  <section id="daftar" class="register-section">
    <div class="container">
      <h2 class="fw-bold mb-3">Pendaftaran Online RS Jiwa Kenangan</h2>
      <p class="fs-5 mb-4">Menjadi pusat pelayanan kesehatan jiwa unggulan di Yogyakarta.</p>
      <a href="pendaftaran.php" class="btn btn-register me-3">Daftar Sekarang</a>
      <a href="riwayat.php" class="btn btn-light">Lihat Riwayat Pasien</a>
    </div>
  </section>

  <!-- FOOTER -->
  <div class="footer text-white">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <h5 class="text-warning mb-3">RS Jiwa Kenangan</h5>
          <p>Menjadi pusat pelayanan kesehatan jiwa yang unggul, humanis, dan terpercaya di Yogyakarta.</p>
        </div>
        <div class="col-md-4">
          <h5 class="text-warning mb-3">Lokasi Kami</h5>
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!..." width="100%" height="200" style="border:0;" allowfullscreen loading="lazy"></iframe>
        </div>
        <div class="col-md-4">
          <h5 class="text-warning mb-3">Hubungi Kami</h5>
          <p>Jl. Kaliurang No.12, Sleman, Yogyakarta 55281</p><br>
          <a href="tel:+62274555555" class="d-block">📞 (0274) 555555</a>
          <a href="mailto:info@rsjiwakenangan.co.id" class="d-block">✉️ info@rsjiwakenangan.co.id</a>
        </div>
      </div>
    </div>
  </div>

  <footer>
    © 2025 RS Jiwa Kenangan Yogyakarta. Seluruh hak cipta dilindungi.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
