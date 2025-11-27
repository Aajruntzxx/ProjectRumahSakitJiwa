<?php
session_start();

// --- 1. CEK OTENTIKASI & ROLE ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['Super Admin', 'Front Office'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda tidak diizinkan mengakses halaman ini.</p><a href="javascript:history.back()" class="btn btn-primary w-100">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// --- 2. VARIABEL TAMPILAN ---
$nama_lengkap = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = basename($_SERVER['PHP_SELF']);

// Menu Side Bar
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Pasien | Front Office</title>
    
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
            
            /* Logic Desktop Toggled (Hide) */
            #wrapper.toggled #sidebar-wrapper { margin-left: calc(var(--sidebar-width) * -1); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Mobile View */
        @media (max-width: 991px) {
            /* Logic Mobile Toggled (Show Overlay) */
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

        /* Card Styles */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
        }
        .card-header-custom {
            background-color: white;
            border-bottom: 2px solid #f0f2f5;
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }
        
        /* Table Responsive Tweaks */
        .table-responsive {
            white-space: nowrap; /* Teks satu baris */
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
                        <span class="d-block fw-bold small text-dark"><?php echo $nama_lengkap; ?></span>
                        <span class="d-block text-muted" style="font-size: 0.75rem;"><?php echo $role; ?></span>
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
                    <h3 class="fw-bold text-dark mb-1">Data Pasien</h3>
                    <p class="text-muted small mb-0">Kelola data rekam medis dan registrasi.</p>
                </div>
                <a href="pasien_form.php" class="btn btn-primary shadow-sm fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i>Pasien Baru
                </a>
            </div>

            <div class="card card-custom">
                <div class="card-body p-0">
                    <?php
                    $sql = "SELECT pasien_id, no_rekam_medis, nik, nama_lengkap, tgl_lahir, jenis_kelamin, no_hp, tgl_daftar FROM pasien ORDER BY tgl_daftar DESC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        // WRAPPER TABLE RESPONSIVE
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-hover align-middle mb-0" style="min-width: 900px;">'; // Min-width untuk trigger scroll
                        echo '<thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">No. RM</th>
                                    <th>NIK</th>
                                    <th>Nama Lengkap</th>
                                    <th>Tgl Lahir</th>
                                    <th>L/P</th>
                                    <th>No HP</th>
                                    <th>Tgl Daftar</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                              </thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            $jk_badge = ($row['jenis_kelamin'] == 'Laki-laki') 
                                ? '<span class="badge rounded-pill bg-soft-primary text-primary border border-primary bg-light">L</span>' 
                                : '<span class="badge rounded-pill bg-soft-danger text-danger border border-danger bg-light">P</span>';

                            echo "<tr>";
                            echo "<td class='ps-4 fw-bold text-primary'>" . htmlspecialchars($row['no_rekam_medis'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['nik']) . "</td>";
                            echo "<td><span class='fw-bold text-dark'>" . htmlspecialchars($row['nama_lengkap']) . "</span></td>";
                            echo "<td>" . date('d M Y', strtotime($row['tgl_lahir'])) . "</td>";
                            echo "<td>" . $jk_badge . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_hp'] ?? '-') . "</td>";
                            echo "<td>" . date('d/m/y', strtotime($row['tgl_daftar'])) . "</td>";
                            echo '<td class="text-center pe-4">';
                            echo "<a href='pasien_form.php?id=" . $row['pasien_id'] . "' class='btn btn-sm btn-outline-warning'><i class='bi bi-pencil-square'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // End table-responsive
                    } else {
                        echo '<div class="p-5 text-center">';
                        echo '<i class="bi bi-folder2-open display-4 text-muted opacity-50"></i>';
                        echo '<p class="mt-3 text-muted fw-bold">Belum ada data pasien.</p>';
                        echo '</div>';
                    }

                    if (isset($result)) mysqli_free_result($result);
                    mysqli_close($conn);
                    ?>
                </div>
                <div class="card-footer bg-white border-top text-center text-md-end py-3">
                    <small class="text-muted">Terakhir diperbarui: <?php echo date("d M Y H:i"); ?></small>
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