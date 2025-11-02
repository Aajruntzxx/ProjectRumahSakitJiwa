<?php
// Mulai session
session_start();

// Panggil koneksi database
include "koneksi.php"; // Pastikan file ini berisi variabel $koneksi (mysqli_connect)

$pesan_error = "";

// Cek jika formulir login disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil dan sanitasi data input
    $no_rm = mysqli_real_escape_string($koneksi, $_POST['no_rm']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['tgl_lahir']);

    // 2. Query untuk mencocokkan No. RM dan Tanggal Lahir
    // Mencari pasien di tabel 'pasien'
    $sql = "SELECT * FROM pasien WHERE no_rm = '$no_rm' AND tgl_lahir = '$tgl_lahir'";
    $result = mysqli_query($koneksi, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        // Data pasien ditemukan, login berhasil
        $data_pasien = mysqli_fetch_assoc($result);

        // Set session user
        $_SESSION['logged_in'] = true;
        $_SESSION['no_rm'] = $data_pasien['no_rm'];
        $_SESSION['nama_pasien'] = $data_pasien['nama'];

        // Redirect ke halaman dashboard pasien atau pendaftaran antrian online
        header("Location: dashboard_pasien.php"); 
        exit();

    } else {
        // Login gagal
        $pesan_error = "Login gagal. Nomor Rekam Medis atau Tanggal Lahir salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pasien Lama - RS Jiwa Kenangan</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg"
        type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap"
        rel="stylesheet">

    <style>
        /* CSS Disesuaikan untuk Halaman Login */
        body {
            background-color: #f7fafc;
            font-family: 'Nunito Sans', sans-serif;
            color: #2d2d2d;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        .login-container {
            max-width: 450px;
            margin: auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-container h2 {
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
            margin-top: auto; /* Memastikan footer selalu di bawah */
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
            </div>
    </nav>

    <div class="container my-5 flex-grow-1 d-flex">
        <div class="login-container">
            <h2 class="mb-4">Login Pasien Lama</h2>
            <p class="text-center text-muted mb-4">Masukkan Nomor Rekam Medis dan Tanggal Lahir Anda.</p>

            <?php
            // Menampilkan pesan error jika login gagal
            if ($pesan_error) {
                echo '<div class="alert alert-danger text-center" role="alert">' . $pesan_error . '</div>';
            }
            ?>

            <form method="POST" action="pasienlama.php">
                <div class="mb-3">
                    <label for="no_rm" class="form-label fw-semibold">Nomor Rekam Medis (No. RM)</label>
                    <input type="text" class="form-control" id="no_rm" name="no_rm" required 
                           placeholder="Contoh: RM000001">
                </div>
                <div class="mb-4">
                    <label for="tgl_lahir" class="form-label fw-semibold">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-theme">Login</button>
                </div>
                <div class="text-center mt-3">
                    <a href="pasienbaru.php" class="text-decoration-none text-secondary">Belum punya No. RM? Daftar Pasien Baru</a>
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