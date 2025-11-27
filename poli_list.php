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
    $poli_id_del = (int)$_GET['delete_id'];
    $sql_del = "DELETE FROM poli WHERE poli_id = $poli_id_del";
    
    if (mysqli_query($conn, $sql_del)) {
        header("Location: poli_list.php?success=delete");
        exit();
    } else {
        $error_message = "Gagal menghapus poli. Mungkin terhubung data lain.";
    }
}

// --- LOGIKA TAMBAH/EDIT ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $poli_id_post = (int)$_POST['poli_id'];
    $nama_poli = escape_input($conn, $_POST['nama_poli']);
    $deskripsi = escape_input($conn, $_POST['deskripsi']);

    if ($poli_id_post == 0) { // Tambah
        $sql = "INSERT INTO poli (nama_poli, deskripsi) VALUES ('$nama_poli', '$deskripsi')";
    } else { // Edit
        $sql = "UPDATE poli SET nama_poli='$nama_poli', deskripsi='$deskripsi' WHERE poli_id = $poli_id_post";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: poli_list.php?success=" . ($poli_id_post == 0 ? 'add' : 'edit'));
        exit();
    } else {
        $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
    }
}

// Set pesan sukses
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') $success_message = "Data Poli berhasil ditambahkan.";
    if ($_GET['success'] == 'edit') $success_message = "Data Poli berhasil diperbarui.";
    if ($_GET['success'] == 'delete') $success_message = "Data Poli berhasil dihapus.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manajemen Poli | Super Admin</title>
    
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
            #sidebar-wrapper { left: 0; }
            #page-content-wrapper { margin-left: var(--sidebar-width); }
            
            #wrapper.toggled #sidebar-wrapper { margin-left: calc(var(--sidebar-width) * -1); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Mobile (Layar Kecil) */
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

        .btn-theme { background-color: var(--primary-highlight); color: white; border: none; }
        .btn-theme:hover { background-color: #5345b8; color: white; }

        /* Table Responsive */
        .table-responsive {
            white-space: nowrap; /* Teks tidak turun baris agar rapi di scroll */
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
            
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Manajemen Poli</h3>
                <p class="text-muted small mb-0">Kelola data poliklinik rumah sakit.</p>
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

            <div class="row g-4"> <div class="col-12 col-lg-4">
                    <div class="card card-custom h-100">
                        <div class="card-header-custom">
                            <h6 class="mb-0 fw-bold" id="form-title"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Poli Baru</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="poli_id" id="poli_id" value="0">
                                
                                <div class="mb-3">
                                    <label for="nama_poli" class="form-label small fw-bold">Nama Poli <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_poli" name="nama_poli" required placeholder="Cth: Poli Anak">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label small fw-bold">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" placeholder="Penjelasan singkat..."></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-theme shadow-sm fw-bold" id="submit-button">
                                        <i class="bi bi-save me-2"></i> Simpan
                                    </button>
                                    <button type="button" class="btn btn-light border" onclick="resetForm()">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card card-custom h-100">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Daftar Poli</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            $sql = "SELECT poli_id, nama_poli, deskripsi FROM poli ORDER BY poli_id ASC";
                            $result = mysqli_query($conn, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                // WRAPPER RESPONSIVE UNTUK TABEL
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-hover align-middle mb-0" style="min-width: 600px;">'; // min-width memaksa scroll horizontal di layar kecil
                                echo '<thead class="table-light text-secondary small text-uppercase"><tr><th class="ps-4">ID</th><th>Nama Poli</th><th>Deskripsi</th><th class="text-center pe-4">Aksi</th></tr></thead>';
                                echo '<tbody>';
                                
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td class='ps-4 fw-bold text-muted'>#" . $row['poli_id'] . "</td>";
                                    echo "<td><span class='fw-bold text-dark'>" . htmlspecialchars($row['nama_poli']) . "</span></td>";
                                    // Limit deskripsi agar tidak terlalu panjang di tabel
                                    $desc = htmlspecialchars($row['deskripsi'] ?? '-');
                                    if(strlen($desc) > 30) $desc = substr($desc, 0, 30) . '...';
                                    echo "<td class='text-muted small'>" . $desc . "</td>";
                                    
                                    echo '<td class="text-center pe-4">';
                                    
                                    // Tombol Aksi Mobile-Friendly
                                    echo "<button type='button' class='btn btn-sm btn-outline-warning me-1' onclick='editPoli(" . $row['poli_id'] . ", \"" . addslashes(htmlspecialchars($row['nama_poli'])) . "\", \"" . addslashes(htmlspecialchars($row['deskripsi'])) . "\")'><i class='bi bi-pencil'></i></button>";
                                    
                                    echo "<a href='poli_list.php?delete_id=" . $row['poli_id'] . "' onclick='return confirm(\"Hapus Poli: " . addslashes(htmlspecialchars($row['nama_poli'])) . "?\")' class='btn btn-sm btn-outline-danger'><i class='bi bi-trash'></i></a>";
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                            } else {
                                echo '<div class="p-5 text-center">';
                                echo '<i class="bi bi-inbox display-4 text-muted opacity-50"></i>';
                                echo '<p class="mt-3 text-muted small">Belum ada data poli.</p>';
                                echo '</div>';
                            }

                            if (isset($result)) mysqli_free_result($result);
                            mysqli_close($conn);
                            ?>
                        </div>
                    </div>
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

    // Fungsi untuk mengisi form saat Edit
    function editPoli(id, nama, deskripsi) {
        document.getElementById('poli_id').value = id;
        document.getElementById('nama_poli').value = nama;
        document.getElementById('deskripsi').value = deskripsi;
        
        // Perubahan tampilan
        const formTitle = document.getElementById('form-title');
        formTitle.innerHTML = '<i class="bi bi-pencil-square me-2 text-warning"></i>Edit Poli (ID: ' + id + ')';
        
        const btn = document.getElementById('submit-button');
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Update';
        btn.classList.remove('btn-theme');
        btn.classList.add('btn-warning', 'text-dark');
        
        // Scroll ke atas (Mobile user experience)
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Fungsi Reset
    function resetForm() {
        document.getElementById('poli_id').value = 0;
        document.getElementById('nama_poli').value = '';
        document.getElementById('deskripsi').value = '';
        
        const formTitle = document.getElementById('form-title');
        formTitle.innerHTML = '<i class="bi bi-plus-circle me-2 text-success"></i>Tambah Poli Baru';
        
        const btn = document.getElementById('submit-button');
        btn.innerHTML = '<i class="bi bi-save me-2"></i> Simpan';
        btn.classList.remove('btn-warning', 'text-dark');
        btn.classList.add('btn-theme');
    }
</script>
</body>
</html>