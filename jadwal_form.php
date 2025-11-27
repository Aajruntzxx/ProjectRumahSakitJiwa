<?php
session_start();

// --- 1. OTENTIKASI & LOGIKA DATABASE ---

// Cek Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (Hanya Super Admin)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="javascript:history.back()" class="btn btn-primary w-100">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = 'jadwal_list.php'; // Highlight menu 'Manajemen Jadwal'

// --- MENU ITEMS ---
$menu_items = [
    [ 'title' => 'Dashboard Utama', 'icon' => 'bi-house-door-fill', 'link' => 'superadmin_dashboard.php' ],
    [ 'title' => 'Manajemen Admin/User', 'icon' => 'bi-universal-access', 'link' => 'admin_list.php' ],
    [ 'title' => 'Manajemen Dokter', 'icon' => 'bi-person-badge-fill', 'link' => 'dokter_list.php' ],
    [ 'title' => 'Manajemen Poli', 'icon' => 'bi-hospital-fill', 'link' => 'poli_list.php' ],
    [ 'title' => 'Manajemen Jadwal', 'icon' => 'bi-calendar-event-fill', 'link' => 'jadwal_list.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
    [ 'title' => 'Akses Front Office', 'icon' => 'bi-door-open-fill', 'link' => 'frontoffice_dashboard.php' ],
];

$jadwal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$jadwal_data = [];
$error_message = "";
$mode = $jadwal_id > 0 ? 'Edit' : 'Tambah';

// --- AMBIL DATA DROPDOWN ---

// Helper Input
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

// 1. Daftar Dokter Aktif
$dokter_list = [];
$sql_dokter = "SELECT dokter_id, nama_lengkap FROM dokter WHERE status_aktif = 1 ORDER BY nama_lengkap";
$result_dokter = mysqli_query($conn, $sql_dokter);
if ($result_dokter) {
    while($row = mysqli_fetch_assoc($result_dokter)) {
        $dokter_list[] = $row;
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

// Daftar Hari
$hari_options = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// --- LOGIKA AMBIL DATA (EDIT) ---
if ($mode === 'Edit') {
    $sql = "SELECT * FROM jadwal_praktik WHERE jadwal_id = $jadwal_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $jadwal_data = mysqli_fetch_assoc($result);
        // Format waktu untuk input time (HH:MM)
        $jadwal_data['jam_mulai'] = substr($jadwal_data['jam_mulai'], 0, 5);
        $jadwal_data['jam_selesai'] = substr($jadwal_data['jam_selesai'], 0, 5);
    } else {
        $error_message = "Data Jadwal tidak ditemukan.";
        $jadwal_id = 0;
        $mode = 'Tambah';
    }
}

// --- LOGIKA SIMPAN DATA (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post      = (int)$_POST['jadwal_id'];
    $dokter_id    = (int)$_POST['dokter_id'];
    $poli_id      = (int)$_POST['poli_id'];
    $hari_praktik = escape_input($conn, $_POST['hari_praktik']);
    $jam_mulai    = escape_input($conn, $_POST['jam_mulai']);
    $jam_selesai  = escape_input($conn, $_POST['jam_selesai']);

    // Validasi Input
    if (empty($dokter_id) || empty($poli_id) || empty($hari_praktik) || empty($jam_mulai) || empty($jam_selesai)) {
        $error_message = "Semua kolom wajib diisi.";
    } elseif ($jam_mulai >= $jam_selesai) {
        $error_message = "Jam mulai harus lebih awal dari jam selesai.";
    } else {
        // Cek Duplikasi Jadwal (Dokter, Poli, Hari yang sama)
        $sql_check = "SELECT jadwal_id FROM jadwal_praktik 
                      WHERE dokter_id = $dokter_id AND poli_id = $poli_id AND hari_praktik = '$hari_praktik'";
        
        if ($id_post > 0) {
            $sql_check .= " AND jadwal_id != $id_post"; // Kecualikan diri sendiri saat edit
        }
        
        $result_check = mysqli_query($conn, $sql_check);
        if (mysqli_num_rows($result_check) > 0) {
            $error_message = "Dokter ini sudah memiliki jadwal di Poli tersebut pada hari $hari_praktik.";
        } else {
            if ($id_post == 0) { // Tambah
                $sql = "INSERT INTO jadwal_praktik (dokter_id, poli_id, hari_praktik, jam_mulai, jam_selesai) 
                        VALUES ($dokter_id, $poli_id, '$hari_praktik', '$jam_mulai', '$jam_selesai')";
            } else { // Edit
                $sql = "UPDATE jadwal_praktik SET 
                        dokter_id=$dokter_id, poli_id=$poli_id, hari_praktik='$hari_praktik', 
                        jam_mulai='$jam_mulai', jam_selesai='$jam_selesai'
                        WHERE jadwal_id = $id_post";
            }

            if (mysqli_query($conn, $sql)) {
                header("Location: jadwal_list.php?success=" . strtolower($mode));
                exit();
            } else {
                $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $mode; ?> Jadwal | Super Admin</title>
    
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
            background: rgba(124, 77, 255, 0.15);
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
        
        /* Card Styles */
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
        
        /* Button */
        .btn-theme {
            background-color: var(--primary-highlight); color: white; border: none;
        }
        .btn-theme:hover { background-color: #5345b8; color: white; }
    </style>
</head>
<body>

<div id="wrapper">

    <div id="overlay-backdrop"></div>

    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-gear-fill me-2"></i> SUPER ADMIN
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
                    <h3 class="fw-bold text-dark mb-1"><?php echo $mode; ?> Jadwal</h3>
                    <p class="text-muted small mb-0">Formulir pengaturan jadwal dokter.</p>
                </div>
                <a href="jadwal_list.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
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
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-clock me-2"></i>Detail Jadwal</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="hidden" name="jadwal_id" value="<?php echo $jadwal_id; ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="dokter_id" class="form-label fw-bold small">Dokter <span class="text-danger">*</span></label>
                                <select name="dokter_id" id="dokter_id" class="form-select" required>
                                    <option value="">-- Pilih Dokter --</option>
                                    <?php foreach($dokter_list as $dokter): ?>
                                        <option value="<?php echo $dokter['dokter_id']; ?>" 
                                            <?php echo ($jadwal_data['dokter_id'] ?? '') == $dokter['dokter_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dokter['nama_lengkap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="poli_id" class="form-label fw-bold small">Poli <span class="text-danger">*</span></label>
                                <select name="poli_id" id="poli_id" class="form-select" required>
                                    <option value="">-- Pilih Poli --</option>
                                    <?php foreach($poli_list as $poli): ?>
                                        <option value="<?php echo $poli['poli_id']; ?>" 
                                            <?php echo ($jadwal_data['poli_id'] ?? '') == $poli['poli_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($poli['nama_poli']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="hari_praktik" class="form-label fw-bold small">Hari Praktik <span class="text-danger">*</span></label>
                                <select name="hari_praktik" id="hari_praktik" class="form-select" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <?php foreach($hari_options as $hari): ?>
                                        <option value="<?php echo $hari; ?>" 
                                            <?php echo ($jadwal_data['hari_praktik'] ?? '') == $hari ? 'selected' : ''; ?>>
                                            <?php echo $hari; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6">
                                <label for="jam_mulai" class="form-label fw-bold small">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" value="<?php echo htmlspecialchars($jadwal_data['jam_mulai'] ?? ''); ?>" required>
                            </div>

                            <div class="col-6">
                                <label for="jam_selesai" class="form-label fw-bold small">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" value="<?php echo htmlspecialchars($jadwal_data['jam_selesai'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="jadwal_list.php" class="btn btn-light border fw-bold py-2 px-4 order-2 order-md-1">Batal</a>
                            <button type="submit" class="btn btn-theme shadow-sm fw-bold py-2 px-4 order-1 order-md-2">
                                <i class="bi bi-save me-2"></i> Simpan Data
                            </button>
                        </div>

                    </form>
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

<?php mysqli_close($conn); ?>