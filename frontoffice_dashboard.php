<?php
session_start();

// Cek Otentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Batasi akses hanya untuk Front Office dan Super Admin
$allowed_roles = ['Super Admin', 'Front Office'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect atau tampilkan pesan error jika peran tidak diizinkan
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p><a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard Utama</a></div></body></html>';
    exit();
}

include "koneksi.php"; // Pastikan file koneksi ada

$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas FO');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Front Office');
$today = date('Y-m-d');

// --- LOGIKA MENGAMBIL STATISTIK ---
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

// 2. Pendaftaran Hari Ini
$sql_pendaftaran = "SELECT COUNT(pendaftaran_id) AS total 
                    FROM pendaftaran 
                    WHERE DATE(tgl_waktu_input) = '$today' AND status_pendaftaran IN ('Menunggu Verifikasi', 'Terverifikasi')";
$result_pendaftaran = mysqli_query($conn, $sql_pendaftaran);
$stats['pendaftaran_hari_ini'] = mysqli_fetch_assoc($result_pendaftaran)['total'];
if ($result_pendaftaran) mysqli_free_result($result_pendaftaran);

// 3. Antrian Menunggu Hari Ini (Status 'Menunggu')
$sql_menunggu = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Menunggu'";
$result_menunggu = mysqli_query($conn, $sql_menunggu);
$stats['antrian_menunggu'] = mysqli_fetch_assoc($result_menunggu)['total'];
if ($result_menunggu) mysqli_free_result($result_menunggu);

// 4. Antrian Selesai Hari Ini
$sql_selesai = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Selesai'";
$result_selesai = mysqli_query($conn, $sql_selesai);
$stats['antrian_selesai'] = mysqli_fetch_assoc($result_selesai)['total'];
if ($result_selesai) mysqli_free_result($result_selesai);

// --- END LOGIKA STATISTIK ---


// Daftar menu utama Front Office
$menu_items = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-house-door-fill',
        'link' => 'frontoffice_dashboard.php',
        'target' => '_self',
        'active' => true // Tambahkan penanda aktif untuk Dashboard
    ],
    [
        'title' => 'Daftar Pasien',
        'desc' => 'Kelola data registrasi pasien.',
        'icon' => 'bi-people-fill',
        'color' => 'primary',
        'link' => 'pasien_list.php',
        'target' => '_self'
    ],
    [
        'title' => 'Manajemen Pendaftaran',
        'desc' => 'Input/Verifikasi pendaftaran.',
        'icon' => 'bi-file-earmark-spreadsheet-fill',
        'color' => 'info',
        'link' => 'pendaftaran_list.php',
        'target' => '_self'
    ],
    [
        'title' => 'Pemanggilan Antrian',
        'desc' => 'Panggil antrian pasien.',
        'icon' => 'bi-telephone-fill',
        'color' => 'success',
        'link' => 'antrian_call.php',
        'target' => '_self'
    ],
    [
        'title' => 'Layar Antrian Publik',
        'desc' => 'Tampilkan status antrian (Tab Baru).',
        'icon' => 'bi-tv-fill',
        'color' => 'warning',
        'link' => 'antrian_display.php', 
        'target' => '_blank'
    ],
    [
        'title' => 'Laporan Pendaftaran',
        'desc' => 'Lihat rekapitulasi data.',
        'icon' => 'bi-bar-chart-fill',
        'color' => 'secondary',
        'link' => 'report.php',
        'target' => '_self'
    ],
];

// Pisahkan Layar Antrian Publik agar ditaruh di tempat yang menonjol di konten utama
$public_display_item = null;
$internal_menu_items_dashboard = [];

foreach ($menu_items as $item) {
    if (isset($item['desc'])) { // Hanya item dengan 'desc' yang ditampilkan di card dashboard
        if ($item['link'] === 'antrian_display.php') {
            $public_display_item = $item;
        } else {
            $internal_menu_items_dashboard[] = $item;
        }
    }
}


