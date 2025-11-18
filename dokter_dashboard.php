<?php
session_start();

// --- KONEKSI DATABASE ---
include "koneksi.php"; // Menggunakan file koneksi eksternal untuk mendapatkan variabel $conn
// -----------------------

// 1. Cek Otentikasi dan Role
if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'Dokter') {
    // Tampilan Akses Ditolak
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p><a href="login.php" class="btn btn-primary">Halaman Login</a></div></body></html>';
    mysqli_close($conn);
    exit();
}

// Definisikan variabel sesi dan login
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Dokter');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Dokter');
$admin_id_login = $_SESSION['admin_id'];
$today = date('Y-m-d');
$hari_ini = date('N'); // 1 (Senin) hingga 7 (Minggu)
$hari_map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hari_praktik = $hari_map[$hari_ini];


$dokter_info = [];
$dokter_id_login = 0;
$poli_ids_hari_ini = [];
$poli_layanan_info = "";
$stats = ['selesai_hari_ini' => 0, 'total_diagnosa' => 0];
$error_message = "";

// 2. Dapatkan Dokter ID, Nama, dan Daftar Poli/Jadwal Hari Ini
$sql_dokter_info = "SELECT 
                        d.dokter_id, 
                        d.nama_lengkap, 
                        GROUP_CONCAT(jp.poli_id) AS poli_ids, 
                        GROUP_CONCAT(p.nama_poli SEPARATOR ', ') AS poli_names
                    FROM dokter d
                    LEFT JOIN jadwal_praktik jp ON d.dokter_id = jp.dokter_id AND jp.hari_praktik = '$hari_praktik'
                    LEFT JOIN poli p ON jp.poli_id = p.poli_id
                    WHERE d.admin_id = $admin_id_login
                    GROUP BY d.dokter_id";

$result_dokter_info = mysqli_query($conn, $sql_dokter_info);

if ($result_dokter_info && mysqli_num_rows($result_dokter_info) == 1) {
    $dokter_info = mysqli_fetch_assoc($result_dokter_info);
    $dokter_id_login = $dokter_info['dokter_id'];
    
    if ($dokter_info['poli_ids']) {
        $poli_ids_hari_ini = explode(',', $dokter_info['poli_ids']);
        $poli_layanan_info = $dokter_info['poli_names'];
    } else {
        $poli_layanan_info = "Tidak ada jadwal hari ini.";
    }

} else {
    $error_message = "Error: Data Dokter tidak ditemukan atau belum terhubung dengan akun.";
}

// 3. Ambil Statistik Layanan Medis Dokter Hari Ini
if ($dokter_id_login > 0) {
    // Total Pasien Selesai Hari Ini
    $sql_selesai = "SELECT COUNT(lm.layanan_id) AS total_selesai
                    FROM layanan_medis lm
                    WHERE lm.dokter_id = $dokter_id_login AND DATE(lm.tgl_waktu_layanan) = '$today'";
    $result_selesai = mysqli_query($conn, $sql_selesai);
    $stats['selesai_hari_ini'] = mysqli_fetch_assoc($result_selesai)['total_selesai'] ?? 0;
    if ($result_selesai) mysqli_free_result($result_selesai);

    // Total Diagnosa Dilakukan (keseluruhan)
    $sql_total_diagnosa = "SELECT COUNT(lm.layanan_id) AS total_diagnosa
                            FROM layanan_medis lm
                            WHERE lm.dokter_id = $dokter_id_login";
    $result_total_diagnosa = mysqli_query($conn, $sql_total_diagnosa);
    $stats['total_diagnosa'] = mysqli_fetch_assoc($result_total_diagnosa)['total_diagnosa'] ?? 0;
    if ($result_total_diagnosa) mysqli_free_result($result_total_diagnosa);
}


// 4. Query Antrian Aktif di Poli Dokter Hari Ini
$antrian_aktif = [];
if (!empty($poli_ids_hari_ini)) {
    $poli_id_string = implode(',', $poli_ids_hari_ini);
    $sql_antrian_aktif = "SELECT 
                            a.antrian_id, a.nomor_antrian, a.status_antrian, a.waktu_dipanggil,
                            s.nama_lengkap AS nama_pasien, o.nama_poli
                          FROM antrian a
                          JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                          JOIN pasien s ON pf.pasien_id = s.pasien_id
                          JOIN poli o ON a.poli_id = o.poli_id
                          WHERE a.tgl_layanan = '$today' 
                          AND a.poli_id IN ($poli_id_string)
                          AND a.status_antrian IN ('Menunggu', 'Dipanggil', 'Sedang Periksa')
                          ORDER BY FIELD(a.status_antrian, 'Sedang Periksa', 'Dipanggil', 'Menunggu'), a.antrian_id ASC";
    
    $result_antrian_aktif = mysqli_query($conn, $sql_antrian_aktif);
    if ($result_antrian_aktif) {
        while($row = mysqli_fetch_assoc($result_antrian_aktif)) {
            $antrian_aktif[] = $row;
        }
        mysqli_free_result($result_antrian_aktif);
    }
}

