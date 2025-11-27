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

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

// Trik: Set current file ke 'pendaftaran_list.php' agar menu sidebar Pendaftaran tetap aktif
$current_file = 'pendaftaran_list.php'; 

$pendaftaran_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pendaftaran_data = [];
$error_message = "";
$mode = $pendaftaran_id > 0 ? 'Edit' : 'Input';

// Menu Sidebar
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];

// --- AMBIL DATA DROPDOWN ---

// 1. Daftar Pasien
$pasien_list = [];
$sql_pasien = "SELECT pasien_id, nama_lengkap, no_rekam_medis FROM pasien ORDER BY nama_lengkap";
$result_pasien = mysqli_query($conn, $sql_pasien);
if ($result_pasien) {
    while($row = mysqli_fetch_assoc($result_pasien)) {
        $pasien_list[] = $row;
    }
}

// 2. Daftar Poli
$poli_list = [];
$sql_poli = "SELECT poli_id, nama_poli FROM poli ORDER BY nama_poli";
$result_poli = mysqli_query($conn, $sql_poli);
if ($result_poli) {
    while($row = mysqli_fetch_assoc($result_poli)) {
        $poli_list[] = $row;
    }
}

// Opsi Status
$status_pendaftaran_options = ['Menunggu Verifikasi', 'Terverifikasi', 'Dibatalkan', 'Selesai'];

// --- LOGIKA LOAD DATA (EDIT) ---
if ($mode === 'Edit') {
    $sql = "SELECT * FROM pendaftaran WHERE pendaftaran_id = $pendaftaran_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $pendaftaran_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data Pendaftaran tidak ditemukan.";
        $pendaftaran_id = 0;
        $mode = 'Input';
    }
}

// --- LOGIKA SIMPAN DATA (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post             = (int)$_POST['pendaftaran_id'];
    $pasien_id           = (int)$_POST['pasien_id'];
    $poli_id             = (int)$_POST['poli_id'];
    $tgl_rencana_periksa = escape_input($conn, $_POST['tgl_rencana_periksa']);
    $jenis_pendaftaran   = "Walk-in"; // Fixed
    $status_pendaftaran  = escape_input($conn, $_POST['status_pendaftaran']);
    $catatan_awal        = escape_input($conn, $_POST['catatan_awal']);
    $tgl_waktu_input     = date('Y-m-d H:i:s'); 

    if (empty($pasien_id) || empty($poli_id) || empty($tgl_rencana_periksa)) {
        $error_message = "Data Pasien, Poli, dan Tanggal Periksa wajib diisi.";
    } else {
        if ($id_post == 0) { // Insert
            $sql = "INSERT INTO pendaftaran (pasien_id, poli_id, tgl_rencana_periksa, jenis_pendaftaran, status_pendaftaran, tgl_waktu_input, catatan_awal) 
                    VALUES ($pasien_id, $poli_id, '$tgl_rencana_periksa', '$jenis_pendaftaran', '$status_pendaftaran', '$tgl_waktu_input', '$catatan_awal')";
        } else { // Update
            $sql = "UPDATE pendaftaran SET 
                    pasien_id=$pasien_id, poli_id=$poli_id, tgl_rencana_periksa='$tgl_rencana_periksa', 
                    jenis_pendaftaran='$jenis_pendaftaran', status_pendaftaran='$status_pendaftaran', 
                    catatan_awal='$catatan_awal'
                    WHERE pendaftaran_id = $id_post";
        }

        if (mysqli_query($conn, $sql)) {
            header("Location: pendaftaran_list.php?status_updated=" . ($id_post == 0 ? 'data_ditambahkan' : 'data_diperbarui'));
            exit();
        } else {
            $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $mode; ?> Pendaftaran | Front Office</title>
    
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
            z-index: 1050;
            left: calc(var(--sidebar-width) * -1); /* Hidden Mobile */
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
        
        /* Card & Form */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
        }
        .card-header-custom {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
        }
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1"><?php echo $mode; ?> Pendaftaran</h3>
                    <p class="text-muted small mb-0">Isi formulir pendaftaran klinik.</p>
                </div>
                <a href="pendaftaran_list.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-medical me-2"></i>Detail Registrasi</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="hidden" name="pendaftaran_id" value="<?php echo $pendaftaran_id; ?>">

                        <div class="mb-3">
                            <label for="pasien_id" class="form-label fw-bold small text-muted">Cari Pasien <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person-fill"></i></span>
                                <select name="pasien_id" id="pasien_id" class="form-select" required>
                                    <option value="">-- Pilih Pasien --</option>
                                    <?php 
                                    $current_pasien_id = $pendaftaran_data['pasien_id'] ?? '';
                                    foreach($pasien_list as $pasien): 
                                    ?>
                                        <option value="<?php echo $pasien['pasien_id']; ?>" 
                                            <?php echo $current_pasien_id == $pasien['pasien_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pasien['nama_lengkap']); ?> (RM: <?php echo htmlspecialchars($pasien['no_rekam_medis'] ?? '-'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text small">Pasien belum ada? <a href="pasien_list.php" class="text-decoration-none">Daftar Pasien Baru</a></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="poli_id" class="form-label fw-bold small text-muted">Poli Tujuan <span class="text-danger">*</span></label>
                                <select name="poli_id" id="poli_id" class="form-select" required>
                                    <option value="">-- Pilih Poli --</option>
                                    <?php 
                                    $current_poli_id = $pendaftaran_data['poli_id'] ?? '';
                                    foreach($poli_list as $poli): 
                                    ?>
                                        <option value="<?php echo $poli['poli_id']; ?>" 
                                            <?php echo $current_poli_id == $poli['poli_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($poli['nama_poli']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tgl_rencana_periksa" class="form-label fw-bold small text-muted">Tgl Periksa <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tgl_rencana_periksa" name="tgl_rencana_periksa" 
                                    value="<?php echo htmlspecialchars($pendaftaran_data['tgl_rencana_periksa'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="jenis_pendaftaran" class="form-label fw-bold small text-muted">Jenis</label>
                                <input type="text" class="form-control bg-light text-muted" value="Walk-in" readonly>
                                <input type="hidden" name="jenis_pendaftaran" value="Walk-in">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status_pendaftaran" class="form-label fw-bold small text-muted">Status <span class="text-danger">*</span></label>
                                <select name="status_pendaftaran" id="status_pendaftaran" class="form-select" required>
                                    <?php 
                                    $current_status = $pendaftaran_data['status_pendaftaran'] ?? 'Menunggu Verifikasi';
                                    foreach($status_pendaftaran_options as $status): 
                                    ?>
                                        <option value="<?php echo $status; ?>" 
                                            <?php echo $current_status == $status ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="catatan_awal" class="form-label fw-bold small text-muted">Catatan Awal (Opsional)</label>
                            <textarea class="form-control" id="catatan_awal" name="catatan_awal" rows="3" placeholder="Keluhan singkat..."><?php echo htmlspecialchars($pendaftaran_data['catatan_awal'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="pendaftaran_list.php" class="btn btn-light border fw-bold py-2 px-4">Batal</a>
                            <button type="submit" class="btn btn-primary fw-bold py-2 px-4 shadow-sm">
                                <i class="bi bi-save me-2"></i> Simpan
                            </button>
                        </div>

                    </form>
                </div>
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