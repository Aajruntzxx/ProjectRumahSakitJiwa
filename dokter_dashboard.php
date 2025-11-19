<?php
session_start();

// --- KONEKSI DATABASE ---
// Asumsi file koneksi.php menyediakan $conn
include "koneksi.php"; 
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
                    LEFT JOIN jadwal_praktik jp ON d.dokter_id = jp.dokter_id AND jp.hari_praktik = ?
                    LEFT JOIN poli p ON jp.poli_id = p.poli_id
                    WHERE d.admin_id = ?
                    GROUP BY d.dokter_id";

if ($stmt = mysqli_prepare($conn, $sql_dokter_info)) {
    mysqli_stmt_bind_param($stmt, "si", $hari_praktik, $admin_id_login);
    mysqli_stmt_execute($stmt);
    $result_dokter_info = mysqli_stmt_get_result($stmt);

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
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Database error: " . mysqli_error($conn);
}


// 3. Ambil Statistik Layanan Medis Dokter Hari Ini
if ($dokter_id_login > 0) {
    // Total Pasien Selesai Hari Ini
    $sql_selesai = "SELECT COUNT(lm.layanan_id) AS total_selesai
                    FROM layanan_medis lm
                    WHERE lm.dokter_id = ? AND DATE(lm.tgl_waktu_layanan) = ?";
    if ($stmt = mysqli_prepare($conn, $sql_selesai)) {
        mysqli_stmt_bind_param($stmt, "is", $dokter_id_login, $today);
        mysqli_stmt_execute($stmt);
        $result_selesai = mysqli_stmt_get_result($stmt);
        $stats['selesai_hari_ini'] = mysqli_fetch_assoc($result_selesai)['total_selesai'] ?? 0;
        mysqli_stmt_close($stmt);
    }

    // Total Diagnosa Dilakukan (keseluruhan)
    $sql_total_diagnosa = "SELECT COUNT(lm.layanan_id) AS total_diagnosa
                            FROM layanan_medis lm
                            WHERE lm.dokter_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql_total_diagnosa)) {
        mysqli_stmt_bind_param($stmt, "i", $dokter_id_login);
        mysqli_stmt_execute($stmt);
        $result_total_diagnosa = mysqli_stmt_get_result($stmt);
        $stats['total_diagnosa'] = mysqli_fetch_assoc($result_total_diagnosa)['total_diagnosa'] ?? 0;
        mysqli_stmt_close($stmt);
    }
}


