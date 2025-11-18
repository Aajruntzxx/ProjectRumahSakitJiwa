<?php
session_start();

// Autentikasi dan Cek Role
// Asumsi: Hanya Super Admin yang diizinkan mengelola jadwal
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin yang diizinkan)
if ($_SESSION['role'] !== 'Super Admin') {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="jadwal_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

$jadwal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$jadwal_data = [];
$error_message = "";
$mode = $jadwal_id > 0 ? 'Edit' : 'Tambah';

// --- Ambil Data Pilihan untuk Dropdown ---

// 1. Ambil daftar Dokter aktif
$dokter_list = [];
$sql_dokter = "SELECT dokter_id, nama_lengkap FROM dokter WHERE status_aktif = 1 ORDER BY nama_lengkap";
$result_dokter = mysqli_query($conn, $sql_dokter);
if ($result_dokter) {
    while($row = mysqli_fetch_assoc($result_dokter)) {
        $dokter_list[] = $row;
    }
}

// 2. Ambil daftar Poli
$poli_list = [];
$sql_poli = "SELECT poli_id, nama_poli FROM poli ORDER BY nama_poli";
$result_poli = mysqli_query($conn, $sql_poli);
if ($result_poli) {
    while($row = mysqli_fetch_assoc($result_poli)) {
        $poli_list[] = $row;
    }
}

// Daftar hari praktik (sesuai ENUM di DB)
$hari_options = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// --- Logika Ambil Data (untuk mode Edit) ---
if ($mode === 'Edit') {
    $sql = "SELECT * FROM jadwal_praktik WHERE jadwal_id = $jadwal_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $jadwal_data = mysqli_fetch_assoc($result);
        // Format jam agar sesuai dengan input type="time" (HH:MM)
        $jadwal_data['jam_mulai'] = substr($jadwal_data['jam_mulai'], 0, 5);
        $jadwal_data['jam_selesai'] = substr($jadwal_data['jam_selesai'], 0, 5);
    } else {
        $error_message = "Data Jadwal tidak ditemukan.";
        $jadwal_id = 0;
        $mode = 'Tambah';
    }
}

// --- Logika POST (Simpan Data) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post     = (int)$_POST['jadwal_id'];
    $dokter_id   = (int)$_POST['dokter_id'];
    $poli_id     = (int)$_POST['poli_id'];
    $hari_praktik= escape_input($conn, $_POST['hari_praktik']);
    $jam_mulai   = escape_input($conn, $_POST['jam_mulai']);
    $jam_selesai = escape_input($conn, $_POST['jam_selesai']);

    // Validasi dasar
    if (empty($dokter_id) || empty($poli_id) || empty($hari_praktik) || empty($jam_mulai) || empty($jam_selesai)) {
        $error_message = "Semua kolom wajib diisi.";
    } elseif ($jam_mulai >= $jam_selesai) {
        $error_message = "Jam mulai harus lebih awal dari jam selesai.";
    } else {
        // Cek duplikasi (unik key: dokter_id, poli_id, hari_praktik)
        $sql_check = "SELECT jadwal_id FROM jadwal_praktik 
                      WHERE dokter_id = $dokter_id AND poli_id = $poli_id AND hari_praktik = '$hari_praktik'";
        
        if ($id_post > 0) {
            // Kecualikan diri sendiri saat edit
            $sql_check .= " AND jadwal_id != $id_post";
        }
        
        $result_check = mysqli_query($conn, $sql_check);
        if (mysqli_num_rows($result_check) > 0) {
            $error_message = "Jadwal Praktik Dokter di Poli tersebut pada hari yang sama sudah ada.";
        } else {
            if ($id_post == 0) { // Tambah
                $sql = "INSERT INTO jadwal_praktik (dokter_id, poli_id, hari_praktik, jam_mulai, jam_selesai) 
                        VALUES ($dokter_id, $poli_id, '$hari_praktik', '$jam_mulai', '$jam_selesai')";
            } else { // Edit
                $sql = "UPDATE jadwal_praktik SET 
                        dokter_id=$dokter_id, poli_id=$poli_id, hari_praktik='$hari_praktik', 
                        jam_mulai='$jam_mulai', jam_selesai='$jam_selesai'
                        WHERE jadwal_id = $id_post";
            }

            if (mysqli_query($conn, $sql)) {
                header("Location: jadwal_list.php?success=" . strtolower($mode));
                exit();
            } else {
                $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Jadwal Praktik | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="jadwal_list.php">
                RS Jiwa - Manajemen Jadwal
            </a>
            <div class="collapse navbar-collapse justify-content-end">
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
                <div class="col-lg-6 col-md-8">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> text-white">
                            <h3 class="mb-0">📝 <?php echo $mode; ?> Jadwal Praktik</h3>
                        </div>
                        <div class="card-body">

                            <a href="jadwal_list.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Daftar Jadwal
                            </a>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="jadwal_id" value="<?php echo $jadwal_id; ?>">

                                <div class="mb-3">
                                    <label for="dokter_id" class="form-label">Dokter <span class="text-danger">*</span></label>
                                    <select name="dokter_id" id="dokter_id" class="form-select" required>
                                        <option value="">-- Pilih Dokter --</option>
                                        <?php foreach($dokter_list as $dokter): ?>
                                            <option value="<?php echo $dokter['dokter_id']; ?>" 
                                                <?php echo ($jadwal_data['dokter_id'] ?? '') == $dokter['dokter_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dokter['nama_lengkap']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="poli_id" class="form-label">Poli <span class="text-danger">*</span></label>
                                    <select name="poli_id" id="poli_id" class="form-select" required>
                                        <option value="">-- Pilih Poli --</option>
                                        <?php foreach($poli_list as $poli): ?>
                                            <option value="<?php echo $poli['poli_id']; ?>" 
                                                <?php echo ($jadwal_data['poli_id'] ?? '') == $poli['poli_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($poli['nama_poli']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="hari_praktik" class="form-label">Hari Praktik <span class="text-danger">*</span></label>
                                    <select name="hari_praktik" id="hari_praktik" class="form-select" required>
                                        <option value="">-- Pilih Hari --</option>
                                        <?php foreach($hari_options as $hari): ?>
                                            <option value="<?php echo $hari; ?>" 
                                                <?php echo ($jadwal_data['hari_praktik'] ?? '') == $hari ? 'selected' : ''; ?>>
                                                <?php echo $hari; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="jam_mulai" class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" value="<?php echo htmlspecialchars($jadwal_data['jam_mulai'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="jam_selesai" class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" value="<?php echo htmlspecialchars($jadwal_data['jam_selesai'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-check me-2" viewBox="0 0 16 16">
                                            <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                                        </svg>
                                        <?php echo $mode; ?> Jadwal
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

<?php 
// Tutup koneksi jika belum ditutup di logika POST
if (isset($conn)) { 
    mysqli_close($conn); 
}
?>