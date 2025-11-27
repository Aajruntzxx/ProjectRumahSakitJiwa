<?php
session_start();

// --- 1. OTENTIKASI & LOGIKA DATABASE ---

// Cek Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Batasi akses hanya untuk Front Office dan Super Admin
$allowed_roles = ['Super Admin', 'Front Office'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p><a href="javascript:history.back()" class="btn btn-primary w-100">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas FO');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Front Office');
$current_file = basename($_SERVER['PHP_SELF']);
$today = date('Y-m-d');

// --- LOGIKA MENGAMBIL STATISTIK ---
$stats = [
    'total_pasien' => 0,
    'pendaftaran_hari_ini' => 0,
    'antrian_menunggu' => 0,
    'antrian_selesai' => 0
];

// 1. Total Pasien
$sql_pasien = "SELECT COUNT(pasien_id) AS total FROM pasien";
$result_pasien = mysqli_query($conn, $sql_pasien);
$stats['total_pasien'] = mysqli_fetch_assoc($result_pasien)['total'];

// 2. Pendaftaran Hari Ini
$sql_pendaftaran = "SELECT COUNT(pendaftaran_id) AS total FROM pendaftaran 
                    WHERE DATE(tgl_waktu_input) = '$today' AND status_pendaftaran IN ('Menunggu Verifikasi', 'Terverifikasi')";
$result_pendaftaran = mysqli_query($conn, $sql_pendaftaran);
$stats['pendaftaran_hari_ini'] = mysqli_fetch_assoc($result_pendaftaran)['total'];

// 3. Antrian Menunggu
$sql_menunggu = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Menunggu'";
$result_menunggu = mysqli_query($conn, $sql_menunggu);
$stats['antrian_menunggu'] = mysqli_fetch_assoc($result_menunggu)['total'];

// 4. Antrian Selesai
$sql_selesai = "SELECT COUNT(antrian_id) AS total FROM antrian WHERE tgl_layanan = '$today' AND status_antrian = 'Selesai'";
$result_selesai = mysqli_query($conn, $sql_selesai);
$stats['antrian_selesai'] = mysqli_fetch_assoc($result_selesai)['total'];

