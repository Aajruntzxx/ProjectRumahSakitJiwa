<?php
// Mulai session
session_start();

// Panggil koneksi database
include "koneksi.php"; // Pastikan file ini berisi variabel $koneksi (mysqli_connect)

$pesan_sukses = "";
$pesan_error = "";

// Cek jika formulir disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil dan sanitasi data dari formulir
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['tgl_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $nik = mysqli_real_escape_string($koneksi, $_POST['nik']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $poli_tujuan = mysqli_real_escape_string($koneksi, $_POST['poli_tujuan']);
    $keluhan_awal = mysqli_real_escape_string($koneksi, $_POST['keluhan_awal']);

    // 2. Logika Sederhana untuk mendapatkan Nomor Rekam Medis (RM) baru
    // ***CATATAN: Dalam sistem nyata, generate RM harus lebih kompleks dan unik!***
    $query_count = "SELECT COUNT(*) AS total FROM pasien";
    $result_count = mysqli_query($koneksi, $query_count);
    $data_count = mysqli_fetch_assoc($result_count);
    $new_rm_number = str_pad($data_count['total'] + 1, 6, '0', STR_PAD_LEFT);
    $rm_baru = "RM" . $new_rm_number;

    // 3. Query INSERT data ke tabel 'pasien'
    // ***CATATAN: Anda perlu membuat tabel 'pasien' terlebih dahulu di database Anda!***
    $sql = "INSERT INTO pasien (
                no_rm, nama, tgl_lahir, jenis_kelamin, nik, alamat, no_hp, poli_tujuan, keluhan_awal, tgl_daftar
            ) VALUES (
                '$rm_baru', '$nama', '$tgl_lahir', '$jenis_kelamin', '$nik', '$alamat', '$no_hp', '$poli_tujuan', '$keluhan_awal', NOW()
            )";

    if (mysqli_query($koneksi, $sql)) {
        $pesan_sukses = "Pendaftaran Pasien Baru Berhasil! Nomor Rekam Medis Anda adalah: <b>$rm_baru</b>. Mohon catat nomor ini untuk login dan kunjungan berikutnya.";
    } else {
        $pesan_error = "Error saat pendaftaran: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Pasien Baru - RS Jiwa Kenangan</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg"
        type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .form-container h2 {
            color: #009999;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
        }

        .btn-theme {
            background-color: #009999;
            color: #fff;
            border-radius: 25px;
            padding: 10px 30px;
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

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="rsj.php">
                <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ="
                    alt="Logo RS Jiwa Kenangan">
                <div>
                    <h1 class="mb-0">RS Jiwa Kenangan</h1>
                    <small>Jl. Kesumayudha No.29, Bangli</small>
                </div>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav fw-semibold">
                    <li class="nav-item"><a class="nav-link" href="rsj.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="jadwaldokter.php">Jadwal Dokter</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pendaftaran.php">Pendaftaran</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="form-container">
            <h2>Formulir Pendaftaran Pasien Baru</h2>
            <p class="text-center text-muted mb-4">Mohon isi data diri Anda dengan lengkap dan benar.</p>

            <?php
            // Menampilkan pesan sukses atau error setelah submit
            if ($pesan_sukses) {
                echo '<div class="alert alert-success text-center" role="alert">' . $pesan_sukses . '</div>';
            }
            if ($pesan_error) {
                echo '<div class="alert alert-danger text-center" role="alert">' . $pesan_error . '</div>';
            }
            ?>

            <form method="POST" action="pasienbaru.php">
                <h4 class="mb-3 text-secondary">Data Diri Pasien</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nik" class="form-label">Nomor Induk Kependudukan (NIK)</label>
                        <input type="text" class="form-control" id="nik" name="nik" maxlength="16" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_lahir" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" required>
                    </div>
                    <div class="col-md-6">
                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="no_hp" class="form-label">Nomor Handphone Aktif</label>
                        <input type="tel" class="form-control" id="no_hp" name="no_hp" required>
                    </div>
                    <div class="col-12">
                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="2" required></textarea>
                    </div>
                </div>

                <h4 class="mt-4 mb-3 text-secondary border-top pt-3">Informasi Kunjungan</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="poli_tujuan" class="form-label">Poli Tujuan</label>
                        <select id="poli_tujuan" name="poli_tujuan" class="form-select" required>
                            <option value="">Pilih Poliklinik...</option>
                            <option value="Poli Jiwa Dewasa">Poli Jiwa Dewasa</option>
                            <option value="Poli Kesehatan Anak & Remaja">Poli Kesehatan Anak & Remaja</option>
                            <option value="Poli Geriatri (Lansia)">Poli Geriatri (Lansia)</option>
                            <option value="Poli Psikoterapi & Konsultasi">Poli Psikoterapi & Konsultasi</option>
                            <option value="Poli Gangguan Tidur & Cemas">Poli Gangguan Tidur & Cemas</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="keluhan_awal" class="form-label">Keluhan atau Gejala Awal Singkat</label>
                        <textarea class="form-control" id="keluhan_awal" name="keluhan_awal" rows="3"
                            placeholder="Contoh: Sulit tidur, sering cemas berlebihan, atau murung selama 1 bulan terakhir." required></textarea>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-theme">Daftar & Dapatkan No. RM</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p class="mb-0">© 2025 RS Jiwa Kenangan — Semua Hak Dilindungi</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>