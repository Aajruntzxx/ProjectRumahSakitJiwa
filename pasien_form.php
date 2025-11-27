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

// Variabel Sesi
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
$current_file = 'pasien_list.php'; // Highlight menu 'Daftar Pasien'

// Inisialisasi Variabel Form
$pasien_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pasien_data = [];
$error_message = "";
$mode = $pasien_id > 0 ? 'Edit' : 'Tambah';

// Menu Sidebar
$menu_items = [
    [ 'title' => 'Dashboard FO', 'icon' => 'bi-speedometer2', 'link' => 'frontoffice_dashboard.php' ],
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Antrian Panggil', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];

// Helper Function
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

// Logika Ambil Data (Mode Edit)
if ($mode === 'Edit') {
    $sql = "SELECT * FROM pasien WHERE pasien_id = $pasien_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $pasien_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data Pasien tidak ditemukan.";
        $pasien_id = 0;
        $mode = 'Tambah';
    }
}

// Logika POST (Simpan Data)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post        = (int)$_POST['pasien_id'];
    $no_rm          = escape_input($conn, $_POST['no_rekam_medis']);
    $nik            = escape_input($conn, $_POST['nik']);
    $nama_lengkap   = escape_input($conn, $_POST['nama_lengkap']);
    $tgl_lahir      = escape_input($conn, $_POST['tgl_lahir']);
    $jenis_kelamin  = escape_input($conn, $_POST['jenis_kelamin']);
    $alamat         = escape_input($conn, $_POST['alamat']);
    $no_hp          = escape_input($conn, $_POST['no_hp']);
    $email          = escape_input($conn, $_POST['email']);
    $tgl_waktu_input = date('Y-m-d H:i:s'); 

    if ($id_post == 0) { // Tambah
        $sql = "INSERT INTO pasien (no_rekam_medis, nik, nama_lengkap, tgl_lahir, jenis_kelamin, alamat, no_hp, email, tgl_daftar) 
                VALUES ('$no_rm', '$nik', '$nama_lengkap', '$tgl_lahir', '$jenis_kelamin', '$alamat', '$no_hp', '$email', '$tgl_waktu_input')";
    } else { // Edit
        $sql = "UPDATE pasien SET 
                no_rekam_medis='$no_rm', nik='$nik', nama_lengkap='$nama_lengkap', tgl_lahir='$tgl_lahir', jenis_kelamin='$jenis_kelamin', 
                alamat='$alamat', no_hp='$no_hp', email='$email'
                WHERE pasien_id = $id_post";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: pasien_list.php?success=" . strtolower($mode));
        exit();
    } else {
        $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $mode; ?> Pasien | Front Office</title>
    
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
        
        /* Card Custom */
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
        .form-label { font-weight: 600; font-size: 0.9rem; color: #555; }
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
                    <h3 class="fw-bold text-dark mb-1"><?php echo $mode; ?> Pasien</h3>
                    <p class="text-muted small mb-0">Formulir data pasien baru.</p>
                </div>
                <a href="pasien_list.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
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
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pasien</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="hidden" name="pasien_id" value="<?php echo $pasien_id; ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="no_rekam_medis" class="form-label">No. Rekam Medis</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" class="form-control" id="no_rekam_medis" name="no_rekam_medis" value="<?php echo htmlspecialchars($pasien_data['no_rekam_medis'] ?? ''); ?>" placeholder="Isi No. RM">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="nik" class="form-label">NIK (16 digit) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nik" name="nik" value="<?php echo htmlspecialchars($pasien_data['nik'] ?? ''); ?>" required pattern="\d{16}" title="NIK harus 16 digit angka" placeholder="Masukkan NIK Pasien">
                            </div>
                            
                            <div class="col-12">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($pasien_data['nama_lengkap'] ?? ''); ?>" required placeholder="Masukkan Nama Lengkap">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="tgl_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" value="<?php echo htmlspecialchars($pasien_data['tgl_lahir'] ?? ''); ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                                    <option value="">-- Pilih JK --</option>
                                    <option value="Laki-laki" <?php echo ($pasien_data['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo ($pasien_data['jenis_kelamin'] ?? '') == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="alamat" class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Jalan, RT/RW..."><?php echo htmlspecialchars($pasien_data['alamat'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="no_hp" class="form-label">No. HP / WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($pasien_data['no_hp'] ?? ''); ?>" placeholder="08...">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Email (Opsional)</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($pasien_data['email'] ?? ''); ?>" placeholder="email@contoh.com">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="pasien_list.php" class="btn btn-light border fw-bold py-2 px-4 order-2 order-md-1">Batal</a>
                            <button type="submit" class="btn btn-primary fw-bold py-2 px-4 shadow-sm order-1 order-md-2">
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

<?php if (isset($conn)) mysqli_close($conn); ?>