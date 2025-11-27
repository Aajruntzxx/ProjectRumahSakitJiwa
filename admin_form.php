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

// Helper Input
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = 'admin_list.php'; // Highlight menu Admin List

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

$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$admin_data = null;
$error_message = "";
$mode = $admin_id > 0 ? 'Edit' : 'Tambah';

// --- LOGIKA AMBIL DATA (EDIT) ---
if ($mode === 'Edit') {
    $sql = "SELECT admin_id, username, nama_lengkap, role, status_aktif FROM admin WHERE admin_id = $admin_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $admin_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data Admin tidak ditemukan.";
        $mode = 'Tambah';
        $admin_id = 0;
    }
}

// --- LOGIKA SIMPAN DATA (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id_post = (int)$_POST['admin_id'];
    $username      = escape_input($conn, $_POST['username']);
    $nama_lengkap  = escape_input($conn, $_POST['nama_lengkap']);
    $role          = escape_input($conn, $_POST['role']);
    $status_aktif  = isset($_POST['status_aktif']) ? 1 : 0;
    $password      = $_POST['password']; 

    if ($admin_id_post == 0) { // Tambah
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO admin (username, password_hash, nama_lengkap, role, status_aktif) VALUES ('$username', '$password_hash', '$nama_lengkap', '$role', $status_aktif)";
            if (mysqli_query($conn, $sql_insert)) {
                header("Location: admin_list.php?success=add");
                exit();
            } else {
                $error_message = "Gagal menambah data: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Password harus diisi untuk admin baru.";
        }
    } else { // Edit
        $sql_update = "UPDATE admin SET username='$username', nama_lengkap='$nama_lengkap', role='$role', status_aktif=$status_aktif";
        
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash='$password_hash'";
        }
        
        $sql_update .= " WHERE admin_id = $admin_id_post";
        
        if (mysqli_query($conn, $sql_update)) {
            // Update sesi jika mengedit diri sendiri
            if ($_SESSION['admin_id'] == $admin_id_post) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['role'] = $role;
            }
            header("Location: admin_list.php?success=edit");
            exit();
        } else {
            $error_message = "Gagal mengupdate data: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $mode; ?> Admin | Super Admin</title>
    
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
        
        /* Form Buttons */
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
                    <h3 class="fw-bold text-dark mb-1"><?php echo $mode; ?> Pengguna</h3>
                    <p class="text-muted small mb-0">Formulir data akun sistem.</p>
                </div>
                <a href="admin_list.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
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
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-person-bounding-box me-2"></i>Detail Akun</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label fw-bold small">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required placeholder="Masukkan Username">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label fw-bold small">
                                    Password 
                                    <?php if ($mode == 'Edit'): ?>
                                        <small class="text-muted fw-normal">(Opsional)</small>
                                    <?php else: ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo $mode == 'Tambah' ? 'required' : ''; ?> placeholder="Masukkan Password">
                            </div>
                            
                            <div class="col-12">
                                <label for="nama_lengkap" class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($admin_data['nama_lengkap'] ?? ''); ?>" required placeholder="Nama Lengkap Pengguna">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label fw-bold small">Role / Peran <span class="text-danger">*</span></label>
                                <select id="role" name="role" class="form-select" required>
                                    <option value="">-- Pilih Role --</option>
                                    <?php 
                                    $current_role = $admin_data['role'] ?? ''; 
                                    ?>
                                    <option value="Super Admin" <?php echo $current_role == 'Super Admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    <option value="Front Office" <?php echo $current_role == 'Front Office' ? 'selected' : ''; ?>>Front Office</option>
                                    <option value="Dokter" <?php echo $current_role == 'Dokter' ? 'selected' : ''; ?>>Dokter</option>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif" value="1" <?php echo ($admin_data['status_aktif'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="status_aktif">Status Akun Aktif</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="admin_list.php" class="btn btn-light border fw-bold py-2 px-4 order-2 order-md-1">Batal</a>
                            <button type="submit" class="btn btn-theme shadow-sm fw-bold py-2 px-4 order-1 order-md-2">
                                <i class="bi bi-save me-2"></i> Simpan Data
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
        
        <footer class="mt-auto py-3 bg-white text-center border-top">
            <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa GraSHia. Hak Cipta Dilindungi.</span>
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