// Tutup koneksi sebelum output HTML
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dokter | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            padding-top: 30px;
            padding-bottom: 30px;
        }
        .header-section {
            background: linear-gradient(135deg, #198754, #155724); /* Hijau Tua/Gelap */
            color: white;
            padding: 20px 0;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(21, 126, 44, 0.3);
        }
        .stat-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .antrian-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .antrian-row:hover {
            background-color: #e9ecef;
        }
        .status-Dipanggil { background-color: #ffc107; color: #343a40; font-weight: bold; }
        .status-SedangPeriksa { background-color: #198754; color: white; font-weight: bold; }
        .status-Menunggu { background-color: #e2e6ea; }
        .status-SedangPeriksa td, .status-SedangPeriksa a { color: white !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dokter_dashboard.php">
                <i class="bi bi-heart-pulse-fill me-2 text-success"></i> **Dokter Panel**
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDO" aria-controls="navbarNavDO" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDO">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="dokter_dashboard.php">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="antrian_call.php">
                            <i class="bi bi-telephone me-1"></i> Monitor Antrian
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, **<?php echo $dokter_info['nama_lengkap'] ?? $nama_lengkap_admin; ?>** (<?php echo $role_admin; ?>)</span>
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
            
            <div class="header-section text-center">
                <h1 class="mb-2">👨‍⚕️ Dashboard Dokter</h1>
                <p class="lead mb-0">Jadwal Anda: **<?php echo $hari_praktik; ?>** | Poli: **<?php echo $poli_layanan_info; ?>**</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger mb-4"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <h4 class="mb-3 text-dark fw-bold">Kinerja Hari Ini (<?php echo date('d F Y', strtotime($today)); ?>)</h4>
            <div class="row mb-5">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card stat-card bg-success text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Pasien Selesai Hari Ini</h5>
                            <p class="stat-value mb-0"><?php echo $stats['selesai_hari_ini']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card stat-card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Total Layanan Tercatat</h5>
                            <p class="stat-value mb-0"><?php echo $stats['total_diagnosa']; ?></p>
                        </div>
                    </div>
                </div>
                 <div class="col-lg-4 col-md-12 mb-3">
                    <div class="card stat-card bg-info text-dark shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Antrian Aktif Di Poli Anda</h5>
                            <p class="stat-value mb-0">
                                <?php echo count($antrian_aktif); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <h3 class="mb-3 text-dark fw-bold">Daftar Antrian Aktif Kerja Anda</h3>
            <p class="text-muted">Antrian di Poli Anda dengan status Menunggu, Dipanggil, atau Sedang Periksa.</p>

            <?php if (empty($poli_ids_hari_ini)): ?>
                <div class="alert alert-warning">Anda tidak memiliki jadwal praktik di poli manapun pada hari **<?php echo $hari_praktik; ?>**.</div>
            <?php elseif (empty($antrian_aktif)): ?>
                 <div class="alert alert-info">Tidak ada antrian aktif (Menunggu/Dipanggil/Sedang Periksa) di Poli Anda hari ini.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover shadow-sm">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Prioritas</th>
                                <th>No. Antrian</th>
                                <th>Nama Pasien</th>
                                <th>Poli</th>
                                <th>Status</th>
                                <th>Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prioritas = 1;
                            foreach ($antrian_aktif as $antrian): 
                                $status_class = str_replace(' ', '', $antrian['status_antrian']);
                                // Aksi Cepat SELALU mengarahkan ke halaman Monitor Antrian (antrian_call.php)
                                $link_call = "antrian_call.php"; 
                                $aksi_text = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'Lanjut Layanan' : 'Panggil/Kelola';
                                $aksi_icon = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'bi-arrow-right-circle' : 'bi-telephone-fill';
                                $aksi_color = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'danger' : 'primary';
                            ?>
                            <tr class="antrian-row status-<?php echo $status_class; ?>" onclick="window.location='<?php echo $link_call; ?>'">
                                <td><?php echo $prioritas++; ?></td>
                                <td><h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($antrian['nomor_antrian']); ?></h5></td>
                                <td><?php echo htmlspecialchars($antrian['nama_pasien']); ?></td>
                                <td><?php echo htmlspecialchars($antrian['nama_poli']); ?></td>
                                <td><span class="badge bg-<?php echo ($antrian['status_antrian'] == 'Sedang Periksa') ? 'danger' : (($antrian['status_antrian'] == 'Dipanggil') ? 'warning' : 'secondary'); ?>"><?php echo $antrian['status_antrian']; ?></span></td>
                                <td>
                                    <a href="<?php echo $link_call; ?>" class="btn btn-sm btn-<?php echo $aksi_color; ?>">
                                        <i class="bi <?php echo $aksi_icon; ?>"></i> <?php echo $aksi_text; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-end mt-3"><a href="antrian_call.php" class="btn btn-sm btn-outline-dark">Lihat Semua Antrian & Kelola Panggilan</a></p>
            <?php endif; ?>
            
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>