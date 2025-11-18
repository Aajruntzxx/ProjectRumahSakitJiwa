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
        'title' => 'Daftar Pasien',
        'desc' => 'Kelola data registrasi dan informasi dasar pasien.',
        'icon' => 'bi-people-fill',
        'color' => 'primary',
        'link' => 'pasien_list.php',
        'target' => '_self'
    ],
    [
        'title' => 'Manajemen Pendaftaran',
        'desc' => 'Input pendaftaran baru dan verifikasi pendaftaran online.',
        'icon' => 'bi-file-earmark-spreadsheet-fill',
        'color' => 'info',
        'link' => 'pendaftaran_list.php',
        'target' => '_self'
    ],
    [
        'title' => 'Pemanggilan Antrian',
        'desc' => 'Panggil antrian pasien yang sudah terverifikasi hari ini.',
        'icon' => 'bi-telephone-fill',
        'color' => 'success',
        'link' => 'antrian_call.php',
        'target' => '_self'
    ],
    [
        'title' => 'Layar Antrian Publik',
        'desc' => 'Tampilkan status antrian saat ini di layar tunggu (Buka di tab baru).',
        'icon' => 'bi-tv-fill',
        'color' => 'warning',
        'link' => 'antrian_display.php', 
        'target' => '_blank'
    ],
    [
        'title' => 'Laporan Pendaftaran',
        'desc' => 'Lihat rekapitulasi dan detail pendaftaran dalam rentang waktu tertentu.',
        'icon' => 'bi-bar-chart-fill',
        'color' => 'secondary',
        'link' => 'report.php',
        'target' => '_self'
    ],
];

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
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f0f2f5;
            padding-top: 56px;
        }
        .content-wrapper {
            flex: 1;
            padding-top: 30px;
            padding-bottom: 30px;
        }
        .header-section {
            background: linear-gradient(135deg, #007bff, #17a2b8);
            color: white;
            padding: 20px 0;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .nav-link.active-menu {
            border-bottom: 3px solid #ffc107;
            font-weight: bold;
        }
        .menu-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            border-left: 5px solid;
            height: 100%; /* Penting untuk stat card */
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="frontoffice_dashboard.php">
                <i class="bi bi-heart-pulse-fill me-2 text-info"></i> RS JIWA FO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavFO" aria-controls="navbarNavFO" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavFO">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active-menu" aria-current="page" href="frontoffice_dashboard.php">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>
                    <?php 
                    $current_path = basename($_SERVER['PHP_SELF']); 
                    foreach ($menu_items as $item): 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link" 
                               href="<?php echo $item['link']; ?>" 
                               target="<?php echo $item['target']; ?>">
                                <i class="bi <?php echo $item['icon']; ?> me-1"></i> <?php echo $item['title']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, <?php echo $nama_lengkap_admin; ?> (<?php echo $role_admin; ?>)</span>
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
                <h1 class="mb-2">👋 Selamat Datang, Petugas!</h1>
                <p class="lead mb-0">PUSAT KONTROL LAYANAN PASIEN & ANTRIAN</p>
            </div>
            
            <h4 class="mb-3 text-dark fw-bold">Ringkasan Harian (<?php echo date('d F Y', strtotime($today)); ?>)</h4>
            <div class="row mb-5">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Total Pasien</h5>
                            <p class="stat-value mb-0"><?php echo $stats['total_pasien']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-info text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Pendaftaran Hari Ini</h5>
                            <p class="stat-value mb-0"><?php echo $stats['pendaftaran_hari_ini']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-danger text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Antrian Menunggu</h5>
                            <p class="stat-value mb-0"><?php echo $stats['antrian_menunggu']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card bg-success text-white shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-uppercase small">Antrian Selesai</h5>
                            <p class="stat-value mb-0"><?php echo $stats['antrian_selesai']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="lead text-dark fw-bold mb-4">Akses Modul Utama:</p>
            
            <div class="row mt-3">
                <?php 
                // Pisahkan Layar Antrian Publik agar ditaruh di tempat yang menonjol
                $public_display_item = null;
                $internal_menu_items = [];

                foreach ($menu_items as $item) {
                    if ($item['link'] === 'antrian_display.php') {
                        $public_display_item = $item;
                    } else {
                        $internal_menu_items[] = $item;
                    }
                }
                
                foreach ($internal_menu_items as $item): ?>
                    <div class="col-lg-6 col-md-6 mb-4">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none" target="<?php echo $item['target']; ?>">
                            <div class="card menu-card shadow-lg border-<?php echo $item['color']; ?>" 
                                 style="border-left-color: var(--bs-<?php echo $item['color']; ?>) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $item['icon']; ?> fs-1 me-3 text-<?php echo $item['color']; ?>"></i>
                                        <div>
                                            <h5 class="card-title text-dark fw-bold mb-1"><?php echo $item['title']; ?></h5>
                                            <p class="card-text text-muted small mb-0"><?php echo $item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <?php if ($public_display_item): ?>
                    <div class="col-lg-6 col-md-6 mb-4">
                        <a href="<?php echo $public_display_item['link']; ?>" class="text-decoration-none" target="<?php echo $public_display_item['target']; ?>">
                            <div class="card menu-card shadow-lg border-<?php echo $public_display_item['color']; ?> bg-light" 
                                 style="border-left-color: var(--bs-<?php echo $public_display_item['color']; ?>) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $public_display_item['icon']; ?> fs-1 me-3 text-<?php echo $public_display_item['color']; ?>"></i>
                                        <div>
                                            <h5 class="card-title text-dark fw-bold mb-1"><?php echo $public_display_item['title']; ?></h5>
                                            <p class="card-text text-muted small mb-0 fw-bold text-danger"><?php echo $public_display_item['desc']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr class="my-5">

            <div class="alert alert-secondary text-center">
                <i class="bi bi-info-circle-fill me-2"></i> Gunakan navigasi di **Navbar** untuk akses cepat dari halaman manapun.
            </div>

        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Opsional: JavaScript untuk menandai link aktif di Navbar secara dinamis
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            // Logika default: tandai Dashboard sebagai aktif
            if (link.href.endsWith('frontoffice_dashboard.php')) {
                if (currentPath === 'frontoffice_dashboard.php' || currentPath === '') {
                    link.classList.add('active-menu');
                } else {
                    link.classList.remove('active-menu');
                }
            }
        });
    });
    </script>
</body>
</html>