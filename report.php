<?php
session_start();

// --- 1. OTENTIKASI & LOGIKA DATABASE ---

// Cek Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role
$allowed_roles = ['Super Admin', 'Front Office'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda tidak diizinkan mengakses halaman ini.</p><a href="javascript:history.back()" class="btn btn-primary w-100">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Helper Input
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

// Helper Badge Status
function get_report_status_badge($status) {
    switch ($status) {
        case 'Terverifikasi': return '<span class="badge rounded-pill bg-success"><i class="bi bi-check-circle me-1"></i>Ok</span>';
        case 'Menunggu Verifikasi': return '<span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Tunggu</span>';
        case 'Dibatalkan': return '<span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle me-1"></i>Batal</span>';
        case 'Selesai': return '<span class="badge rounded-pill bg-primary"><i class="bi bi-flag-fill me-1"></i>Selesai</span>';
        default: return '<span class="badge rounded-pill bg-secondary">' . $status . '</span>';
    }
}

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = basename($_SERVER['PHP_SELF']);

// Menu Sidebar
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];

// --- LOGIKA FILTER LAPORAN ---
$tgl_mulai = isset($_POST['tgl_mulai']) ? escape_input($conn, $_POST['tgl_mulai']) : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) ? escape_input($conn, $_POST['tgl_akhir']) : date('Y-m-d');
$report_result = null;
$total_rows = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql_report = "SELECT 
                      p.tgl_rencana_periksa, p.jenis_pendaftaran, p.status_pendaftaran, p.tgl_waktu_input,
                      s.nama_lengkap AS nama_pasien, s.no_rekam_medis, 
                      o.nama_poli
                    FROM pendaftaran p
                    JOIN pasien s ON p.pasien_id = s.pasien_id
                    JOIN poli o ON p.poli_id = o.poli_id
                    WHERE p.tgl_rencana_periksa BETWEEN '$tgl_mulai' AND '$tgl_akhir'
                    ORDER BY p.tgl_rencana_periksa ASC, p.tgl_waktu_input ASC";
    
    $report_result = mysqli_query($conn, $sql_report);
    if ($report_result) {
        $total_rows = mysqli_num_rows($report_result);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Pendaftaran | RS Jiwa</title>
    
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
            overflow-x: hidden; /* Mencegah scroll horizontal */
        }
        h1, h2, h3, h4, h5 { font-family: var(--heading-font); }

        /* --- LAYOUT WRAPPER & SIDEBAR (RESPONSIVE) --- */
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
            left: calc(var(--sidebar-width) * -1); /* Hidden default di mobile */
            overflow-y: auto;
        }

        #page-content-wrapper {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        /* --- LOGIKA MEDIA QUERIES --- */
        
        /* Desktop (Layar Besar) */
        @media (min-width: 992px) {
            #sidebar-wrapper { left: 0; } /* Muncul default di desktop */
            #page-content-wrapper { margin-left: var(--sidebar-width); }
            
            /* Logic Toggled Desktop (Hide Sidebar) */
            #wrapper.toggled #sidebar-wrapper { margin-left: calc(var(--sidebar-width) * -1); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Mobile (Layar Kecil) */
        @media (max-width: 991px) {
            /* Logic Toggled Mobile (Show Sidebar Overlay) */
            #wrapper.toggled #sidebar-wrapper { left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.3); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; } /* Konten tidak geser */
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
        .list-group-item:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .list-group-item.active-menu {
            background: rgba(13, 110, 253, 0.15);
            color: var(--primary-highlight);
            border-left: 4px solid var(--primary-highlight);
            font-weight: 600;
        }

        /* Backdrop Gelap untuk Mobile */
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

        /* Card Custom */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
            margin-bottom: 25px;
        }
        .card-header-custom {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        .card-body { padding: 20px; }

        /* Table Responsive Tweaks */
        .table-responsive {
            white-space: nowrap; /* Mencegah teks turun ke bawah, memaksa scroll horizontal */
        }
        .table th, .table td { vertical-align: middle; }
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
                $active_class = ($item['link'] == $current_file) ? 'active-menu' : '';
            ?>
                <a href="<?php echo $item['link']; ?>" class="list-group-item list-group-item-action <?php echo $active_class; ?>">
                    <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['title']; ?>
                </a>
            <?php endforeach; ?>
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
            
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Laporan Pendaftaran</h3>
                <p class="text-muted small mb-0">Rekap data pasien berdasarkan periode.</p>
            </div>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Data</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="tgl_mulai" class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>" required>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label for="tgl_akhir" class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                        </div>
                        
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-search me-2"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div class="card card-custom">
                    <div class="card-header-custom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Hasil Pencarian</h6>
                        <span class="badge bg-light text-dark border">
                            <?php echo date('d/m/y', strtotime($tgl_mulai)); ?> - <?php echo date('d/m/y', strtotime($tgl_akhir)); ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        
                        <?php if ($report_result && $total_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 800px;"> <thead class="table-light text-secondary small text-uppercase">
                                        <tr>
                                            <th class="ps-4">Tgl Rencana</th>
                                            <th>Pasien (RM)</th>
                                            <th>Poli Tujuan</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                            <th>Waktu Input</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($report_result)): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-secondary">
                                                    <?php echo date('d M Y', strtotime($row['tgl_rencana_periksa'])); ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_pasien']); ?></div>
                                                    <small class="text-muted" style="font-size: 0.75rem;">RM: <?php echo htmlspecialchars($row['no_rekam_medis'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-soft-primary text-primary border border-primary bg-light">
                                                        <?php echo htmlspecialchars($row['nama_poli']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $row['jenis_pendaftaran']; ?></td>
                                                <td><?php echo get_report_status_badge($row['status_pendaftaran']); ?></td>
                                                <td class="text-muted small">
                                                    <?php echo date('H:i', strtotime($row['tgl_waktu_input'])); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white border-top text-center text-md-end py-3">
                                <span class="fw-bold text-dark small">Total Data: <?php echo $total_rows; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center">
                                <i class="bi bi-clipboard-x display-4 text-muted opacity-50"></i>
                                <p class="mt-3 text-muted fw-bold">Tidak ditemukan data.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php else: ?>
                <div class="text-center p-5 text-muted bg-white rounded-3 shadow-sm border">
                    <i class="bi bi-arrow-up-circle display-4 text-primary opacity-50"></i>
                    <p class="mt-3 mb-0 fw-bold">Silakan filter tanggal di atas.</p>
                </div>
            <?php endif; ?>

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

        // Event: Klik Backdrop untuk menutup menu di Mobile
        backdrop.addEventListener('click', function() {
            if (window.innerWidth < 992) { // Hanya aktif di mobile
                wrapper.classList.remove('toggled');
            }
        });
    });
</script>
</body>
</html>

<?php 
if (isset($report_result) && $report_result) mysqli_free_result($report_result);
if (isset($conn)) mysqli_close($conn); 
?>