// 4. Query Antrian Aktif di Poli Dokter Hari Ini
$antrian_aktif = [];
if (!empty($poli_ids_hari_ini)) {
    // Gunakan placeholder (?) untuk setiap ID
    $placeholders = implode(',', array_fill(0, count($poli_ids_hari_ini), '?'));
    $types = str_repeat('i', count($poli_ids_hari_ini)); // Semua ID adalah integer
    
    // Perlu menambahkan today ($s)
    $types = 's' . $types;
    // Persiapan array untuk bind_param
    $bind_params = array_merge([$types, $today], $poli_ids_hari_ini);


    $sql_antrian_aktif = "SELECT 
                            a.antrian_id, a.nomor_antrian, a.status_antrian, a.waktu_dipanggil,
                            s.nama_lengkap AS nama_pasien, o.nama_poli
                            FROM antrian a
                            JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                            JOIN pasien s ON pf.pasien_id = s.pasien_id
                            JOIN poli o ON a.poli_id = o.poli_id
                            WHERE a.tgl_layanan = ? 
                            AND a.poli_id IN ($placeholders)
                            AND a.status_antrian IN ('Menunggu', 'Dipanggil', 'Sedang Periksa')
                            ORDER BY FIELD(a.status_antrian, 'Sedang Periksa', 'Dipanggil', 'Menunggu'), a.antrian_id ASC";
    
    if ($stmt = mysqli_prepare($conn, $sql_antrian_aktif)) {
        // Binding parameters dynamically
        $ref_array = [];
        // Perlu memecah $bind_params untuk referensi karena call_user_func_array
        foreach ($bind_params as $key => $value) {
            $ref_array[$key] = &$bind_params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $ref_array));
        
        mysqli_stmt_execute($stmt);
        $result_antrian_aktif = mysqli_stmt_get_result($stmt);
        
        if ($result_antrian_aktif) {
            while($row = mysqli_fetch_assoc($result_antrian_aktif)) {
                $antrian_aktif[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Menu items untuk Sidebar
$menu_items = [
    [
        'title' => 'Home/Dashboard',
        'icon' => 'bi-house-door-fill',
        'link' => 'dokter_dashboard.php',
        'active' => true 
    ],
    [
        'title' => 'Monitor Antrian',
        'icon' => 'bi-telephone-fill',
        'link' => 'antrian_call.php',
        'active' => false
    ],
    [
        'title' => 'Rekam Medis Pasien',
        'icon' => 'bi-journal-medical',
        'link' => 'rekam_medis.php', // Asumsi ada halaman ini
        'active' => false
    ],
];


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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Font dan Warna Modern */
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #1f2a38; /* Warna gelap yang sedikit lebih biru */
            --sidebar-color: #f8f9fa;
            --primary-highlight: #00bcd4; /* Teal/Cyan modern */
            --main-font: 'Poppins', sans-serif; 
            --heading-font: 'Montserrat', sans-serif;
            --navbar-bg: #1f2a38;
        }
        body {
            font-family: var(--main-font); 
            background-color: #f0f2f5; 
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
        }

        /* --- Layout Wrapper (Sidebar + Content) --- */
        #wrapper {
            display: flex;
        }
        
        /* Gaya Sidebar */
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: calc(var(--sidebar-width) * -1);
            transition: margin 0.25s ease-out;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            position: fixed;
            z-index: 1030;
            display: flex; /* Untuk menempatkan logout di bawah */
            flex-direction: column;
        }
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        
        #page-content-wrapper {
            width: 100%;
            padding: 0; /* Padding di dalam container-fluid */
            transition: margin-left 0.25s ease-out;
        }

        /* Terapkan hanya pada layar besar */
        @media (min-width: 992px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                margin-left: var(--sidebar-width);
                padding-left: 0; /* Padding di dalam container-fluid */
            }
            #wrapper.toggled #sidebar-wrapper {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            #wrapper.toggled #page-content-wrapper {
                margin-left: 0;
            }
        }

        /* Gaya Navigasi Sidebar */
        .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1.3rem;
            color: var(--primary-highlight);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 700;
        }
        .list-group-item {
            border: none;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            padding: 0.8rem 1.25rem;
            transition: background-color 0.2s, color 0.2s;
            font-weight: 500;
        }
        .list-group-item:hover {
            background-color: #2c3a50; 
            color: white;
        }
        .list-group-item.active-menu {
            background-color: #2c3a50; 
            color: var(--primary-highlight);
            border-left: 5px solid var(--primary-highlight);
            font-weight: 600;
        }
        .sidebar-logout {
             margin-top: auto; /* Mendorong ke bawah */
             padding: 1rem;
             border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* --- End Sidebar Styles --- */


        /* Navbar Top (hanya tombol toggle dan info user) */
        .navbar-top {
            background-color: white !important;
            box-shadow: 0 4px 10px rgba(0,0,0,.05);
            z-index: 1020;
            border-bottom: 3px solid #28a745; /* Garis hijau untuk dokter */
            position: sticky;
            top: 0;
        }

        /* Header Section Update */
        .header-section {
            background: linear-gradient(45deg, #28a745, #198754); /* Gradient Hijau/Success */
            color: white;
            padding: 40px;
            border-radius: 15px; 
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        .header-section h1 {
            font-weight: 700;
        }

        /* Statistik Card Update */
        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08); 
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            background-color: white !important;
            border-left: 5px solid; 
            height: 100%;
        }
        .stat-card:hover {
             transform: translateY(-3px);
             box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        /* Warna Aksen */
        .stat-card.bg-success { border-left-color: #28a745; }
        .stat-card.bg-primary { border-left-color: #0d6efd; }
        .stat-card.bg-info { border-left-color: #0dcaf0; }

        .stat-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
        }
        .stat-value {
            font-size: 2.8rem; 
            font-family: var(--heading-font);
            font-weight: 700;
            line-height: 1.1;
            color: #343a40;
        }
        
        /* Table Antrian Update */
        .table-antrian-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
        }
        .table-antrian-card table {
            margin-bottom: 0;
        }
        .table-antrian-card thead {
            background-color: var(--navbar-bg); 
            color: white;
        }
        .antrian-row {
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
            font-weight: 500;
        }
        .antrian-row:hover {
            background-color: #e9ecef !important;
            transform: scale(1.005);
        }
        .status-Dipanggil { background-color: #fffaeb !important; } 
        .status-SedangPeriksa { background-color: #dc3545 !important; color: white; font-weight: bold; } 
        .status-Menunggu { background-color: white !important; }
        
        .status-SedangPeriksa td, .status-SedangPeriksa a { color: white !important; }
        .status-SedangPeriksa .badge { background-color: white !important; color: #dc3545 !important; }

        .antrian-row td h5 {
            font-family: var(--heading-font);
            color: #343a40;
        }
        .status-SedangPeriksa td h5 {
            color: white;
        }
        .btn-aksi-cepat {
            border-radius: 50px; 
            font-weight: 600;
        }
        /* Footer */
        .footer {
            background-color: #fff !important;
            border-top: 1px solid #e9ecef !important;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div id="sidebar-wrapper">
        <div class="sidebar-heading text-center">
            <i class="bi bi-heart-pulse-fill me-2 text-success"></i> Dokter Panel
        </div>
        <div class="list-group list-group-flush">
            <?php 
            $current_file = basename($_SERVER['PHP_SELF']);

            foreach ($menu_items as $item): 
                $is_active = ($item['link'] == $current_file) || (isset($item['active']) && $item['active'] && $current_file == 'dokter_dashboard.php');
                $active_class = $is_active ? 'active-menu' : '';
            ?>
                <a href="<?php echo $item['link']; ?>" 
                   class="list-group-item list-group-item-action <?php echo $active_class; ?>">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-logout">
             <a class="btn btn-outline-warning w-100 fw-bold" href="logout.php">
                 <i class="bi bi-box-arrow-right me-2"></i> Logout
             </a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light navbar-top py-2">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary" id="sidebarToggle">
                    <i class="bi bi-list"></i> Menu
                </button>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-dark me-3 d-none d-md-inline small">
                        Halo, <b class="fw-bolder"><?php echo $dokter_info['nama_lengkap'] ?? $nama_lengkap_admin; ?></b> (<span class="text-success"><?php echo $role_admin; ?></span>)
                    </span>
                    <a class="btn btn-sm btn-danger d-md-none" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </nav>
        <div class="container-fluid py-4">
            
            <div class="header-section text-center">
                <h1 class="mb-2">👨‍⚕️ Dashboard Dokter</h1>
                <p class="lead mb-0 fw-light">Jadwal Anda: **<?php echo $hari_praktik; ?>** | Poli: **<?php echo $poli_layanan_info; ?>**</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger mb-4"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <h4 class="mb-4 text-dark fw-bold">🚀 Kinerja Hari Ini (<?php echo date('d F Y', strtotime($today)); ?>)</h4>
            <div class="row mb-5">
                <div class="col-xl-4 col-lg-4 col-md-6 mb-4">
                    <div class="card stat-card bg-success text-dark shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="stat-title mb-1">Pasien Selesai Hari Ini</p>
                                    <p class="stat-value mb-0 text-success"><?php echo $stats['selesai_hari_ini']; ?></p>
                                </div>
                                <i class="bi bi-check-circle-fill fs-1 text-success opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 mb-4">
                    <div class="card stat-card bg-primary text-dark shadow-sm">
                        <div class="card-body">
                             <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="stat-title mb-1">Total Layanan Tercatat</p>
                                    <p class="stat-value mb-0 text-primary"><?php echo $stats['total_diagnosa']; ?></p>
                                </div>
                                <i class="bi bi-journal-medical fs-1 text-primary opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-12 mb-4">
                    <div class="card stat-card bg-info text-dark shadow-sm">
                        <div class="card-body">
                             <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="stat-title mb-1">Antrian Aktif Di Poli Anda</p>
                                    <p class="stat-value mb-0 text-info">
                                        <?php echo count($antrian_aktif); ?>
                                    </p>
                                </div>
                                <i class="bi bi-clock-fill fs-1 text-info opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>

            <h3 class="mb-3 text-dark fw-bold">📋 Daftar Antrian Aktif Kerja Anda</h3>
            <p class="text-muted">Antrian di Poli Anda dengan status Menunggu, Dipanggil, atau Sedang Periksa.</p>

            <?php if (empty($poli_ids_hari_ini)): ?>
                <div class="alert alert-warning">Anda tidak memiliki jadwal praktik di poli manapun pada hari **<?php echo $hari_praktik; ?>**.</div>
            <?php elseif (empty($antrian_aktif)): ?>
                <div class="alert alert-info">Tidak ada antrian aktif (Menunggu/Dipanggil/Sedang Periksa) di Poli Anda hari ini.</div>
            <?php else: ?>
                <div class="table-antrian-card">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
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
                                    $link_call = "antrian_call.php?antrian_id=" . $antrian['antrian_id']; // Mengarahkan ke halaman call dengan ID spesifik
                                    $aksi_text = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'Lanjut Layanan' : 'Panggil/Kelola';
                                    $aksi_icon = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'bi-arrow-right-circle' : 'bi-telephone-fill';
                                    $aksi_color = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'danger' : 'primary';
                                ?>
                                <tr class="antrian-row status-<?php echo $status_class; ?>">
                                    <td><?php echo $prioritas++; ?></td>
                                    <td><h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($antrian['nomor_antrian']); ?></h5></td>
                                    <td><?php echo htmlspecialchars($antrian['nama_pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($antrian['nama_poli']); ?></td>
                                    <td><span class="badge bg-<?php echo ($antrian['status_antrian'] == 'Sedang Periksa') ? 'danger' : (($antrian['status_antrian'] == 'Dipanggil') ? 'warning' : 'secondary'); ?>"><?php echo $antrian['status_antrian']; ?></span></td>
                                    <td>
                                        <a href="<?php echo $link_call; ?>" class="btn btn-sm btn-<?php echo $aksi_color; ?> btn-aksi-cepat">
                                            <i class="bi <?php echo $aksi_icon; ?>"></i> <?php echo $aksi_text; ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="text-end mt-3"><a href="antrian_call.php" class="btn btn-sm btn-outline-dark fw-bold">Lihat Semua Antrian & Kelola Panggilan <i class="bi bi-arrow-right"></i></a></p>
            <?php endif; ?>
            
        </div>
        
        <footer class="footer mt-auto py-3">
            <div class="container-fluid text-center">
                <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
            </div>
        </footer>
        
    </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle sidebar script
    document.addEventListener('DOMContentLoaded', function() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var wrapper = document.getElementById('wrapper');

        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
        });

        // Logika untuk memastikan sidebar tertutup di ponsel secara default, 
        // dan terbuka di desktop.
        function checkViewport() {
            if (window.innerWidth < 992) {
                // Di bawah desktop, sidebar tersembunyi
                wrapper.classList.add('toggled');
            } else {
                // Di desktop, sidebar ditampilkan
                wrapper.classList.remove('toggled');
            }
        }

        window.addEventListener('resize', checkViewport);
        checkViewport(); // Panggil saat pemuatan
    });
</script>
</body>
</html>