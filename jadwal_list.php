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

$error_message = "";
$success_message = "";

// --- LOGIKA HAPUS ---
if (isset($_GET['delete_id'])) {
    $jadwal_id_del = (int)$_GET['delete_id'];
    $sql_del = "DELETE FROM jadwal_praktik WHERE jadwal_id = $jadwal_id_del";
    
    if (mysqli_query($conn, $sql_del)) {
        header("Location: jadwal_list.php?success=delete");
        exit();
    } else {
        $error_message = "Gagal menghapus jadwal: " . mysqli_error($conn);
    }
}

// Pesan Sukses dari URL
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') $success_message = "Jadwal berhasil ditambahkan.";
    if ($_GET['success'] == 'edit') $success_message = "Jadwal berhasil diperbarui.";
    if ($_GET['success'] == 'delete') $success_message = "Jadwal berhasil dihapus.";
}

// --- AMBIL DATA JADWAL ---
$total_rows = 0;
$sql = "SELECT jp.*, d.nama_lengkap AS nama_dokter, p.nama_poli 
        FROM jadwal_praktik jp
        JOIN dokter d ON jp.dokter_id = d.dokter_id
        JOIN poli p ON jp.poli_id = p.poli_id
        ORDER BY FIELD(jp.hari_praktik, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai ASC";
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
    <title>Manajemen Jadwal | Super Admin</title>
    
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
            flex-wrap: wrap; /* Agar responsif jika judul panjang */
            gap: 10px;
        }
        .card-header-custom h5 { margin-bottom: 0; }

        /* Badges */
        .badge-poli { background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .badge-hari { background-color: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb; }

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
                    <h3 class="fw-bold text-dark mb-1">Manajemen Jadwal</h3>
                    <p class="text-muted small mb-0">Atur jadwal praktik dokter.</p>
                </div>
                <a href="jadwal_form.php" class="btn btn-primary shadow-sm fw-bold btn-sm-mobile" style="background-color: var(--primary-highlight); border-color: var(--primary-highlight);">
                    <i class="bi bi-calendar-plus me-2"></i>Tambah
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-week me-2"></i>Daftar Jadwal</h6>
                    <span class="badge bg-light text-dark border mt-2 mt-sm-0">Total: <?php echo $total_rows; ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($result && $total_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="min-width: 800px;"> <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Nama Dokter</th>
                                        <th>Poli</th>
                                        <th>Hari</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th class="text-center pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <?php 
                                            $jam_mulai = substr($row['jam_mulai'], 0, 5);
                                            $jam_selesai = substr($row['jam_selesai'], 0, 5);
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($row['nama_dokter']); ?></td>
                                            <td><span class="badge badge-poli rounded-pill"><?php echo htmlspecialchars($row['nama_poli']); ?></span></td>
                                            <td><span class="badge badge-hari rounded-pill"><?php echo $row['hari_praktik']; ?></span></td>
                                            <td class="text-success fw-bold"><?php echo $jam_mulai; ?></td>
                                            <td class="text-danger fw-bold"><?php echo $jam_selesai; ?></td>
                                            <td class="text-center pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="jadwal_form.php?id=<?php echo $row['jadwal_id']; ?>" class="btn btn-outline-warning text-dark" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="jadwal_list.php?delete_id=<?php echo $row['jadwal_id']; ?>" onclick="return confirm('Hapus jadwal <?php echo htmlspecialchars($row['nama_dokter']); ?>?')" class="btn btn-outline-danger" title="Hapus">
                                                        <i class="bi bi-trash"></i>
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
                            <i class="bi bi-calendar-x display-4 text-muted opacity-50"></i>
                            <p class="mt-3 text-muted small fw-bold">Belum ada jadwal praktik.</p>
                        </div>
                    <?php endif; ?>
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

<?php
if ($result) { mysqli_free_result($result); }
if (isset($conn)) { mysqli_close($conn); }
?>