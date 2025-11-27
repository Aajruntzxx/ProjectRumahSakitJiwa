<?php
session_start();

// --- 1. OTENTIKASI & CEK ROLE ---
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
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = basename($_SERVER['PHP_SELF']);

// Fungsi Helper Input
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

// Menu Sidebar
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];

$error_message = "";
$success_message = ""; 

// =========================================================================
// LOGIKA BISNIS (HAPUS & VERIFIKASI)
// =========================================================================

// 1. LOGIKA HAPUS DENGAN FOREIGN KEY FIX
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Hapus antrian terkait dulu
    $sql_del_antrian = "DELETE FROM antrian WHERE pendaftaran_id = $delete_id";
    if (mysqli_query($conn, $sql_del_antrian)) {
        // Baru hapus pendaftaran
        $sql_del_pendaftaran = "DELETE FROM pendaftaran WHERE pendaftaran_id = $delete_id";
        if (mysqli_query($conn, $sql_del_pendaftaran)) {
            header("Location: pendaftaran_list.php?status_updated=data_dihapus");
            exit();
        } else {
            $error_message = "Gagal menghapus data pendaftaran utama: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Gagal menghapus antrian terkait: " . mysqli_error($conn);
    }
}

// 2. LOGIKA VERIFIKASI / BATAL
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = escape_input($conn, $_GET['action']);
    $id = (int)$_GET['id'];
    $new_status = "";

    if ($action === 'verify') $new_status = 'Terverifikasi';
    elseif ($action === 'cancel') $new_status = 'Dibatalkan';
    
    if (!empty($new_status)) {
        // Ambil data termasuk kode_antrian poli
        $sql_get_data = "SELECT p.pasien_id, p.poli_id, p.tgl_rencana_periksa, o.kode_antrian 
                         FROM pendaftaran p
                         JOIN poli o ON p.poli_id = o.poli_id 
                         WHERE p.pendaftaran_id = $id";
        
        $res_data = mysqli_query($conn, $sql_get_data);
        $data = mysqli_fetch_assoc($res_data);
        $antrian_success = true;
        $nomor_antrian_baru = "";
        
        if ($data && $new_status === 'Terverifikasi') {
            $tgl_layanan = $data['tgl_rencana_periksa'];
            $poli_id = $data['poli_id'];
            $kode_antrian_poli = $data['kode_antrian']; 

            // Cek nomor terakhir
            $sql_max_antrian = "SELECT MAX(CAST(SUBSTRING(nomor_antrian, 2) AS SIGNED)) AS max_num 
                                FROM antrian 
                                WHERE poli_id = $poli_id AND tgl_layanan = '$tgl_layanan'";
            
            $res_max = mysqli_query($conn, $sql_max_antrian);
            $max_num = mysqli_fetch_assoc($res_max)['max_num'];
            $next_num = $max_num ? $max_num + 1 : 1;
            
            // Generate Nomor: KodePoli + 001
            $nomor_antrian_baru = $kode_antrian_poli . str_pad($next_num, 3, '0', STR_PAD_LEFT); 

            // Insert Antrian
            $sql_insert_antrian = "INSERT INTO antrian (pendaftaran_id, poli_id, tgl_layanan, nomor_antrian, status_antrian)
                                   VALUES ($id, $poli_id, '$tgl_layanan', '$nomor_antrian_baru', 'Menunggu')";
            
            if (!mysqli_query($conn, $sql_insert_antrian)) {
                $error_message = "Gagal membuat antrian: " . mysqli_error($conn);
                $antrian_success = false;
            }
        }
        
        // Update Status Pendaftaran
        if (empty($error_message) || !$antrian_success) {
            $sql_update = "UPDATE pendaftaran SET status_pendaftaran = '$new_status' WHERE pendaftaran_id = $id";
            if (mysqli_query($conn, $sql_update)) {
                $status_redir = strtolower(str_replace(' ', '_', $new_status));
                if ($new_status === 'Terverifikasi' && $antrian_success) {
                    $status_redir .= "&antrian_no=" . urlencode($nomor_antrian_baru);
                }
                header("Location: pendaftaran_list.php?status_updated=" . $status_redir);
                exit();
            } else {
                $error_message = "Gagal mengubah status pendaftaran: " . mysqli_error($conn);
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
    <title>Manajemen Pendaftaran | RS Jiwa</title>
    
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
            left: calc(var(--sidebar-width) * -1); /* Default Hidden Mobile */
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
            
            /* Toggled Desktop (Hide) */
            #wrapper.toggled #sidebar-wrapper { margin-left: calc(var(--sidebar-width) * -1); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Mobile View */
        @media (max-width: 991px) {
            /* Toggled Mobile (Show Overlay) */
            #wrapper.toggled #sidebar-wrapper { left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.3); }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; }
        }

        /* Sidebar Item */
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

        /* Overlay Backdrop Mobile */
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
            border-bottom: 2px solid #f0f2f5;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Agar judul turun jika sempit */
            gap: 10px;
        }

        /* Table Responsive Tweaks */
        .table-responsive {
            white-space: nowrap; /* Penting agar tabel bisa di-scroll horizontal */
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
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Pendaftaran</h3>
                    <p class="text-muted small mb-0">Verifikasi pendaftaran dan kelola antrian.</p>
                </div>
                <a href="pendaftaran_form.php" class="btn btn-primary shadow-sm fw-bold">
                    <i class="bi bi-plus-lg me-2"></i>Baru
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status_updated'])): ?>
                <div class="alert alert-success shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>
                            <?php 
                                $status_text = ucwords(str_replace('_', ' ', $_GET['status_updated']));
                                if ($status_text == 'Data Dihapus') {
                                    echo "Data pendaftaran berhasil dihapus!";
                                } else {
                                    echo "Status diperbarui: <strong>$status_text</strong>";
                                }
                            ?>
                        </div>
                    </div>
                    <?php if (isset($_GET['antrian_no'])): ?>
                        <div class="mt-2 p-2 bg-white bg-opacity-75 rounded border border-success">
                            Nomor Antrian: <strong><span class="text-success fs-5"><?php echo htmlspecialchars($_GET['antrian_no']); ?></span></strong>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2"></i>Data Pendaftaran</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 900px;"> <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Data Pasien</th>
                                    <th>Poli / Klinik</th>
                                    <th>Antrian</th>
                                    <th>Tgl Rencana</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT p.*, s.nama_lengkap AS nama_pasien, s.no_rekam_medis, o.nama_poli, a.nomor_antrian
                                    FROM pendaftaran p
                                    JOIN pasien s ON p.pasien_id = s.pasien_id
                                    JOIN poli o ON p.poli_id = o.poli_id
                                    LEFT JOIN antrian a ON p.pendaftaran_id = a.pendaftaran_id
                                    ORDER BY p.tgl_waktu_input DESC";
                            $result = mysqli_query($conn, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    
                                    // Status Badge Logic
                                    $status_badge = '';
                                    switch ($row['status_pendaftaran']) {
                                        case 'Terverifikasi': $status_badge = '<span class="badge rounded-pill bg-success"><i class="bi bi-check-lg me-1"></i>OK</span>'; break;
                                        case 'Menunggu Verifikasi': $status_badge = '<span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-hourglass me-1"></i>Tunggu</span>'; break;
                                        case 'Dibatalkan': $status_badge = '<span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle me-1"></i>Batal</span>'; break;
                                        case 'Selesai': $status_badge = '<span class="badge rounded-pill bg-primary">Selesai</span>'; break;
                                        default: $status_badge = '<span class="badge rounded-pill bg-secondary">' . $row['status_pendaftaran'] . '</span>';
                                    }

                                    // Antrian Badge
                                    $nomor_antrian = $row['nomor_antrian'] 
                                        ? '<span class="badge bg-soft-primary text-primary fw-bold border border-primary fs-6">' . htmlspecialchars($row['nomor_antrian']) . '</span>' 
                                        : '<span class="text-muted small">-</span>';

                                    echo "<tr>";
                                    echo "<td class='ps-4 fw-bold text-muted'>#" . $row['pendaftaran_id'] . "</td>";
                                    echo "<td>
                                            <div class='fw-bold text-dark'>" . htmlspecialchars($row['nama_pasien']) . "</div>
                                            <small class='text-muted' style='font-size:0.75rem'>RM: " . htmlspecialchars($row['no_rekam_medis'] ?? '-') . "</small>
                                          </td>";
                                    echo "<td><span class='badge bg-light text-dark border'>" . htmlspecialchars($row['nama_poli']) . "</span></td>";
                                    echo "<td>" . $nomor_antrian . "</td>";
                                    echo "<td>" . date('d M Y', strtotime($row['tgl_rencana_periksa'])) . "</td>";
                                    echo "<td><small>" . $row['jenis_pendaftaran'] . "</small></td>";
                                    echo "<td>" . $status_badge . "</td>";
                                    echo "<td class='text-center'>";
                                    
                                    echo "<div class='btn-group btn-group-sm'>";
                                    // Tombol Edit
                                    echo "<a href='pendaftaran_form.php?id=" . $row['pendaftaran_id'] . "' class='btn btn-outline-info' title='Edit'><i class='bi bi-pencil'></i></a>";
                                    
                                    // Logika Aksi
                                    if ($row['status_pendaftaran'] === 'Menunggu Verifikasi') {
                                        echo "<a href='pendaftaran_list.php?action=verify&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Verifikasi dan buat antrian?\")' class='btn btn-outline-success' title='Verifikasi'><i class='bi bi-check-lg'></i></a>";
                                        echo "<a href='pendaftaran_list.php?action=cancel&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Batalkan?\")' class='btn btn-outline-danger' title='Batalkan'><i class='bi bi-x-lg'></i></a>";
                                    } elseif ($row['status_pendaftaran'] === 'Terverifikasi') {
                                        echo "<a href='pendaftaran_list.php?action=cancel&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Batalkan? Antrian akan dihapus.\")' class='btn btn-outline-danger' title='Batalkan'><i class='bi bi-x-lg'></i></a>";
                                    }
                                    
                                    // Tombol Hapus
                                    echo "<a href='pendaftaran_list.php?delete_id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Hapus PERMANEN? Data antrian juga hilang.\")' class='btn btn-outline-secondary' title='Hapus'><i class='bi bi-trash'></i></a>";
                                    echo "</div>";

                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center py-5 text-muted'><i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>Belum ada data pendaftaran</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                    <div class="card-footer bg-white border-top text-center text-md-end py-3">
                        <small class="text-muted fw-bold">Total: <?php echo mysqli_num_rows($result); ?> Pendaftaran</small>
                    </div>
                <?php endif; ?>
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

<?php if (isset($result) && is_object($result)) mysqli_free_result($result); mysqli_close($conn); ?>