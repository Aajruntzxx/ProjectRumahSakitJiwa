<?php
session_start();

// Cek Otentikasi dan Role (Hanya Dokter yang boleh akses)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] !== 'Dokter') {
    header("Location: login.php");
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Dokter');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Dokter');
$admin_id_login = $_SESSION['admin_id'];

$antrian_id = isset($_GET['antrian_id']) ? (int)$_GET['antrian_id'] : 0;
$layanan_id = isset($_GET['layanan_id']) ? (int)$_GET['layanan_id'] : 0; // Untuk mode edit
$layanan_data = [];
$pasien_info = [];
$riwayat_layanan = []; // NEW: Array untuk menyimpan riwayat
$error_message = "";
$success_message = "";
$mode = $layanan_id > 0 ? 'Edit' : 'Input';

// --- Ambil Dokter ID yang sedang login ---
$sql_dokter = "SELECT dokter_id, nama_lengkap FROM dokter WHERE admin_id = $admin_id_login";
$res_dokter = mysqli_query($conn, $sql_dokter);
$dokter_login = mysqli_fetch_assoc($res_dokter);
$dokter_id_login = $dokter_login['dokter_id'] ?? 0;

if ($dokter_id_login == 0) {
    die("Error: Akun Admin ini tidak terhubung dengan data Dokter.");
}
if (isset($res_dokter)) mysqli_free_result($res_dokter);


// --- Logika Ambil Data Antrian dan Pasien ---
if ($antrian_id > 0) {
    $sql_info = "SELECT 
                    a.nomor_antrian, a.status_antrian, 
                    p.pendaftaran_id, p.poli_id, p.pasien_id, -- Tambahkan p.pasien_id
                    s.nama_lengkap AS nama_pasien, s.no_rekam_medis, 
                    o.nama_poli
                  FROM antrian a
                  JOIN pendaftaran p ON a.pendaftaran_id = p.pendaftaran_id
                  JOIN pasien s ON p.pasien_id = s.pasien_id
                  JOIN poli o ON p.poli_id = o.poli_id
                  WHERE a.antrian_id = $antrian_id";
    
    $result_info = mysqli_query($conn, $sql_info);
    if ($result_info && mysqli_num_rows($result_info) == 1) {
        $pasien_info = mysqli_fetch_assoc($result_info);
    } else {
        $error_message = "Data Antrian tidak ditemukan atau tidak valid.";
        $antrian_id = 0; // Blokir form
    }
    if (isset($result_info)) mysqli_free_result($result_info);
    
    // Cek apakah data layanan medis sudah ada untuk antrian ini (jika mode Input)
    if ($mode === 'Input') {
        $sql_check_exist = "SELECT layanan_id, diagnosa, tindakan FROM layanan_medis WHERE antrian_id = $antrian_id";
        $res_check = mysqli_query($conn, $sql_check_exist);
        if (mysqli_num_rows($res_check) > 0) {
            $layanan_data = mysqli_fetch_assoc($res_check);
            $layanan_id = $layanan_data['layanan_id'];
            $mode = 'Edit';
            // PENTING: Redirect ke mode edit jika data sudah ada
            header("Location: layanan_medis_form.php?layanan_id=" . $layanan_id);
            exit();
        }
        if (isset($res_check)) mysqli_free_result($res_check);
    }
}


// --- Logika Ambil Data Layanan Medis (untuk mode Edit) ---
if ($mode === 'Edit' && $layanan_id > 0) {
    $sql = "SELECT * FROM layanan_medis WHERE layanan_id = $layanan_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $layanan_data = mysqli_fetch_assoc($result);
        $antrian_id = $layanan_data['antrian_id']; // Ambil antrian_id dari data layanan

        // Ambil info pasien/antrian yang terkait dengan layanan yang diedit
        $sql_info = "SELECT a.nomor_antrian, s.nama_lengkap AS nama_pasien, s.no_rekam_medis, p.pasien_id, o.nama_poli 
                     FROM antrian a 
                     JOIN pendaftaran p ON a.pendaftaran_id = p.pendaftaran_id
                     JOIN pasien s ON p.pasien_id = s.pasien_id
                     JOIN poli o ON p.poli_id = o.poli_id
                     WHERE a.antrian_id = $antrian_id";
        $result_info = mysqli_query($conn, $sql_info);
        $pasien_info = mysqli_fetch_assoc($result_info);
        if (isset($result_info)) mysqli_free_result($result_info);
        
    } else {
        $error_message = "Data Layanan Medis tidak ditemukan.";
        $layanan_id = 0;
        $mode = 'Input';
    }
    if (isset($result)) mysqli_free_result($result);
}


