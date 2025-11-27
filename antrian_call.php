<?php
session_start();

// --- KONEKSI DATABASE ---
include "koneksi.php"; 

// --- FUNGSI HELPER ---
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, $data);
    }
}

function get_status_badge_antrian($status) {
    switch ($status) {
        case 'Dipanggil': return '<span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-megaphone-fill me-1"></i>Dipanggil</span>';
        case 'Sedang Periksa': return '<span class="badge rounded-pill bg-primary"><i class="bi bi-stethoscope me-1"></i>Periksa</span>';
        case 'Menunggu': return '<span class="badge rounded-pill bg-info text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>';
        case 'Selesai': return '<span class="badge rounded-pill bg-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>';
        case 'Tidak Hadir': return '<span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Skip</span>';
        default: return '<span class="badge rounded-pill bg-secondary">' . $status . '</span>';
    }
}

// --- 1. OTENTIKASI & CEK ROLE ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['Super Admin', 'Front Office', 'Dokter'];
$current_role = $_SESSION['role'] ?? 'Guest';

if (!in_array($current_role, $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body class="bg-light d-flex align-items-center justify-content-center px-3" style="min-height: 100vh;"><div class="card p-4 shadow-lg w-100" style="max-width:400px"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda tidak diizinkan mengakses halaman ini.</p><a href="login.php" class="btn btn-primary w-100">Halaman Login</a></div></body></html>';
    if (isset($conn)) mysqli_close($conn); 
    exit();
}

// Variabel Tampilan
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($current_role);
$current_file = basename($_SERVER['PHP_SELF']);

// --- MENU SIDEBAR ---
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];

$error_message = "";
$success_message = "";

// --- LOGIKA FILTER & DATA ---
$poli_list = [];
$sql_poli = "SELECT poli_id, nama_poli FROM poli ORDER BY nama_poli";
$result_poli = mysqli_query($conn, $sql_poli);
if ($result_poli) {
    while($row = mysqli_fetch_assoc($result_poli)) {
        $poli_list[] = $row;
    }
}

$poli_id_filter = isset($_GET['poli_id']) ? (int)$_GET['poli_id'] : 0;

// --- LOGIKA AKSI (Call, Skip, Serve, Finish) ---
if (isset($_GET['action']) && isset($_GET['antrian_id'])) {
    $action = escape_input($conn, $_GET['action']);
    $antrian_id = (int)$_GET['antrian_id'];
    $new_status = "";
    $update_time = "";
    $is_allowed = false;

    // Logika Izin Aksi
    if ($action === 'call' && in_array($current_role, ['Super Admin', 'Front Office'])) {
        $new_status = 'Dipanggil';
        $update_time = ", waktu_dipanggil = NOW()";
        $is_allowed = true;
    } elseif ($action === 'skip' && in_array($current_role, ['Super Admin', 'Front Office'])) {
        $new_status = 'Tidak Hadir';
        $is_allowed = true;
    } elseif ($action === 'serve' && in_array($current_role, ['Super Admin', 'Dokter'])) {
        $new_status = 'Sedang Periksa';
        $is_allowed = true;
    } elseif ($action === 'finish' && in_array($current_role, ['Super Admin', 'Dokter'])) {
        $new_status = 'Selesai';
        $is_allowed = true;
    }

    if ($is_allowed && !empty($new_status)) {
        $sql_update = "UPDATE antrian SET status_antrian = '$new_status' $update_time WHERE antrian_id = $antrian_id";
        if (mysqli_query($conn, $sql_update)) {
            $success_message = "Status: $new_status";
            
            // Redirect sesuai role
            if ($current_role === 'Dokter') {
                header("Location: dokter_dashboard.php?success_msg=" . urlencode($success_message));
                exit();
            } else {
                header("Location: antrian_call.php?poli_id=" . $poli_id_filter . "&success_msg=" . urlencode($success_message));
                exit();
            }
        } else {
            $error_message = "Gagal mengubah status: " . mysqli_error($conn);
        }
    } elseif (!$is_allowed) {
        $error_message = "Akses ditolak untuk aksi ini.";
    }
}

if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars($_GET['success_msg']);
}

// --- QUERY ANTRIAN ---
$sql_antrian = "SELECT a.*, p.nama_poli, p.poli_id, s.nama_lengkap AS nama_pasien, s.no_rekam_medis 
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                JOIN pasien s ON pf.pasien_id = s.pasien_id
                WHERE a.tgl_layanan = CURDATE()";

if ($poli_id_filter > 0) {
    $sql_antrian .= " AND a.poli_id = $poli_id_filter";
}

$sql_antrian .= " ORDER BY FIELD(a.status_antrian, 'Dipanggil', 'Sedang Periksa', 'Menunggu', 'Selesai', 'Tidak Hadir'), p.nama_poli ASC, a.antrian_id ASC";
                  
$result_antrian = mysqli_query($conn, $sql_antrian);

$grouped_antrian = [];
$total_antrian = 0;

