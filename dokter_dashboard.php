<?php
session_start();

// --- KONEKSI DATABASE ---
include "koneksi.php"; 
// -----------------------

// 1. Cek Otentikasi dan Role
if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'Dokter') {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Anda tidak memiliki izin mengakses halaman ini.</p><a href="login.php" class="btn btn-primary w-100">Halaman Login</a></div></body></html>';
    mysqli_close($conn);
    exit();
}

// Definisikan variabel sesi dan login
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Dokter');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Dokter');
$current_file = basename($_SERVER['PHP_SELF']);
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
    $types = str_repeat('i', count($poli_ids_hari_ini)); 
    $types = 's' . $types; // Tambah tipe string untuk $today
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
        $ref_array = [];
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

// --- PERUBAHAN DI SINI: Menu "Rekam Medis" Dihapus ---
$menu_items = [
    [ 'title' => 'Dashboard Dokter', 'icon' => 'bi-house-door-fill', 'link' => 'dokter_dashboard.php', 'active' => true ],
    [ 'title' => 'Monitor Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php', 'active' => false ],
];
// ----------------------------------------------------

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Dokter | RS Jiwa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1f2a38; 
            --sidebar-color: #f8f9fa;
            --primary-highlight: #198754; 
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
            background: rgba(25, 135, 84, 0.15); /* Hijau Transparan */
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
            background: linear-gradient(45deg, #198754, #20c997); 
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(25, 135, 84, 0.3);
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

        /* Stat Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            background-color: white;
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stat-title { font-size: 0.8rem; color: #6c757d; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #333; font-family: var(--heading-font); line-height: 1; }

        /* Table Responsive Tweaks */
        .table-responsive { white-space: nowrap; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>

<div id="wrapper">

    <div id="overlay-backdrop"></div>

    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-heart-pulse-fill me-2"></i> DOKTER PANEL
        </div>
        <div class="list-group list-group-flush mt-2">
            <?php foreach ($menu_items as $item): 
                $is_active = ($item['link'] == $current_file) || ($item['active']);
                $active_class = $is_active ? 'active-menu' : '';
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
                        <span class="d-block fw-bold small text-dark"><?php echo $dokter_info['nama_lengkap'] ?? $nama_lengkap_admin; ?></span>
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
                <i class="bi bi-stethoscope header-bg-icon"></i>
                <div class="position-relative">
                    <h3 class="fw-bold mb-1">Dashboard Dokter</h3>
                    <p class="mb-0 opacity-90 small">
                        <i class="bi bi-calendar-check me-2"></i><?php echo $hari_praktik; ?> | 
                        <i class="bi bi-hospital me-2 ms-2"></i><?php echo $poli_layanan_info; ?>
                    </p>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-success text-success bg-light me-3">
                                <i class="bi bi-person-check-fill"></i>
                            </div>
                            <div>
                                <div class="stat-title">Pasien Selesai Hari Ini</div>
                                <div class="stat-value text-success"><?php echo $stats['selesai_hari_ini']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="stat-icon bg-soft-primary text-primary bg-light me-3">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <div>
                                <div class="stat-title">Total Riwayat Diagnosa</div>
                                <div class="stat-value text-primary"><?php echo $stats['total_diagnosa']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="card stat-card bg-light border-0 shadow-none">
                        <div class="card-body d-flex flex-column flex-sm-row align-items-center justify-content-between p-3 gap-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-white text-warning shadow-sm me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <div class="stat-title">Antrian Menunggu / Sedang Diperiksa</div>
                                    <div class="stat-value text-dark"><?php echo count($antrian_aktif); ?></div>
                                </div>
                            </div>
                            <a href="antrian_call.php" class="btn btn-primary shadow-sm px-4 fw-bold w-100 w-sm-auto">
                                <i class="bi bi-telephone-forward me-2"></i>Kelola Panggilan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2 text-success"></i>Daftar Antrian Aktif</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($poli_ids_hari_ini)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-calendar-x display-4 mb-3 d-block opacity-50"></i>
                            <p class="small mb-0">Tidak ada jadwal praktik hari ini (<strong><?php echo $hari_praktik; ?></strong>).</p>
                        </div>
                    <?php elseif (empty($antrian_aktif)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-check2-all display-4 mb-3 d-block text-success opacity-50"></i>
                            <p class="small mb-0">Tidak ada antrian aktif saat ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No. Antrian</th>
                                        <th>Nama Pasien</th>
                                        <th>Poli</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($antrian_aktif as $antrian): 
                                        $link_call = "antrian_call.php?antrian_id=" . $antrian['antrian_id'];
                                        $btn_color = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'success' : 'primary';
                                        $btn_text = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'Lanjut' : 'Panggil';
                                        $btn_icon = ($antrian['status_antrian'] == 'Sedang Periksa') ? 'bi-stethoscope' : 'bi-megaphone-fill';
                                    ?>
                                    <tr class="<?php echo ($antrian['status_antrian'] == 'Dipanggil') ? 'table-warning' : (($antrian['status_antrian'] == 'Sedang Periksa') ? 'table-success' : ''); ?>">
                                        <td class="ps-4">
                                            <span class="badge bg-white text-dark border border-secondary fs-6">
                                                <?php echo htmlspecialchars($antrian['nomor_antrian']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($antrian['nama_pasien']); ?></td>
                                        <td><?php echo htmlspecialchars($antrian['nama_poli']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php echo ($antrian['status_antrian'] == 'Sedang Periksa') ? 'success' : (($antrian['status_antrian'] == 'Dipanggil') ? 'warning text-dark' : 'secondary'); ?>">
                                                <?php echo $antrian['status_antrian']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo $link_call; ?>" class="btn btn-sm btn-<?php echo $btn_color; ?> shadow-sm">
                                                <i class="bi <?php echo $btn_icon; ?> me-1"></i> <?php echo $btn_text; ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <footer class="mt-auto py-3 bg-white text-center border-top">
            <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa GraSHia.</span>
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