// --- RIWAYAT LAYANAN PASIEN (Tambahan untuk Dokter) ---
if (!empty($pasien_info['pasien_id'])) {
    $pasien_id_current = $pasien_info['pasien_id'];
    
    $sql_riwayat = "SELECT lm.tgl_waktu_layanan, lm.diagnosa, p.nama_poli, d.nama_lengkap AS nama_dokter
                    FROM layanan_medis lm
                    JOIN antrian a ON lm.antrian_id = a.antrian_id
                    JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                    JOIN poli p ON pf.poli_id = p.poli_id
                    JOIN dokter d ON lm.dokter_id = d.dokter_id
                    WHERE pf.pasien_id = $pasien_id_current
                    ORDER BY lm.tgl_waktu_layanan DESC
                    LIMIT 3"; // Hanya ambil 3 riwayat terakhir
    
    $result_riwayat = mysqli_query($conn, $sql_riwayat);
    if ($result_riwayat) {
        while ($row = mysqli_fetch_assoc($result_riwayat)) {
            $riwayat_layanan[] = $row;
        }
        mysqli_free_result($result_riwayat);
    }
}


// --- Logika POST (Simpan Data) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $antrian_id > 0) {
    $layanan_id_post       = (int)$_POST['layanan_id'];
    $antrian_id_post       = (int)$_POST['antrian_id'];
    $dokter_id_post        = $dokter_id_login; // Gunakan ID Dokter yang login
    $diagnosa              = escape_input($conn, $_POST['diagnosa']);
    $tindakan              = escape_input($conn, $_POST['tindakan']);
    $tgl_waktu_layanan     = date('Y-m-d H:i:s'); 

    if (empty($diagnosa) && empty($tindakan)) {
        $error_message = "Diagnosa atau Tindakan wajib diisi.";
    } else {
        if ($layanan_id_post == 0) { // Tambah (Input)
            // Lakukan pengecekan duplikasi terakhir
            $sql_check_exist = "SELECT layanan_id FROM layanan_medis WHERE antrian_id = $antrian_id_post";
            if (mysqli_num_rows(mysqli_query($conn, $sql_check_exist)) > 0) {
                 $error_message = "Layanan medis untuk antrian ini sudah ada. Silakan gunakan mode Edit.";
            } else {
                $sql = "INSERT INTO layanan_medis (antrian_id, dokter_id, tgl_waktu_layanan, diagnosa, tindakan) 
                        VALUES ($antrian_id_post, $dokter_id_post, '$tgl_waktu_layanan', '$diagnosa', '$tindakan')";
                
                if (mysqli_query($conn, $sql)) {
                    // BERHASIL: Ubah status antrian menjadi 'Selesai'
                    $sql_finish_antrian = "UPDATE antrian SET status_antrian = 'Selesai' WHERE antrian_id = $antrian_id_post";
                    mysqli_query($conn, $sql_finish_antrian); 
                    
                    header("Location: dokter_dashboard.php?success_msg=" . urlencode("Layanan Medis berhasil diinput. Antrian telah diselesaikan."));
                    exit();
                } else {
                    $error_message = "Gagal menyimpan data layanan medis: " . mysqli_error($conn);
                }
            }
        } else { // Edit
            $sql = "UPDATE layanan_medis SET 
                    diagnosa='$diagnosa', tindakan='$tindakan'
                    WHERE layanan_id = $layanan_id_post";

            if (mysqli_query($conn, $sql)) {
                $success_message = "Data Layanan Medis berhasil diubah.";
                header("Location: dokter_dashboard.php?success_msg=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "Gagal mengupdate data layanan medis: " . mysqli_error($conn);
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
    <title><?php echo $mode; ?> Layanan Medis | RS Jiwa</title>
    <!-- Link Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Link Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Style kustom untuk layout -->
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .content-wrapper {
            flex: 1;
            padding: 20px 0;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .info-card {
            background-color: #e9ecef;
            border-left: 5px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .riwayat-card {
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fefefe;
        }
        .riwayat-item {
            border-bottom: 1px dashed #eee;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
        .riwayat-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>

    <!-- 🧠 Navbar DOKTER -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dokter_dashboard.php">
                <i class="bi bi-heart-pulse-fill me-2 text-success"></i> **Dokter Panel**
            </a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, **<?php echo $dokter_login['nama_lengkap'] ?? $nama_lengkap_admin; ?>** (<?php echo $role_admin; ?>)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-outline-danger ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama (Formulir) -->
    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-md-12">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'primary'; ?> text-white">
                            <h3 class="mb-0">🩺 <?php echo $mode; ?> Layanan Medis</h3>
                        </div>
                        <div class="card-body">

                            <!-- Tombol Kembali -->
                            <a href="dokter_dashboard.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Dashboard Dokter
                            </a>
                            
                            <!-- Area Pesan Error/Sukses -->
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($success_message): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($antrian_id > 0 && empty($error_message)): ?>

                                <div class="row">
                                    <!-- Kolom Kiri: Info Pasien -->
                                    <div class="col-md-6">
                                        <!-- Informasi Pasien Saat Ini -->
                                        <div class="info-card">
                                            <h5 class="text-primary mb-3">Pasien Aktif</h5>
                                            <p class="mb-1"><strong>Antrian No:</strong> <span class="badge bg-primary fs-6">**<?php echo htmlspecialchars($pasien_info['nomor_antrian'] ?? 'N/A'); ?>**</span></p>
                                            <p class="mb-1"><strong>Pasien:</strong> <?php echo htmlspecialchars($pasien_info['nama_pasien'] ?? 'N/A'); ?> (RM: <span class="text-secondary"><?php echo htmlspecialchars($pasien_info['no_rekam_medis'] ?? 'N/A'); ?></span>)</p>
                                            <p class="mb-1"><strong>Poli:</strong> <?php echo htmlspecialchars($pasien_info['nama_poli'] ?? 'N/A'); ?></p>
                                            <p class="mb-0"><strong>Dokter PJ:</strong> <?php echo htmlspecialchars($dokter_login['nama_lengkap'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Riwayat Pasien -->
                                    <div class="col-md-6">
                                        <h5 class="text-dark mb-3">Riwayat Layanan Terakhir</h5>
                                        <div class="riwayat-card">
                                            <?php if (!empty($riwayat_layanan)): ?>
                                                <?php foreach ($riwayat_layanan as $riwayat): ?>
                                                    <div class="riwayat-item">
                                                        <p class="mb-0 small text-dark fw-bold">
                                                            <i class="bi bi-calendar-check me-1"></i> <?php echo date('d M Y', strtotime($riwayat['tgl_waktu_layanan'])); ?>
                                                            <span class="float-end badge bg-info"><?php echo htmlspecialchars($riwayat['nama_poli']); ?></span>
                                                        </p>
                                                        <p class="mb-1 small text-muted">
                                                            **Diagnosa:** <span class="text-dark"><?php echo htmlspecialchars($riwayat['diagnosa']); ?></span>
                                                        </p>
                                                        <p class="mb-0 small text-secondary">
                                                            Dokter: <?php echo htmlspecialchars($riwayat['nama_dokter']); ?>
                                                        </p>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted text-center small mt-3">Belum ada riwayat layanan medis tercatat.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>

                                <!-- Form Input Layanan -->
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                    <input type="hidden" name="layanan_id" value="<?php echo $layanan_id; ?>">
                                    <input type="hidden" name="antrian_id" value="<?php echo $antrian_id; ?>">

                                    <div class="mb-3">
                                        <label for="diagnosa" class="form-label">Diagnosa <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="diagnosa" name="diagnosa" rows="5" required placeholder="Tuliskan diagnosa medis pasien..."><?php echo htmlspecialchars($layanan_data['diagnosa'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tindakan" class="form-label">Tindakan/Resep</label>
                                        <textarea class="form-control" id="tindakan" name="tindakan" rows="5" placeholder="Tuliskan tindakan yang diberikan atau resep obat..."><?php echo htmlspecialchars($layanan_data['tindakan'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> btn-lg">
                                            <i class="bi bi-save me-2"></i>
                                            <?php echo $mode; ?> Layanan & 
                                            <?php echo $mode == 'Input' ? 'Selesaikan Antrian' : 'Update Catatan'; ?>
                                        </button>
                                    </div>
                                    
                                    <?php if ($mode === 'Edit'): ?>
                                        <p class="text-muted text-center mt-3 small">* Mode edit tidak mengubah status antrian yang sudah **Selesai**.</p>
                                    <?php endif; ?>
                                </form>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ⬇️ Footer -->
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <!-- Link Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php mysqli_close($conn); ?>