// --- MENU NAVIGASI ---
$menu_items = [
    [ 'title' => 'Dashboard', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php', 'target' => '_self', 'active' => true, 'is_sidebar' => true ],
    [ 'title' => 'Daftar Pasien', 'desc' => 'Kelola data registrasi pasien.', 'icon' => 'bi-people-fill', 'color' => 'primary', 'link' => 'pasien_list.php', 'target' => '_self', 'is_sidebar' => true ],
    [ 'title' => 'Pendaftaran', 'desc' => 'Input/Verifikasi pendaftaran poli.', 'icon' => 'bi-file-earmark-medical-fill', 'color' => 'info', 'link' => 'pendaftaran_list.php', 'target' => '_self', 'is_sidebar' => true ],
    [ 'title' => 'Antrian', 'desc' => 'Panggil antrian pasien per poli.', 'icon' => 'bi-telephone-fill', 'color' => 'success', 'link' => 'antrian_call.php', 'target' => '_self', 'is_sidebar' => true ],
    [ 'title' => 'Layar Publik', 'desc' => 'Tampilan layar TV antrian (Tab Baru).', 'icon' => 'bi-tv-fill', 'color' => 'warning', 'link' => 'antrian_display.php', 'target' => '_blank', 'is_sidebar' => false ],
    [ 'title' => 'Laporan', 'desc' => 'Lihat rekapitulasi data pendaftaran.', 'icon' => 'bi-bar-chart-fill', 'color' => 'secondary', 'link' => 'report.php', 'target' => '_self', 'is_sidebar' => true ],
];

// Pisahkan item untuk Card Dashboard
$dashboard_cards = [];
$public_display_item = null;

foreach ($menu_items as $item) {
    if (isset($item['desc'])) {
        if ($item['link'] === 'antrian_display.php') {
            $public_display_item = $item;
        } else {
            $dashboard_cards[] = $item;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Front Office | RS Jiwa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1f2a38; 
            --sidebar-color: #f8f9fa;
            --primary-highlight: #0d6efd; 
            --main-font: 'Poppins', sans-serif; 
            --heading-font: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f0f2f5; 
            font-family: var(--main-font); 
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5 { font-family: var(--heading-font); }

        /* --- LAYOUT WRAPPER & SIDEBAR RESPONSIVE --- */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            transition: all 0.3s;
        }

        #sidebar-wrapper {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            z-index: 1050; /* Di atas konten */
            left: calc(var(--sidebar-width) * -1); /* Hidden Mobile Default */
            overflow-y: auto;
        }

        #page-content-wrapper {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        /* Desktop View */
        @media (min-width: 992px) {
            #sidebar-wrapper { left: 0; }
            #page-content-wrapper { margin-left: var(--sidebar-width); }
            
            #wrapper.toggled #sidebar-wrapper { margin-left: calc(var(--sidebar-width) * -1); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Mobile View */
        @media (max-width: 991px) {
            #wrapper.toggled #sidebar-wrapper { left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.3); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Sidebar Styling */
        .sidebar-heading {
            padding: 1.5rem 1rem; 
            font-size: 1.25rem;
            color: var(--primary-highlight);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 700;
            text-align: center;
        }
        .list-group-item {
            background: transparent;
            color: rgba(255,255,255,0.8);
            border: none;
            padding: 12px 20px;
        }
        .list-group-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .list-group-item.active-menu {
            background: rgba(13, 110, 253, 0.15);
            color: var(--primary-highlight);
            border-left: 4px solid var(--primary-highlight);
            font-weight: 600;
        }

        /* Overlay Backdrop */
        #overlay-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            backdrop-filter: blur(2px);
        }
        #wrapper.toggled #overlay-backdrop { display: block; }
        @media (min-width: 992px) {
            #wrapper.toggled #overlay-backdrop { display: none !important; }
        }

        /* Navbar & Content */
        .navbar-top {
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            padding: 10px 20px;
            z-index: 1020;
        }
        .main-content { padding: 20px; }
        @media (min-width: 768px) { .main-content { padding: 30px; } }

        /* Header Section */
        .header-section {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0); 
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
            position: relative;
            overflow: hidden;
        }
        .header-bg-icon {
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 8rem;
            opacity: 0.15;
            transform: rotate(-15deg);
        }

        /* Menu Cards */
        .menu-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .menu-card:hover { transform: translateY(-5px); }

        /* Stat Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            background-color: white;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stat-title { font-size: 0.8rem; color: #6c757d; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #333; font-family: var(--heading-font); }
    </style>
</head>
<body>

<div id="wrapper">

    <div id="overlay-backdrop"></div>

    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-hospital me-2"></i> FRONT OFFICE
        </div>
        <div class="list-group list-group-flush mt-2">
            <?php foreach ($menu_items as $item): 
                if(isset($item['is_sidebar']) && $item['is_sidebar']):
                    $is_active = ($item['link'] == $current_file) || (isset($item['active']) && $item['active'] && $current_file == 'frontoffice_dashboard.php');
                    $active_class = $is_active ? 'active-menu' : '';
            ?>
                <a href="<?php echo $item['link']; ?>" class="list-group-item list-group-item-action <?php echo $active_class; ?>" target="<?php echo $item['target']; ?>">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endif; endforeach; ?>
        </div>
        
        <div class="mt-auto p-3 mb-3">
             <a class="btn w-100 fw-bold" href="logout.php" style="background-color: var(--primary-highlight); color: white;">
                 <i class="bi bi-box-arrow-right me-2"></i> Logout
             </a>
        </div>
    </div>

    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light navbar-top sticky-top">
            <div class="container-fluid px-0">
                <button class="btn btn-light border shadow-sm" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <div class="ms-auto d-flex align-items-center">
                    <div class="d-none d-md-block text-end me-3">
                        <span class="d-block fw-bold small text-dark"><?php echo $nama_lengkap_admin; ?></span>
                        <span class="d-block text-muted" style="font-size: 0.75rem;"><?php echo $role_admin; ?></span>
                    </div>
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px;">
                        <i class="bi bi-person-fill text-secondary"></i>
                    </div>
                </div>
            </div>
        </nav>

        <div class="main-content">
            
            <div class="header-section">
                <i class="bi bi-pc-display header-bg-icon"></i>
                <div class="position-relative">
                    <h3 class="fw-bold mb-1">Dashboard</h3>
                    <p class="mb-0 opacity-75 small">Selamat Datang, Petugas! Siap melayani.</p>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-primary text-primary bg-light me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="stat-title">Total Pasien</div>
                                <div class="stat-value"><?php echo $stats['total_pasien']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-info text-info bg-light me-3">
                                <i class="bi bi-file-earmark-plus-fill"></i>
                            </div>
                            <div>
                                <div class="stat-title">Daftar Hari Ini</div>
                                <div class="stat-value"><?php echo $stats['pendaftaran_hari_ini']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-warning text-warning bg-light me-3">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="stat-title">Menunggu</div>
                                <div class="stat-value"><?php echo $stats['antrian_menunggu']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-success text-success bg-light me-3">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <div class="stat-title">Selesai</div>
                                <div class="stat-value"><?php echo $stats['antrian_selesai']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h6 class="text-dark fw-bold mb-3 border-start border-4 border-primary ps-3">Menu Layanan</h6>
            
            <div class="row g-3">
                <?php foreach ($dashboard_cards as $item): ?>
                    <div class="col-12 col-md-6">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none" target="<?php echo $item['target']; ?>">
                            <div class="card menu-card h-100" style="border-left: 5px solid var(--bs-<?php echo $item['color']; ?>);">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center bg-light text-<?php echo $item['color']; ?>" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi <?php echo $item['icon']; ?> fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-title text-dark fw-bold mb-1"><?php echo $item['title']; ?></h6>
                                            <p class="card-text text-muted small mb-0 lh-sm"><?php echo $item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <?php if ($public_display_item): ?>
                    <div class="col-12">
                        <a href="<?php echo $public_display_item['link']; ?>" class="text-decoration-none" target="<?php echo $public_display_item['target']; ?>">
                            <div class="card menu-card h-100 bg-light shadow-sm" style="border-left: 5px solid var(--bs-warning);">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-center text-center flex-column flex-sm-row">
                                        <i class="bi <?php echo $public_display_item['icon']; ?> fs-2 me-sm-3 mb-2 mb-sm-0 text-warning"></i>
                                        <div class="text-start">
                                            <h6 class="card-title text-dark fw-bold mb-0 text-center text-sm-start"><?php echo $public_display_item['title']; ?></h6>
                                            <p class="card-text text-muted small mb-0 d-none d-sm-block"><?php echo $public_display_item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        
        <footer class="mt-auto py-3 bg-white text-center border-top">
            <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa.</span>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var wrapper = document.getElementById('wrapper');
        var backdrop = document.getElementById('overlay-backdrop');

        // Fungsi Toggle Sidebar
        function toggleSidebar() {
            wrapper.classList.toggle('toggled');
        }

        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });

        // Tutup sidebar jika backdrop diklik (khusus mobile)
        backdrop.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                wrapper.classList.remove('toggled');
            }
        });
    });
</script>
</body>
</html>

<?php 
if (isset($result_pasien)) mysqli_free_result($result_pasien);
if (isset($conn)) mysqli_close($conn); 
?>