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
$current_file = basename($_SERVER['PHP_SELF']);

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

// --- AMBIL DATA DOKTER ---
$total_rows = 0;
// Query join untuk mendapatkan username akun admin yang terhubung (jika ada)
$sql = "SELECT d.*, a.username 
        FROM dokter d 
        LEFT JOIN admin a ON d.admin_id = a.admin_id
        ORDER BY d.nama_lengkap ASC";
$result = mysqli_query($conn, $sql);

if ($result) {
    $total_rows = mysqli_num_rows($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manajemen Dokter | Super Admin</title>
    
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
            margin-bottom: 25px;
        }
        .card-header-custom {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Agar responsif */
            gap: 10px;
        }
        .card-header-custom h5 { margin-bottom: 0; }

        /* Custom Badges */
        .badge-status-aktif {
            background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;
        }
        .badge-status-nonaktif {
            background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
        }

        /* Table Responsive */
        .table-responsive {
            white-space: nowrap; /* Mencegah teks turun baris di tabel */
        }
        .table th, .table td { vertical-align: middle; }
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
                    <h3 class="fw-bold text-dark mb-1">Manajemen Dokter</h3>
                    <p class="text-muted small mb-0">Kelola data dokter spesialis dan umum.</p>
                </div>
                <a href="dokter_form.php" class="btn btn-primary shadow-sm fw-bold" style="background-color: var(--primary-highlight); border-color: var(--primary-highlight);">
                    <i class="bi bi-person-plus-fill me-2"></i>Tambah Dokter
                </a>
            </div>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-hospital-fill me-2"></i>Daftar Dokter Aktif</h6>
                    <span class="badge bg-light text-dark border mt-2 mt-sm-0">Total: <?php echo $total_rows; ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($result && $total_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="min-width: 900px;"> <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Nama Dokter</th>
                                        <th>Spesialisasi</th>
                                        <th>No. STR</th>
                                        <th>Kontak</th>
                                        <th>Status</th>
                                        <th>User Akun</th>
                                        <th class="text-center pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <?php 
                                            // Status Badge
                                            $status_badge = $row['status_aktif'] 
                                                ? '<span class="badge rounded-pill badge-status-aktif"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>' 
                                                : '<span class="badge rounded-pill badge-status-nonaktif"><i class="bi bi-x-circle-fill me-1"></i>Nonaktif</span>';
                                            
                                            // User Account Badge
                                            $user_akun = htmlspecialchars($row['username'] ?? 'Belum terhubung');
                                            $user_badge = $row['username']
                                                ? '<span class="badge bg-info text-dark"><i class="bi bi-person-badge me-1"></i>' . $user_akun . '</span>'
                                                : '<span class="badge bg-secondary"><i class="bi bi-person-slash me-1"></i>-</span>';
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                            <td><span class="badge bg-soft-primary text-primary bg-light border border-primary"><?php echo htmlspecialchars($row['spesialisasi']); ?></span></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($row['no_str'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['no_telepon'] ?? '-'); ?></td>
                                            <td><?php echo $status_badge; ?></td>
                                            <td><?php echo $user_badge; ?></td>
                                            <td class="text-center pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="dokter_form.php?id=<?php echo $row['dokter_id']; ?>" class="btn btn-outline-warning text-dark" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center">
                            <i class="bi bi-person-vcard display-4 text-muted opacity-50"></i>
                            <p class="mt-3 text-muted small fw-bold">Belum ada data dokter yang terdaftar.</p>
                        </div>
                    <?php endif; ?>
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

<?php
if ($result) { mysqli_free_result($result); }
if (isset($conn)) { mysqli_close($conn); }
?>