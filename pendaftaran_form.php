<?php
session_start();

// Autentikasi dan Cek Role (hanya Super Admin atau Front Office yang bisa akses)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['Super Admin', 'Front Office'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="pasien_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

$pendaftaran_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pendaftaran_data = [];
$error_message = "";
$mode = $pendaftaran_id > 0 ? 'Edit' : 'Input';

// --- MENU ITEMS UNTUK NAV BAR (DIAMBIL DARI FRONTOFFICE DASHBOARD) ---
// Perhatikan: Tambahkan link Bootstrap Icons
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---


// --- Ambil Data Pilihan untuk Dropdown ---

// 1. Ambil daftar Pasien (Nama dan NIK/No. RM)
$pasien_list = [];
$sql_pasien = "SELECT pasien_id, nama_lengkap, no_rekam_medis FROM pasien ORDER BY nama_lengkap";
$result_pasien = mysqli_query($conn, $sql_pasien);
if ($result_pasien) {
    while($row = mysqli_fetch_assoc($result_pasien)) {
        $pasien_list[] = $row;
    }
    mysqli_free_result($result_pasien);
}

// 2. Ambil daftar Poli
$poli_list = [];
$sql_poli = "SELECT poli_id, nama_poli FROM poli ORDER BY nama_poli";
$result_poli = mysqli_query($conn, $sql_poli);
if ($result_poli) {
    while($row = mysqli_fetch_assoc($result_poli)) {
        $poli_list[] = $row;
    }
    mysqli_free_result($result_poli);
}

$jenis_pendaftaran_options = ['Online', 'Walk-in'];
$status_pendaftaran_options = ['Menunggu Verifikasi', 'Terverifikasi', 'Dibatalkan', 'Selesai'];

// --- Logika Ambil Data (untuk mode Edit) ---
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

