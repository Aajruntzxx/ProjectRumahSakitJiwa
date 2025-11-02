<?php
include "koneksi.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pasien - RS Jiwa Kenangan</title>
  <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Nunito Sans', sans-serif;
    }

    .navbar {
      background-color: #fff;
      border-bottom: 3px solid #b3e5fc;
    }

    .navbar-brand h1 {
      font-size: 22px;
      color: #c44d3e;
      font-weight: 700;
    }

    .table th {
      background-color: #c44d3e;
      color: #fff;
    }

    .btn-search {
      background-color: #f8b25c;
      color: white;
      font-weight: bold;
    }

    .btn-search:hover {
      background-color: #eaa244;
    }

    footer {
      background-color: #2d3947;
      color: #ccc;
      text-align: center;
      padding: 15px;
      margin-top: 50px;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="rsj.php">
        <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ=" width="50" height="50" class="me-2 rounded">
        <h1>RS Jiwa Kenangan</h1>
      </a>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="text-center text-danger mb-4">Akses Riwayat Pasien</h2>
    <p class="text-center text-muted mb-4">Masukkan NIK pasien untuk melihat riwayat kunjungan, hasil laboratorium, dan radiologi Anda.</p>

    <!-- Form pencarian pasien -->
    <form method="GET" class="d-flex justify-content-center mb-5">
      <input type="text" name="nik" class="form-control w-50 me-2" placeholder="Masukkan NIK pasien..." required>
      <button type="submit" class="btn btn-search">Cari</button>
    </form>

    <?php
    if (isset($_GET['nik'])) {
      $nik = mysqli_real_escape_string($koneksi, $_GET['nik']);

      // Query data pasien
      $pasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE nik='$nik'");
      if (mysqli_num_rows($pasien) > 0) {
        $data_pasien = mysqli_fetch_assoc($pasien);
        echo "<h4 class='mb-3'>Data Pasien</h4>";
        echo "<table class='table table-bordered'>
                <tr><th>Nama Pasien</th><td>{$data_pasien['nama']}</td></tr>
                <tr><th>NIK</th><td>{$data_pasien['nik']}</td></tr>
                <tr><th>Tanggal Lahir</th><td>{$data_pasien['ttl']}</td></tr>
                <tr><th>Alamat</th><td>{$data_pasien['alamat']}</td></tr>
              </table>";

        // Riwayat kunjungan
        echo "<h4 class='mt-5 mb-3'>Riwayat Kunjungan</h4>";
        $riwayat = mysqli_query($koneksi, "SELECT * FROM kunjungan WHERE nik='$nik' ORDER BY tanggal DESC");
        if (mysqli_num_rows($riwayat) > 0) {
          echo "<table class='table table-striped'>
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Poliklinik</th>
                      <th>Dokter</th>
                      <th>Diagnosa</th>
                    </tr>
                  </thead>
                  <tbody>";
          while ($r = mysqli_fetch_assoc($riwayat)) {
            echo "<tr>
                    <td>{$r['tanggal']}</td>
                    <td>{$r['poli']}</td>
                    <td>{$r['dokter']}</td>
                    <td>{$r['diagnosa']}</td>
                  </tr>";
          }
          echo "</tbody></table>";
        } else {
          echo "<div class='alert alert-warning'>Belum ada riwayat kunjungan.</div>";
        }

        // Hasil laboratorium
        echo "<h4 class='mt-5 mb-3'>Hasil Laboratorium</h4>";
        $lab = mysqli_query($koneksi, "SELECT * FROM hasil_lab WHERE nik='$nik' ORDER BY tanggal DESC");
        if (mysqli_num_rows($lab) > 0) {
          echo "<table class='table table-striped'>
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Jenis Pemeriksaan</th>
                      <th>Hasil</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>";
          while ($l = mysqli_fetch_assoc($lab)) {
            echo "<tr>
                    <td>{$l['tanggal']}</td>
                    <td>{$l['jenis_pemeriksaan']}</td>
                    <td>{$l['hasil']}</td>
                    <td>{$l['keterangan']}</td>
                  </tr>";
          }
          echo "</tbody></table>";
        } else {
          echo "<div class='alert alert-warning'>Belum ada hasil laboratorium.</div>";
        }

        // Hasil radiologi
        echo "<h4 class='mt-5 mb-3'>Hasil Radiologi</h4>";
        $radio = mysqli_query($koneksi, "SELECT * FROM hasil_radiologi WHERE nik='$nik' ORDER BY tanggal DESC");
        if (mysqli_num_rows($radio) > 0) {
          echo "<table class='table table-striped'>
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Jenis Pemeriksaan</th>
                      <th>Kesimpulan</th>
                    </tr>
                  </thead>
                  <tbody>";
          while ($rd = mysqli_fetch_assoc($radio)) {
            echo "<tr>
                    <td>{$rd['tanggal']}</td>
                    <td>{$rd['jenis_pemeriksaan']}</td>
                    <td>{$rd['kesimpulan']}</td>
                  </tr>";
          }
          echo "</tbody></table>";
        } else {
          echo "<div class='alert alert-warning'>Belum ada hasil radiologi.</div>";
        }
      } else {
        echo "<div class='alert alert-danger text-center w-75 mx-auto'>Data pasien dengan NIK <strong>$nik</strong> tidak ditemukan.</div>";
      }
    }
    ?>
  </div>

  <footer>
    © 2025 RS Jiwa Kenangan Yogyakarta. Seluruh hak cipta dilindungi.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
