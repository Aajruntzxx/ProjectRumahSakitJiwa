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

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Super Admin');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Super Admin');

// Daftar menu utama Super Admin (Fokus pada manajemen sistem inti)
$menu_items = [
    // Manajemen Akun dan Pegawai
    [
        'title' => 'Manajemen Admin/User',
        'desc' => 'Kelola semua akun admin, front office, dan dokter.',
        'icon' => 'bi-universal-access',
        'color' => 'danger',
        'link' => 'admin_list.php',
    ],
    [
        'title' => 'Manajemen Dokter',
        'desc' => 'Kelola data Dokter (Spesialisasi, STR, dan Akun Terhubung).',
        'icon' => 'bi-person-badge-fill',
        'color' => 'info',
        'link' => 'dokter_list.php',
    ],
    // Manajemen Layanan
    [
        'title' => 'Manajemen Poli',
        'desc' => 'Tambah, edit, dan hapus data Poli (Klinik).',
        'icon' => 'bi-hospital-fill',
        'color' => 'success',
        'link' => 'poli_list.php',
    ],
    [
        'title' => 'Manajemen Jadwal',
        'desc' => 'Atur jadwal praktik Dokter dan Poli.',
        'icon' => 'bi-calendar-event-fill',
        'color' => 'warning',
        'link' => 'jadwal_list.php',
    ],
    // Monitoring dan Laporan
    [
        'title' => 'Laporan Pendaftaran',
        'desc' => 'Lihat laporan detail pendaftaran dan data historis.',
        'icon' => 'bi-bar-chart-fill',
        'color' => 'secondary',
        'link' => 'report.php',
    ],
    // Tambahan (Jika Super Admin ingin akses fitur FO)
    [
        'title' => 'Akses Front Office',
        'desc' => 'Akses ke menu operasional harian (Pasien, Pendaftaran, Antrian).',
        'icon' => 'bi-door-open-fill',
        'color' => 'primary',
        'link' => 'frontoffice_dashboard.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin | RS Jiwa</title>
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
            background: linear-gradient(135deg, #7c4dff, #5345b8); /* Gradient Ungu/Biru Tua */
            color: white;
            padding: 20px 0;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(124, 77, 255, 0.3);
        }
        .menu-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            border-left: 5px solid;
            height: 100%;
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="superadmin_dashboard.php">
                <i class="bi bi-gear-fill me-2 text-danger"></i> **SUPER ADMIN PANEL**
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavSA" aria-controls="navbarNavSA" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavSA">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="superadmin_dashboard.php">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Manajemen
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="admin_list.php">Admin/User</a></li>
                            <li><a class="dropdown-item" href="dokter_list.php">Dokter</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="poli_list.php">Poli (Klinik)</a></li>
                            <li><a class="dropdown-item" href="jadwal_list.php">Jadwal Praktik</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">
                            <i class="bi bi-bar-chart-fill me-1"></i> Laporan
                        </a>
                    </li>
                </ul>

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
            
            <div class="header-section text-center">
                <h1 class="mb-2">⚙️ Super Admin Dashboard</h1>
                <p class="lead mb-0">PUSAT KONTROL DAN KONFIGURASI SISTEM</p>
            </div>
            
            <p class="lead text-dark fw-bold mb-4">Modul Manajemen Inti:</p>
            
            <div class="row mt-3">
                <?php foreach ($menu_items as $item): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="<?php echo $item['link']; ?>" class="text-decoration-none">
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
            </div>
            
            <hr class="my-5">

            <div class="alert alert-info text-center">
                <i class="bi bi-person-fill me-2"></i> Anda adalah Super Admin. Anda memiliki hak akses tertinggi untuk mengelola seluruh konfigurasi sistem.
            </div>

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