// --- Logika POST (Simpan Data) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post             = (int)$_POST['pendaftaran_id'];
    $pasien_id           = (int)$_POST['pasien_id'];
    $poli_id             = (int)$_POST['poli_id'];
    $tgl_rencana_periksa = escape_input($conn, $_POST['tgl_rencana_periksa']);
    $jenis_pendaftaran   = escape_input($conn, $_POST['jenis_pendaftaran']);
    $status_pendaftaran  = escape_input($conn, $_POST['status_pendaftaran']);
    $catatan_awal        = escape_input($conn, $_POST['catatan_awal']);
    $tgl_waktu_input     = date('Y-m-d H:i:s'); // Hanya untuk INSERT

    if (empty($pasien_id) || empty($poli_id) || empty($tgl_rencana_periksa) || empty($jenis_pendaftaran)) {
        $error_message = "Data Pasien, Poli, Tanggal, dan Jenis Pendaftaran wajib diisi.";
    } else {
        if ($id_post == 0) { // Tambah (Input)
            $sql = "INSERT INTO pendaftaran (pasien_id, poli_id, tgl_rencana_periksa, jenis_pendaftaran, status_pendaftaran, tgl_waktu_input, catatan_awal) 
                    VALUES ($pasien_id, $poli_id, '$tgl_rencana_periksa', '$jenis_pendaftaran', '$status_pendaftaran', '$tgl_waktu_input', '$catatan_awal')";
        } else { // Edit
            $sql = "UPDATE pendaftaran SET 
                    pasien_id=$pasien_id, poli_id=$poli_id, tgl_rencana_periksa='$tgl_rencana_periksa', 
                    jenis_pendaftaran='$jenis_pendaftaran', status_pendaftaran='$status_pendaftaran', 
                    catatan_awal='$catatan_awal'
                    WHERE pendaftaran_id = $id_post";
        }

        if (mysqli_query($conn, $sql)) {
            header("Location: pendaftaran_list.php?success=" . strtolower($mode));
            exit();
        } else {
            $error_message = "Gagal menyimpan data pendaftaran: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Pendaftaran Pasien | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding-top: 56px; /* Offset untuk navbar fixed top */
        }
        .content-wrapper {
            flex: 1;
            padding: 20px 0;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .nav-link.active-menu {
            border-bottom: 3px solid #ffc107; /* Warna kuning */
            font-weight: bold;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="frontoffice_dashboard.php">
                **Front Office Panel**
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavFO" aria-controls="navbarNavFO" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavFO">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="frontoffice_dashboard.php">
                            <i class="bi bi-house-door-fill me-1"></i> Dashboard
                        </a>
                    </li>
                    <?php 
                    $current_path = basename($_SERVER['PHP_SELF']); 
                    // Link Aktif saat ini adalah halaman Form, namun di bawah kategori Pendaftaran (pendaftaran_list.php)
                    $active_category = 'pendaftaran_list.php'; 

                    foreach ($menu_items as $item): 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($item['link'] == $active_category) ? 'active-menu' : ''; ?>" href="<?php echo $item['link']; ?>">
                                <i class="bi <?php echo $item['icon']; ?> me-1"></i> <?php echo $item['title']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, **<?php echo $nama_lengkap_admin; ?>** (<?php echo $role_admin; ?>)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-outline-danger ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'info'; ?> text-white">
                            <h3 class="mb-0">📝 <?php echo $mode; ?> Pendaftaran Pasien</h3>
                        </div>
                        <div class="card-body">

                            <a href="pendaftaran_list.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Daftar Pendaftaran
                            </a>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="pendaftaran_id" value="<?php echo $pendaftaran_id; ?>">

                                <div class="mb-3">
                                    <label for="pasien_id" class="form-label">Pasien <span class="text-danger">*</span></label>
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

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="poli_id" class="form-label">Poli Tujuan <span class="text-danger">*</span></label>
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
                                        <label for="tgl_rencana_periksa" class="form-label">Tgl Rencana Periksa <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tgl_rencana_periksa" name="tgl_rencana_periksa" 
                                            value="<?php echo htmlspecialchars($pendaftaran_data['tgl_rencana_periksa'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="jenis_pendaftaran" class="form-label">Jenis Pendaftaran <span class="text-danger">*</span></label>
                                        <select name="jenis_pendaftaran" id="jenis_pendaftaran" class="form-select" required>
                                            <?php 
                                            $current_jenis = $pendaftaran_data['jenis_pendaftaran'] ?? '';
                                            foreach($jenis_pendaftaran_options as $jenis): 
                                            ?>
                                                <option value="<?php echo $jenis; ?>" 
                                                    <?php echo $current_jenis == $jenis ? 'selected' : ''; ?>>
                                                    <?php echo $jenis; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status_pendaftaran" class="form-label">Status Pendaftaran <span class="text-danger">*</span></label>
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

                                <div class="mb-3">
                                    <label for="catatan_awal" class="form-label">Catatan Awal (Opsional)</label>
                                    <textarea class="form-control" id="catatan_awal" name="catatan_awal" rows="3" placeholder="Keluhan atau catatan penting lainnya"><?php echo htmlspecialchars($pendaftaran_data['catatan_awal'] ?? ''); ?></textarea>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'info'; ?> btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-plus me-2" viewBox="0 0 16 16">
                                            <path d="M8 6.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 .5-.5"/>
                                            <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3.5-1v1h3.5L12 3.5h-1zM4 11V3a1 1 0 0 1 1-1h5.5v2h1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/>
                                        </svg>
                                        <?php echo $mode; ?> Pendaftaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    // Opsional: JavaScript untuk menandai link aktif di Navbar secara dinamis
    document.addEventListener('DOMContentLoaded', function() {
        // Tentukan kategori aktif (Pendaftaran)
        const activeCategoryLink = 'pendaftaran_list.php';
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active-menu');
            // Menandai link 'Manajemen Pendaftaran' sebagai aktif
            if (link.href.endsWith(activeCategoryLink)) {
                 link.classList.add('active-menu');
            }
        });
    });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>