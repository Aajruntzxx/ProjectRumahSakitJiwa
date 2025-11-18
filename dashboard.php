<?php
session_start();

// Autentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Super Admin');

$today = date('Y-m-d');
$stats = [
    'total_pasien' => 0,
    'pendaftaran_hari_ini' => 0,
    'antrian_menunggu' => 0,
    'antrian_selesai' => 0
];

// 1. Total Pasien Terdaftar
$sql_pasien = "SELECT COUNT(pasien_id) AS total FROM pasien";
$result_pasien = mysqli_query($conn, $sql_pasien);
$stats['total_pasien'] = mysqli_fetch_assoc($result_pasien)['total'];
if ($result_pasien) mysqli_free_result($result_pasien);

// 2. Pendaftaran Hari Ini (Terverifikasi/Menunggu)
$sql_pendaftaran = "SELECT COUNT(pendaftaran_id) AS total 
                    FROM pendaftaran 
                    WHERE DATE(tgl_waktu_input) = '$today' AND status_pendaftaran IN ('Menunggu Verifikasi', 'Terverifikasi')";
$result_pendaftaran = mysqli_query($conn, $sql_pendaftaran);
$stats['pendaftaran_hari_ini'] = mysqli_fetch_assoc($result_pendaftaran)['total'];
if ($result_pendaftaran) mysqli_free_result($result_pendaftaran);

// 3. Antrian Menunggu Hari Ini
$sql_menunggu = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Menunggu'";
$result_menunggu = mysqli_query($conn, $sql_menunggu);
$stats['antrian_menunggu'] = mysqli_fetch_assoc($result_menunggu)['total'];
if ($result_menunggu) mysqli_free_result($result_menunggu);

// 4. Antrian Selesai Hari Ini
$sql_selesai = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Selesai'";
$result_selesai = mysqli_query($conn, $sql_selesai);
$stats['antrian_selesai'] = mysqli_fetch_assoc($result_selesai)['total'];
if ($result_selesai) mysqli_free_result($result_selesai);


// Query untuk Tampilan Antrian per Poli Hari Ini
$sql_antrian_poli = "SELECT 
                        p.nama_poli, 
                        COUNT(a.antrian_id) AS total_antrian,
                        SUM(CASE WHEN a.status_antrian = 'Menunggu' THEN 1 ELSE 0 END) AS menunggu,
                        SUM(CASE WHEN a.status_antrian IN ('Dipanggil', 'Sedang Periksa') THEN 1 ELSE 0 END) AS sedang_dilayani,
                        SUM(CASE WHEN a.status_antrian IN ('Selesai', 'Tidak Hadir') THEN 1 ELSE 0 END) AS selesai_tidak_hadir
                    FROM antrian a
                    JOIN poli p ON a.poli_id = p.poli_id
                    WHERE a.tgl_layanan = '$today'
                    GROUP BY p.nama_poli";
$result_antrian_poli = mysqli_query($conn, $sql_antrian_poli);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .content-wrapper {
            flex: 1;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            color: white;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
        .bg-gradient-blue { background: linear-gradient(45deg, #007bff, #0056b3); }
        .bg-gradient-yellow { background: linear-gradient(45deg, #ffc107, #e0a800); }
        .bg-gradient-red { background: linear-gradient(45deg, #dc3545, #c82333); }
        .bg-gradient-green { background: linear-gradient(45deg, #28a745, #1e7e34); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                RS Jiwa - Dashboard
            </a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, **<?php echo $nama_lengkap_admin; ?>** (<?php echo $role_admin; ?>)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-outline-danger ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="mb-4 text-primary">📊 Dashboard Sistem Antrian</h2>
            <p class="text-muted">Ringkasan Data Hari Ini: **<?php echo date('d F Y', strtotime($today)); ?>**</p>
            
            <div class="row mb-5">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-gradient-blue shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-uppercase small">Total Pasien Terdaftar</h3>
                            <p class="stat-value"><?php echo $stats['total_pasien']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-gradient-yellow shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-uppercase small">Pendaftaran Hari Ini</h3>
                            <p class="stat-value"><?php echo $stats['pendaftaran_hari_ini']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-gradient-red shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-uppercase small">Antrian Menunggu</h3>
                            <p class="stat-value"><?php echo $stats['antrian_menunggu']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-gradient-green shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-uppercase small">Antrian Selesai</h3>
                            <p class="stat-value"><?php echo $stats['antrian_selesai']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            ---

            <div class="card shadow-lg">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Status Antrian per Poli Hari Ini</h5>
                </div>
                <div class="card-body">
                    <?php if ($result_antrian_poli && mysqli_num_rows($result_antrian_poli) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Poli</th>
                                        <th class="text-center">Total Antrian</th>
                                        <th class="text-center">Menunggu</th>
                                        <th class="text-center">Sedang Dilayani</th>
                                        <th class="text-center">Selesai/Tidak Hadir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_total = 0;
                                    $grand_menunggu = 0;
                                    $grand_dilayani = 0;
                                    $grand_selesai_th = 0;
                                    
                                    while($row = mysqli_fetch_assoc($result_antrian_poli)): 
                                        $grand_total += $row['total_antrian'];
                                        $grand_menunggu += $row['menunggu'];
                                        $grand_dilayani += $row['sedang_dilayani'];
                                        $grand_selesai_th += $row['selesai_tidak_hadir'];
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-primary fs-6">**<?php echo htmlspecialchars($row['nama_poli']); ?>**</span></td>
                                            <td class="text-center"><?php echo $row['total_antrian']; ?></td>
                                            <td class="text-center text-danger fw-bold"><?php echo $row['menunggu']; ?></td>
                                            <td class="text-center text-info fw-bold"><?php echo $row['sedang_dilayani']; ?></td>
                                            <td class="text-center text-success"><?php echo $row['selesai_tidak_hadir']; ?></td>
                                        </tr>
                                    <?php endwhile; 
                                    
                                    // Bebaskan hasil query tabel
                                    if ($result_antrian_poli) mysqli_free_result($result_antrian_poli);
                                    ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>TOTAL KESELURUHAN</td>
                                        <td class="text-center"><?php echo $grand_total; ?></td>
                                        <td class="text-center text-danger"><?php echo $grand_menunggu; ?></td>
                                        <td class="text-center text-info"><?php echo $grand_dilayani; ?></td>
                                        <td class="text-center text-success"><?php echo $grand_selesai_th; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            Tidak ada data antrian yang tercatat untuk hari ini.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted">
                    <a href="report.php" class="btn btn-sm btn-outline-primary">Lihat Laporan Detail Pendaftaran &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php mysqli_close($conn); ?>