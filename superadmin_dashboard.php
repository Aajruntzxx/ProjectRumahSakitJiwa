<?php
session_start();

// Cek Otentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Batasi akses hanya untuk Super Admin
$allowed_roles = ['Super Admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect logic
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Front Office') {
        header("Location: frontoffice_dashboard.php");
        exit();
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Dokter') {
        header("Location: antrian_call.php");
        exit();
    }
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p></div></body></html>';
    exit();
}

// Variabel Data
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Super Admin');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Super Admin');
$current_file = basename($_SERVER['PHP_SELF']);

// Daftar menu
$menu_items = [
    ['title' => 'Dashboard Utama', 'icon' => 'bi-house-door-fill', 'link' => 'superadmin_dashboard.php', 'is_sidebar' => true, 'active' => true],
    ['title' => 'Manajemen Admin/User', 'desc' => 'Kelola semua akun admin, front office, dan dokter.', 'icon' => 'bi-universal-access', 'color' => 'danger', 'link' => 'admin_list.php', 'is_sidebar' => true],
    ['title' => 'Manajemen Dokter', 'desc' => 'Kelola data Dokter (Spesialisasi, STR, dan Akun Terhubung).', 'icon' => 'bi-person-badge-fill', 'color' => 'info', 'link' => 'dokter_list.php', 'is_sidebar' => true],
    ['title' => 'Manajemen Poli', 'desc' => 'Tambah, edit, dan hapus data Poli (Klinik).', 'icon' => 'bi-hospital-fill', 'color' => 'success', 'link' => 'poli_list.php', 'is_sidebar' => true],
    ['title' => 'Manajemen Jadwal', 'desc' => 'Atur jadwal praktik Dokter dan Poli.', 'icon' => 'bi-calendar-event-fill', 'color' => 'warning', 'link' => 'jadwal_list.php', 'is_sidebar' => true],
    ['title' => 'Laporan Pendaftaran', 'desc' => 'Lihat laporan detail pendaftaran dan data historis.', 'icon' => 'bi-bar-chart-fill', 'color' => 'secondary', 'link' => 'report.php', 'is_sidebar' => true],
    ['title' => 'Akses Front Office', 'desc' => 'Akses ke menu operasional harian (Pasien, Pendaftaran, Antrian).', 'icon' => 'bi-door-open-fill', 'color' => 'primary', 'link' => 'frontoffice_dashboard.php', 'is_sidebar' => true],
];

$card_items = array_filter($menu_items, fn($item) => isset($item['desc']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Super Admin | RS Jiwa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1f2a38; 
            --sidebar-color: #f8f9fa;
            --primary-highlight: #7c4dff;
            --main-font: 'Poppins', sans-serif; 
            --heading-font: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f0f2f5; 
            font-family: var(--main-font);
            overflow-x: hidden; /* Mencegah scroll horizontal */
        }

        h1, h2, h3, h4, h5 { font-family: var(--heading-font); }

        /* --- LAYOUT SISTEM (RESPONSIVE) --- */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            transition: all 0.3s;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            z-index: 1050;
            left: calc(var(--sidebar-width) * -1); /* Hidden by default on mobile */
            overflow-y: auto;
        }

        /* Content Styling */
        #page-content-wrapper {
            width: 100%;
            transition: all 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- LOGIKA RESPONSIVE MEDIA QUERIES --- */
        
        /* Desktop (Layar Besar) */
        @media (min-width: 992px) {
            #sidebar-wrapper {
                left: 0; /* Show by default on desktop */
            }
            #page-content-wrapper {
                margin-left: var(--sidebar-width);
            }
            /* Class toggled untuk menyembunyikan sidebar di desktop */
            #wrapper.toggled #sidebar-wrapper {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            #wrapper.toggled #page-content-wrapper {
                margin-left: 0;
            }
        }

        /* Mobile (Layar Kecil) */
        @media (max-width: 991px) {
            /* Class toggled untuk memunculkan sidebar di mobile */
            #wrapper.toggled #sidebar-wrapper {
                left: 0; 
                box-shadow: 5px 0 15px rgba(0,0,0,0.3);
            }
            /* Content tidak digeser, sidebar menumpuk di atas (Overlay) */
            #wrapper.toggled #page-content-wrapper {
                margin-left: 0; 
            }
        }

        /* Sidebar Items */
        .sidebar-heading {
            padding: 1.5rem;
            font-size: 1.25rem;
            color: var(--primary-highlight);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 700;
        }
        .list-group-item {
            background: transparent;
            color: rgba(255,255,255,0.8);
            border: none;
            padding: 12px 20px;
        }
        .list-group-item:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .list-group-item.active-menu {
            background: rgba(124, 77, 255, 0.15);
            color: var(--primary-highlight);
            border-left: 4px solid var(--primary-highlight);
            font-weight: 600;
        }

        /* Header & Cards */
        .navbar-top {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 10px 20px;
        }

        .main-content {
            padding: 20px; /* Default padding mobile */
        }
        
        @media (min-width: 768px) {
            .main-content { padding: 30px; } /* Padding lebih besar di desktop */
        }

        .header-section {
            background: linear-gradient(45deg, #7c4dff, #5345b8);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(124, 77, 255, 0.2);
        }
        
        .header-bg-icon {
            position: absolute;
            right: -10px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.15;
            transform: rotate(-15deg);
        }

        .menu-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .menu-card:hover { transform: translateY(-5px); }
        
        /* Backdrop Gelap saat Menu Mobile Aktif */
        #overlay-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040; /* Di bawah sidebar, di atas konten */
            backdrop-filter: blur(2px);
        }
        #wrapper.toggled #overlay-backdrop {
            display: block; /* Muncul hanya saat toggled di mobile */
        }
        @media (min-width: 992px) {
            #wrapper.toggled #overlay-backdrop { display: none !important; }
        }

    </style>
