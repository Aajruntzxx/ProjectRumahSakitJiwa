<?php
include "koneksi.php";
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RS Jiwa Kenangan - Jadwal Dokter</title>
  <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #f7fafc;
      font-family: 'Nunito Sans', sans-serif;
      color: #2d2d2d;
    }

    .navbar {
      background-color: #fff;
      border-bottom: 4px solid #b3e5fc;
      padding: 14px 0;
    }

    .navbar-brand h1 {
      font-size: 26px;
      font-weight: 700;
      color: #c44d3e;
      margin: 0;
    }

    .judul-section {
      background: linear-gradient(135deg, #b2ebf2, #e0f7fa);
      border-radius: 12px;
      width: 90%;
      margin: 30px auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
      padding: 20px;
    }

    .judul-section h1 {
      color: #00796b;
      font-weight: 800;
    }

    th {
      background-color: #b2dfdb;
      text-align: center;
      font-weight: bold;
    }

    td {
      text-align: center;
      vertical-align: middle;
    }

    footer {
      background: #2d3947;
      color: #ccc;
      text-align: center;
      padding: 20px;
      margin-top: 50px;
      font-size: 14px;
    }

    .profil-section {
      background-color: #e0f7fa;
      padding: 40px 0;
      margin-top: 50px;
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card img {
      border-radius: 15px 15px 0 0;
      height: 220px;
      object-fit: cover;
    }

    .card-body {
      text-align: center;
    }

    .card-title {
      font-weight: 700;
      color: #00796b;
    }

    .card-text {
      color: #555;
      font-size: 15px;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="rsj.php">
        <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ=" alt="Logo" width="60" height="60" class="rounded-3 me-2">
        <div>
          <h1>RS Jiwa Kenangan</h1>
          <small>Jl. Kesumayudha No.29, Bangli</small>
        </div>
      </a>
    </div>
  </nav>

  <!-- Judul -->
  <div class="judul-section">
    <h2>JADWAL DOKTER</h2>
    <h1>Jadwal Praktek Dokter RS Jiwa Kenangan</h1>
  </div>

  <!-- Tabel Jadwal -->
  <div class="container mb-5">
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead>
          <tr>
            <th>No</th>
            <th>Poliklinik</th>
            <th>Senin</th>
            <th>Selasa</th>
            <th>Rabu</th>
            <th>Kamis</th>
            <th>Jumat</th>
            <th>Sabtu</th>
            <th>Minggu</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "SELECT * FROM jadwal_dokter";
          $result = mysqli_query($koneksi, $query);

          if (!$result) {
              echo "<tr><td colspan='9' class='text-center text-danger'>Gagal mengambil data: " . mysqli_error($koneksi) . "</td></tr>";
          } elseif (mysqli_num_rows($result) > 0) {
              $no = 1;
              while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td class='fw-semibold'>{$row['poliklinik']}</td>
                          <td>{$row['senin']}</td>
                          <td>{$row['selasa']}</td>
                          <td>{$row['rabu']}</td>
                          <td>{$row['kamis']}</td>
                          <td>{$row['jumat']}</td>
                          <td>{$row['sabtu']}</td>
                          <td>{$row['minggu']}</td>
                        </tr>";
                  $no++;
              }
          } else {
              echo "<tr><td colspan='9' class='text-center'>Tidak ada data jadwal dokter.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PROFIL DOKTER -->
  <section class="profil-section">
    <div class="container">
      <h2 class="text-center mb-4">Profil Dokter Kami</h2>
      <div class="row g-4">
        <!-- Profil 1 -->
        <div class="col-md-4 col-lg-3">
          <div class="card">
            <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="dr. Rina Sari">
            <div class="card-body">
              <h5 class="card-title">dr. Rina Sari, Sp.A</h5>
              <p class="card-text">Spesialis Anak & Remaja<br><b>Praktek:</b> Senin, Selasa, Kamis</p>
            </div>
          </div>
        </div>

        <!-- Profil 2 -->
        <div class="col-md-4 col-lg-3">
          <div class="card">
            <img src="https://randomuser.me/api/portraits/women/66.jpg" alt="dr. Dina Rahma">
            <div class="card-body">
              <h5 class="card-title">dr. Dina Rahma, Sp.KJ</h5>
              <p class="card-text">Spesialis Jiwa<br><b>Praktek:</b> Selasa, Kamis, Jumat</p>
            </div>
          </div>
        </div>

        <!-- Profil 3 -->
        <div class="col-md-4 col-lg-3">
          <div class="card">
            <img src="https://randomuser.me/api/portraits/men/52.jpg" alt="dr. Arif Hidayat">
            <div class="card-body">
              <h5 class="card-title">dr. Arif Hidayat, Sp.KJ</h5>
              <p class="card-text">Spesialis Kesehatan Mental<br><b>Praktek:</b> Senin, Rabu, Kamis</p>
            </div>
          </div>
        </div>

        <!-- Profil 4 -->
        <div class="col-md-4 col-lg-3">
          <div class="card">
            <img src="https://randomuser.me/api/portraits/men/61.jpg" alt="dr. Budi Santoso">
            <div class="card-body">
              <h5 class="card-title">dr. Budi Santoso, Sp.PD</h5>
              <p class="card-text">Spesialis Penyakit Dalam<br><b>Praktek:</b> Senin, Rabu, Kamis</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer>
    &copy; 2025 RS Jiwa Kenangan | Semua Hak Dilindungi
  </footer>

</body>
</html>
