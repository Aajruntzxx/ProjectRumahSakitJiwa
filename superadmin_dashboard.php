<?php
session_start();

// Cek Otentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Batasi akses hanya untuk Super Admin
$allowed_roles = ['Super Admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect ke dashboard mereka atau tampilkan pesan tolak akses
    if ($_SESSION['role'] === 'Front Office') {
        header("Location: frontoffice_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Dokter') {
        header("Location: antrian_call.php");
        exit();
    }
    // Jika tidak ada redirect spesifik:
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p></div></body></html>';
    exit();
}

// Definisikan variabel sesi dan file saat ini
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Super Admin');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Super Admin');
$current_file = basename($_SERVER['PHP_SELF']);

// Daftar menu lengkap (digunakan untuk Sidebar & Content Cards)
$menu_items = [
    // Menu Sidebar (Hanya Link)
    [
        'title' => 'Dashboard Utama',
        'icon' => 'bi-house-door-fill',
        'link' => 'superadmin_dashboard.php',
        'is_sidebar' => true,
        'active' => true,
    ],
    // Card & Sidebar
    [
        'title' => 'Manajemen Admin/User',
        'desc' => 'Kelola semua akun admin, front office, dan dokter.',
        'icon' => 'bi-universal-access',
        'color' => 'danger',
        'link' => 'admin_list.php',
        'is_sidebar' => true,
    ],
    [
        'title' => 'Manajemen Dokter',
        'desc' => 'Kelola data Dokter (Spesialisasi, STR, dan Akun Terhubung).',
        'icon' => 'bi-person-badge-fill',
        'color' => 'info',
        'link' => 'dokter_list.php',
        'is_sidebar' => true,
    ],
    [
        'title' => 'Manajemen Poli',
        'desc' => 'Tambah, edit, dan hapus data Poli (Klinik).',
        'icon' => 'bi-hospital-fill',
        'color' => 'success',
        'link' => 'poli_list.php',
        'is_sidebar' => true,
    ],
    [
        'title' => 'Manajemen Jadwal',
        'desc' => 'Atur jadwal praktik Dokter dan Poli.',
        'icon' => 'bi-calendar-event-fill',
        'color' => 'warning',
        'link' => 'jadwal_list.php',
        'is_sidebar' => true,
    ],
    [
        'title' => 'Laporan Pendaftaran',
        'desc' => 'Lihat laporan detail pendaftaran dan data historis.',
        'icon' => 'bi-bar-chart-fill',
        'color' => 'secondary',
        'link' => 'report.php',
        'is_sidebar' => true,
    ],
    // Special Access Card
    [
        'title' => 'Akses Front Office',
        'desc' => 'Akses ke menu operasional harian (Pasien, Pendaftaran, Antrian).',
        'icon' => 'bi-door-open-fill',
        'color' => 'primary',
        'link' => 'frontoffice_dashboard.php',
        'is_sidebar' => true,
    ],
];