if ($result_antrian) {
    while($row = mysqli_fetch_assoc($result_antrian)) {
        $total_antrian++;
        $poli_id = $row['poli_id'];
        if (!isset($grouped_antrian[$poli_id])) {
            $grouped_antrian[$poli_id] = [
                'nama_poli' => $row['nama_poli'],
                'antrian' => []
            ];
        }
        $grouped_antrian[$poli_id]['antrian'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pemanggilan Antrian | RS Jiwa</title>
    
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
        
        /* Card Styles */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
            margin-bottom: 25px;
        }
        .card-header-poli {
            background-color: white;
            border-bottom: 2px solid #f0f2f5;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid var(--primary-highlight);
        }

        /* Table Responsive */
        .table-responsive { white-space: nowrap; }
        .table th, .table td { vertical-align: middle; }
        
        /* Highlight Row */
        .table-dipanggil { background-color: #fff3cd !important; }
        .table-periksa { background-color: #d1e7dd !important; }
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
                    <h3 class="fw-bold text-dark mb-1">Panggilan Antrian</h3>
                    <p class="text-muted small mb-0">Kelola panggilan pasien hari ini.</p>
                </div>
                
                <form method="get" action="antrian_call.php" class="d-flex gap-2">
                    <select name="poli_id" class="form-select shadow-sm" onchange="this.form.submit()" style="min-width: 200px;">
                        <option value="0">-- Semua Poli --</option>
                        <?php foreach($poli_list as $poli): ?>
                            <option value="<?php echo $poli['poli_id']; ?>" 
                                <?php echo $poli_id_filter == $poli['poli_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($poli['nama_poli']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($poli_id_filter > 0): ?>
                        <a href="antrian_call.php" class="btn btn-light border shadow-sm" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                    <?php endif; ?>
                </form>
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

            <?php if (!empty($grouped_antrian)): ?>
                <?php foreach($grouped_antrian as $poli_id => $data_poli): ?>
                    <div class="card card-custom">
                        <div class="card-header-poli">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-building me-2 text-secondary"></i><?php echo htmlspecialchars($data_poli['nama_poli']); ?>
                            </h6>
                            <span class="badge bg-primary rounded-pill px-3"><?php echo count($data_poli['antrian']); ?> Pasien</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                                    <thead class="table-light text-secondary small text-uppercase">
                                        <tr>
                                            <th class="ps-4">No. Antrian</th>
                                            <th>Nama Pasien</th>
                                            <th>Status</th>
                                            <th>Waktu Panggil</th>
                                            <th class="text-center">Aksi Cepat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($data_poli['antrian'] as $row): 
                                            $row_class = '';
                                            if($row['status_antrian'] == 'Dipanggil') $row_class = 'table-dipanggil';
                                            elseif($row['status_antrian'] == 'Sedang Periksa') $row_class = 'table-periksa';
                                        ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td class="ps-4">
                                                    <span class="badge bg-white text-dark border border-secondary fs-6">
                                                        <?php echo htmlspecialchars($row['nomor_antrian']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_pasien']); ?></div>
                                                    <small class="text-muted"><i class="bi bi-card-heading me-1"></i>RM: <?php echo htmlspecialchars($row['no_rekam_medis'] ?? '-'); ?></small>
                                                </td>
                                                <td><?php echo get_status_badge_antrian($row['status_antrian']); ?></td>
                                                <td>
                                                    <?php if($row['waktu_dipanggil']): ?>
                                                        <i class="bi bi-clock me-1 text-muted"></i><?php echo date('H:i', strtotime($row['waktu_dipanggil'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($row['status_antrian'] === 'Menunggu'): ?>
                                                            <?php if (in_array($current_role, ['Super Admin', 'Front Office'])): ?>
                                                                <a href="antrian_call.php?action=call&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-success"><i class="bi bi-megaphone-fill me-1"></i>Panggil</a>
                                                                <a href="antrian_call.php?action=skip&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" onclick="return confirm('Tandai tidak hadir?')" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                                                            <?php else: ?>
                                                                <span class="text-muted small fst-italic">Menunggu FO</span>
                                                            <?php endif; ?>

                                                        <?php elseif ($row['status_antrian'] === 'Dipanggil'): ?>
                                                            <?php if (in_array($current_role, ['Super Admin', 'Dokter'])): ?>
                                                                <a href="antrian_call.php?action=serve&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-primary"><i class="bi bi-stethoscope me-1"></i>Periksa</a>
                                                            <?php endif; ?>
                                                            <?php if (in_array($current_role, ['Super Admin', 'Front Office'])): ?>
                                                                <a href="antrian_call.php?action=call&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-outline-warning text-dark" title="Panggil Ulang"><i class="bi bi-arrow-repeat"></i></a>
                                                            <?php endif; ?>

                                                        <?php elseif ($row['status_antrian'] === 'Sedang Periksa'): ?>
                                                            <?php if (in_array($current_role, ['Super Admin', 'Dokter'])): ?>
                                                                <a href="antrian_call.php?action=finish&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" onclick="return confirm('Selesaikan pemeriksaan?')" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Selesai</a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox display-4 mb-3 d-block opacity-50"></i>
                    <p>Tidak ada antrian untuk ditampilkan hari ini.</p>
                    <?php if($poli_id_filter > 0): ?>
                        <p class="small">Coba reset filter poli Anda.</p>
                    <?php endif; ?>
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

<?php if (isset($result_antrian)) mysqli_free_result($result_antrian); if (isset($conn)) mysqli_close($conn); ?>