</head>
<body>

<div id="wrapper">

    <div id="overlay-backdrop"></div>

    <div id="sidebar-wrapper">
        <div class="sidebar-heading d-flex align-items-center">
            <i class="bi bi-hospital-fill me-2 fs-4"></i> RS Jiwa
        </div>
        <div class="list-group list-group-flush mt-3">
            <?php foreach ($menu_items as $item): 
                $is_active = ($item['link'] == $current_file) || (isset($item['active']) && $item['active'] && $current_file == 'superadmin_dashboard.php');
                if (isset($item['is_sidebar']) && $item['is_sidebar']):
            ?>
                <a href="<?php echo $item['link']; ?>" class="list-group-item list-group-item-action <?php echo $is_active ? 'active-menu' : ''; ?>">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endif; endforeach; ?>
        </div>
        
        <div class="mt-auto p-3 mb-3">
             <a href="logout.php" class="btn w-100 fw-bold d-flex align-items-center justify-content-center" style="background-color: var(--primary-highlight); color: white;">
                 <i class="bi bi-box-arrow-left me-2"></i> Logout
             </a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-light navbar-top sticky-top">
            <div class="container-fluid p-0">
                <button class="btn btn-light border shadow-sm" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <div class="ms-auto d-flex align-items-center">
                    <div class="d-none d-md-block text-end me-3">
                        <span class="d-block fw-bold small text-dark"><?php echo $nama_lengkap_admin; ?></span>
                        <span class="d-block text-muted" style="font-size: 0.75rem;"><?php echo $role_admin; ?></span>
                    </div>
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-secondary fs-5"></i>
                    </div>
                </div>
            </div>
        </nav>

        <div class="main-content">
            
            <div class="header-section">
                <i class="bi bi-gear-wide-connected header-bg-icon"></i>
                <h3 class="fw-bold mb-1">Super Admin</h3>
                <p class="mb-0 opacity-75 small">Panel Kontrol Utama Sistem Rumah Sakit</p>
            </div>
            
            <div class="row g-3 g-md-4">
                <?php foreach ($card_items as $item): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none">
                            <div class="card menu-card h-100" style="border-left: 5px solid var(--bs-<?php echo $item['color']; ?>);">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-3 p-3 me-3 d-flex align-items-center justify-content-center bg-light text-<?php echo $item['color']; ?>" style="width: 50px; height: 50px; min-width: 50px;">
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
            </div>

        </div>

        <footer class="mt-auto py-3 bg-white text-center border-top">
            <div class="container small text-muted">
                &copy; <?php echo date("Y"); ?> RS Jiwa. <span class="d-none d-sm-inline">All Rights Reserved.</span>
            </div>
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