// Pisahkan item untuk tampilan card di konten utama
$card_items = array_filter($menu_items, fn($item) => isset($item['desc']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Fonts: Poppins & Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Variabel untuk Sidebar */
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #1f2a38; /* Warna gelap */
            --sidebar-color: #f8f9fa;
            --primary-highlight: #7c4dff; /* Indigo/Purple untuk Super Admin */
            --main-font: 'Poppins', sans-serif; 
            --heading-font: 'Montserrat', sans-serif;
        }
        body {
            overflow-x: hidden;
            background-color: #f0f2f5; 
            font-family: var(--main-font); 
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
        }
        
        /* Layout Wrapper */
        #wrapper {
            display: flex;
        }
        
        /* Sidebar Styles (Sama seperti FO & Dokter) */
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: calc(var(--sidebar-width) * -1);
            transition: margin 0.25s ease-out;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            position: fixed;
            z-index: 1030;
            display: flex;
            flex-direction: column; 
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

        /* Desktop View */
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
        
        /* Sidebar Navigation */
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

        /* Top Navbar */
        .navbar-top {
            background-color: white !important;
            box-shadow: 0 4px 10px rgba(0,0,0,.05);
            z-index: 1020;
            border-bottom: 3px solid #e9ecef;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        /* Header Section */
        .header-section {
            /* Warna Ungu yang dinamis */
            background: linear-gradient(45deg, #7c4dff, #5345b8); 
            color: white;
            padding: 40px;
            border-radius: 15px; 
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(124, 77, 255, 0.3);
        }
        .header-section h1 { font-weight: 700; }

        /* Menu Card (Modern Look) */
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
            border-color: transparent !important; 
        }
        .menu-card .card-title {
            font-weight: 600;
            font-family: var(--heading-font);
        }
        .menu-card .card-text {
            font-size: 0.9rem;
        }

        /* Footer */
        .footer {
            background-color: white !important;
            border-top: 1px solid #e9ecef;
            z-index: 1000;
        }
        
        .alert-info-sa {
            background-color: #e3f2fd;
            border-left: 5px solid #0d6efd;
            color: #0d6efd;
            font-weight: 500;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading text-center">
            <i class="bi bi-gear-fill me-2" style="color: var(--primary-highlight);"></i> SUPER ADMIN
        </div>
        <div class="list-group list-group-flush">
            <?php 
            foreach ($menu_items as $item): 
                // Tentukan class aktif
                $is_active = ($item['link'] == $current_file) || (isset($item['active']) && $item['active'] && $current_file == 'superadmin_dashboard.php');
                $active_class = $is_active ? 'active-menu' : '';
            ?>
                <a href="<?php echo $item['link']; ?>" 
                   class="list-group-item list-group-item-action <?php echo $active_class; ?>"
                   target="_self">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Logout Button -->
        <div class="p-3 mt-auto" style="margin-top: auto; padding-top: 1rem !important;">
             <a class="btn w-100 fw-bold" href="logout.php" style="background-color: var(--primary-highlight); color: white;">
                 <i class="bi bi-box-arrow-right me-2"></i> Logout
             </a>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <div id="page-content-wrapper">
        
        <!-- Top Navbar (Toggle & User Info) -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-top sticky-top py-2">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary" id="sidebarToggle">
                    <i class="bi bi-list"></i> Menu
                </button>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-dark me-3 d-none d-md-inline small">
                        Halo, <b style="color: var(--primary-highlight);"><?php echo $nama_lengkap_admin; ?></b> (<?php echo $role_admin; ?>)
                    </span>
                    <a class="btn btn-sm btn-danger d-md-none" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </nav>
        <!-- /Top Navbar -->

        
        <div class="container-fluid py-4">
            
            <!-- Header Section -->
            <div class="header-section text-center">
                <h1 class="mb-2">Super Admin Dashboard</h1>
                <p class="lead mb-0 fw-light">PUSAT KONTROL DAN KONFIGURASI SISTEM</p>
            </div>
            
            <p class="lead text-dark fw-bold mb-4" style="font-family: var(--heading-font);">Modul Manajemen Inti:</p>
            
            <div class="row mt-3">
                <?php foreach ($card_items as $item): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none">
                            <div class="card menu-card shadow-sm" 
                                 style="border-left-color: var(--bs-<?php echo $item['color']; ?>) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $item['icon']; ?> fs-1 me-3 text-<?php echo $item['color']; ?>"></i>
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
            </div>
            
            <hr class="my-5">

            <div class="alert alert-info-sa text-center">
                <i class="bi bi-person-fill me-2"></i> Anda adalah Super Admin. Gunakan menu di samping atau kartu di atas untuk navigasi cepat.
            </div>

        </div>
    </div>
    <!-- /#page-content-wrapper -->
</div>
<!-- /#wrapper -->
    
<footer class="footer mt-auto py-3">
    <div class="container-fluid text-center">
        <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
    </div>
</footer>

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