// Tutup koneksi setelah selesai mengambil data
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Front Office | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #1f2a38; /* Warna gelap yang sedikit lebih biru */
            --sidebar-color: #f8f9fa;
            --primary-highlight: #00bcd4; /* Warna highlight teal/cyan modern */
            --main-font: 'Poppins', sans-serif; /* Font utama */
            --heading-font: 'Montserrat', sans-serif; /* Font untuk judul */
        }
        body {
            overflow-x: hidden;
            background-color: #f0f2f5; /* Latar belakang yang lebih modern */
            font-family: var(--main-font); 
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
        }
        
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
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        
        #page-content-wrapper {
            width: 100%;
            padding: 20px;
            padding-left: 20px;
            transition: margin-left 0.25s ease-out;
        }

        /* Terapkan hanya pada layar besar */
        @media (min-width: 992px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                margin-left: var(--sidebar-width);
                padding-left: 30px;
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
            padding: 1.5rem 1.25rem; /* Padding lebih besar */
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
        
        /* Gaya Konten */
        .header-section {
            /* Latar belakang yang lebih dinamis dan halus */
            background: linear-gradient(45deg, #17a2b8, #007bff); 
            color: white;
            padding: 40px; /* Padding lebih besar */
            border-radius: 15px; /* Sudut lebih melengkung */
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.25); /* Bayangan lebih dalam */
        }
        
        /* --- Gaya Card Statistik Modern --- */
        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08); /* Bayangan yang lebih lembut */
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            position: relative;
            background-color: white !important;
        }
        .stat-card:hover {
             transform: translateY(-3px);
             box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-icon-wrapper {
            color: #fff;
            padding: 10px;
            border-radius: 8px;
            line-height: 1;
        }
        .stat-value {
            font-size: 2.8rem; 
            font-family: var(--heading-font);
            font-weight: 700;
            color: #343a40; /* Warna gelap untuk angka */
        }
        .stat-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
        }
        
        /* Warna untuk Stat Card */
        .stat-card.primary .stat-icon-wrapper { background-color: #007bff; }
        .stat-card.info .stat-icon-wrapper { background-color: #17a2b8; }
        .stat-card.danger .stat-icon-wrapper { background-color: #dc3545; }
        .stat-card.success .stat-icon-wrapper { background-color: #28a745; }


        /* --- Gaya Menu Card Akses Cepat Modern --- */
        .menu-card {
            transition: all 0.3s ease-in-out;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            height: 100%;
            background-color: white;
            border-left: 8px solid; /* Border tebal di kiri */
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: transparent !important; /* Hilangkan border standar saat hover */
        }
        .menu-card .card-title {
            font-weight: 600;
            font-family: var(--heading-font);
        }
        .menu-card .card-text {
            font-size: 0.9rem;
        }
        
        /* Navbar */
        .navbar-top {
            background-color: white !important;
            box-shadow: 0 4px 10px rgba(0,0,0,.05);
            z-index: 1020;
            border-bottom: 3px solid var(--primary-highlight);
        }
        
        /* Custom Footer */
        .footer {
            background-color: #fff !important;
            border-top: none !important;
            box-shadow: 0 -2px 5px rgba(0,0,0,.02);
            position: relative;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div id="sidebar-wrapper">
        <div class="sidebar-heading text-center">
            <i class="bi bi-hospital-fill me-2"></i> RS JIWA FO
        </div>
        <div class="list-group list-group-flush">
            <?php 
            $current_file = basename($_SERVER['PHP_SELF']);

            foreach ($menu_items as $item): 
                $is_active = ($item['link'] == $current_file) || (isset($item['active']) && $item['active'] && $current_file == 'frontoffice_dashboard.php');
                $active_class = $is_active ? 'active-menu' : '';
            ?>
                <a href="<?php echo $item['link']; ?>" 
                   class="list-group-item list-group-item-action <?php echo $active_class; ?>"
                   target="<?php echo $item['target']; ?>">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="p-3 mt-auto" style="position: absolute; bottom: 0; width: 100%;">
             <a class="btn btn-outline-light w-100 fw-bold" href="logout.php" style="border-color: #ffc107; color: #ffc107;">
                 <i class="bi bi-box-arrow-right me-2"></i> Logout
             </a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light navbar-top sticky-top py-2">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary" id="sidebarToggle">
                    <i class="bi bi-list"></i> Menu
                </button>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-dark me-3 d-none d-md-inline small">
                        Halo, <b class="fw-bolder"><?php echo $nama_lengkap_admin; ?></b> (<span class="text-info"><?php echo $role_admin; ?></span>)
                    </span>
                    <a class="btn btn-sm btn-danger d-md-none" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </nav>
        <div class="container-fluid py-4">
            
            <div class="header-section text-center">
                <h1 class="mb-2" style="font-weight: 700;">👋 Selamat Datang, Petugas!</h1>
                <p class="lead mb-0" style="font-weight: 400; font-family: var(--main-font);">Dashboard Front Office Layanan Pasien</p>
            </div>
            <h4 class="mb-4 text-dark fw-bold" style="font-family: var(--heading-font);">📈 Ringkasan Harian (<?php echo date('d F Y', strtotime($today)); ?>)</h4>
            <div class="row mb-5">
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="card stat-card primary">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon-wrapper me-4">
                                <i class="bi bi-people-fill fs-2"></i>
                            </div>
                            <div>
                                <p class="stat-title mb-0">Total Pasien Terdaftar</p>
                                <p class="stat-value mb-0 text-primary"><?php echo $stats['total_pasien']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="card stat-card info">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon-wrapper me-4">
                                <i class="bi bi-file-earmark-plus-fill fs-2"></i>
                            </div>
                            <div>
                                <p class="stat-title mb-0">Pendaftaran Baru Hari Ini</p>
                                <p class="stat-value mb-0 text-info"><?php echo $stats['pendaftaran_hari_ini']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="card stat-card danger">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon-wrapper me-4">
                                <i class="bi bi-clock-fill fs-2"></i>
                            </div>
                            <div>
                                <p class="stat-title mb-0">Menunggu Panggilan</p>
                                <p class="stat-value mb-0 text-danger"><?php echo $stats['antrian_menunggu']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="card stat-card success">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-icon-wrapper me-4">
                                <i class="bi bi-check-circle-fill fs-2"></i>
                            </div>
                            <div>
                                <p class="stat-title mb-0">Antrian Selesai Dilayani</p>
                                <p class="stat-value mb-0 text-success"><?php echo $stats['antrian_selesai']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mb-4 text-dark fw-bold" style="font-family: var(--heading-font);">⚙️ Akses Cepat Modul Utama</h4>
            
            <div class="row mt-3">
                <?php foreach ($internal_menu_items_dashboard as $item): ?>
                    <div class="col-lg-6 col-md-12 mb-4">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none" target="<?php echo $item['target']; ?>">
                            <div class="card menu-card shadow-sm" 
                                 style="border-left-color: var(--bs-<?php echo $item['color']; ?>) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $item['icon']; ?> fs-1 me-4 text-<?php echo $item['color']; ?>"></i>
                                        <div>
                                            <h5 class="card-title text-dark mb-1"><?php echo $item['title']; ?></h5>
                                            <p class="card-text text-muted small mb-0"><?php echo $item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <?php if ($public_display_item): ?>
                    <div class="col-lg-6 col-md-12 mb-4">
                        <a href="<?php echo $public_display_item['link']; ?>" class="text-decoration-none" target="<?php echo $public_display_item['target']; ?>">
                            <div class="card menu-card shadow-lg bg-light" 
                                 style="border-left-color: var(--bs-warning) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $public_display_item['icon']; ?> fs-1 me-4 text-warning"></i>
                                        <div>
                                            <h5 class="card-title text-dark mb-1">📢 <?php echo $public_display_item['title']; ?></h5>
                                            <p class="card-text text-dark small mb-0 fw-bold"><?php echo $public_display_item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr class="my-5">

            <div class="alert alert-primary text-center shadow-sm border-0" role="alert" style="border-radius: 10px;">
                <i class="bi bi-lightbulb-fill me-2"></i> **Tips Modern:** Klik tombol <i class="bi bi-list"></i> **Menu** di pojok kiri atas untuk menyembunyikan/menampilkan sidebar dan memaksimalkan ruang kerja Anda.
            </div>

        </div>
        
        <footer class="footer mt-auto py-3">
            <div class="container-fluid text-center">
                <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi. Didesain dengan Modern UI.</span>
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