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

$pasien_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pasien_data = [];
$error_message = "";
$mode = $pasien_id > 0 ? 'Edit' : 'Tambah';

// --- MENU ITEMS UNTUK NAV BAR (DIAMBIL DARI FRONTOFFICE DASHBOARD) ---
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---


// Logika Ambil Data (untuk mode Edit)
if ($mode === 'Edit') {
    // **CATATAN KEAMANAN**: Penggunaan Prepared Statements sangat disarankan
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
    // Ambil dan bersihkan input
    $id_post         = (int)$_POST['pasien_id'];
    // Asumsi fungsi escape_input sudah ada
    $no_rm           = escape_input($conn, $_POST['no_rekam_medis']);
    $nik             = escape_input($conn, $_POST['nik']);
    $nama_lengkap    = escape_input($conn, $_POST['nama_lengkap']);
    $tgl_lahir       = escape_input($conn, $_POST['tgl_lahir']);
    $jenis_kelamin   = escape_input($conn, $_POST['jenis_kelamin']);
    $alamat          = escape_input($conn, $_POST['alamat']);
    $no_hp           = escape_input($conn, $_POST['no_hp']);
    $email           = escape_input($conn, $_POST['email']);
    
    // Tgl_daftar hanya diisi saat TAMBAH
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Data Pasien | RS Jiwa</title>
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
                    // Tentukan path saat ini untuk menandai menu aktif (Active-Menu class)
                    $current_path = basename($_SERVER['PHP_SELF']); 
                    foreach ($menu_items as $item): 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($item['link'] == $current_path) ? 'active-menu' : ''; ?>" href="<?php echo $item['link']; ?>">
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
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'primary'; ?> text-white">
                            <h3 class="mb-0">📝 <?php echo $mode; ?> Data Pasien</h3>
                        </div>
                        <div class="card-body">

                            <a href="pasien_list.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Daftar Pasien
                            </a>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="pasien_id" value="<?php echo $pasien_id; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="no_rekam_medis" class="form-label">No. Rekam Medis</label>
                                        <input type="text" class="form-control" id="no_rekam_medis" name="no_rekam_medis" value="<?php echo htmlspecialchars($pasien_data['no_rekam_medis'] ?? ''); ?>" placeholder="Isi No. RM">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="nik" class="form-label">NIK (16 digit) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nik" name="nik" value="<?php echo htmlspecialchars($pasien_data['nik'] ?? ''); ?>" required pattern="\d{16}" title="NIK harus 16 digit angka" placeholder="Masukkan NIK Pasien">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($pasien_data['nama_lengkap'] ?? ''); ?>" required placeholder="Masukkan Nama Lengkap Pasien">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tgl_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" value="<?php echo htmlspecialchars($pasien_data['tgl_lahir'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                                            <option value="">-- Pilih JK --</option>
                                            <option value="Laki-laki" <?php echo ($pasien_data['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="Perempuan" <?php echo ($pasien_data['jenis_kelamin'] ?? '') == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Masukkan Alamat Lengkap"><?php echo htmlspecialchars($pasien_data['alamat'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="no_hp" class="form-label">No. HP</label>
                                        <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($pasien_data['no_hp'] ?? ''); ?>" placeholder="Cth: 0812...">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($pasien_data['email'] ?? ''); ?>" placeholder="cth@example.com">
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'primary'; ?> btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save me-2" viewBox="0 0 16 16">
                                            <path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6h-1v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h7v-1z"/>
                                            <path d="M8.5 1.5A1.5 1.5 0 0 1 10 0h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2z"/>
                                        </svg>
                                        <?php echo $mode; ?> Data Pasien
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
</body>
</html>

<?php mysqli_close($conn); ?>