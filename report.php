<?php
session_start();

// Autentikasi dan Cek Role (Asumsi: Hanya Admin/Super Admin yang boleh akses laporan)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['Super Admin', 'Front Office']; // Tambahkan role yang diizinkan
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="dashboard.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php"; // Ganti "koneksi.php" jika nama file koneksi Anda berbeda

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Super Admin');

// --- HELPER FUNCTION: DIPINDAHKAN DI SINI UNTUK MENGHINDARI FATAL ERROR REDECLARATION ---
// Helper function untuk Badge Status
function get_report_status_badge($status) {
    switch ($status) {
        case 'Terverifikasi': return '<span class="badge bg-success">Terverifikasi</span>';
        case 'Menunggu Verifikasi': return '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>';
        case 'Dibatalkan': return '<span class="badge bg-danger">Dibatalkan</span>';
        case 'Selesai': return '<span class="badge bg-primary">Selesai</span>';
        default: return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}
// --- END HELPER FUNCTION ---

// --- MENU ITEMS UNTUK NAV BAR (DIAMBIL DARI FRONTOFFICE DASHBOARD) ---
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---

// Default tanggal
// Catatan: Asumsi fungsi `escape_input` sudah didefinisikan dalam `koneksi.php` atau file lain yang di-include.
$tgl_mulai = isset($_POST['tgl_mulai']) ? escape_input($conn, $_POST['tgl_mulai']) : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) ? escape_input($conn, $_POST['tgl_akhir']) : date('Y-m-d');
$report_result = null;
$total_rows = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Query Laporan Pendaftaran berdasarkan tanggal rencana periksa
    $sql_report = "SELECT 
                      p.tgl_rencana_periksa, p.jenis_pendaftaran, p.status_pendaftaran, p.tgl_waktu_input,
                      s.nama_lengkap AS nama_pasien, s.no_rekam_medis, 
                      o.nama_poli
                    FROM pendaftaran p
                    JOIN pasien s ON p.pasien_id = s.pasien_id
                    JOIN poli o ON p.poli_id = o.poli_id
                    WHERE p.tgl_rencana_periksa BETWEEN '$tgl_mulai' AND '$tgl_akhir'
                    ORDER BY p.tgl_rencana_periksa ASC, p.tgl_waktu_input ASC";
    
    $report_result = mysqli_query($conn, $sql_report);
    if ($report_result) {
        $total_rows = mysqli_num_rows($report_result);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendaftaran Pasien | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        <div class="container-fluid">
            
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-4">
                &larr; Kembali ke Dashboard
            </a>

            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">📑 Laporan Detail Pendaftaran Pasien</h3>
                </div>
                <div class="card-body">
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="tgl_mulai" class="form-label">Tanggal Mulai Rencana Periksa</label>
                            <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="tgl_akhir" class="form-label">Tanggal Akhir Rencana Periksa</label>
                            <input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter-square me-2" viewBox="0 0 16 16">
                                    <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                    <path d="m6.646 11.854.646-.646A.5.5 0 0 0 8 11.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 0 .146.354zM3.5 5h9a.5.5 0 0 0 0-1h-9a.5.5 0 0 0 0 1z"/>
                                </svg>
                                Tampilkan Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div class="card shadow-lg">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Hasil Laporan (<?php echo date('d M Y', strtotime($tgl_mulai)); ?> s/d <?php echo date('d M Y', strtotime($tgl_akhir)); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($report_result && $total_rows > 0): ?>
                            <p class="text-success fw-bold">Ditemukan **<?php echo $total_rows; ?>** data pendaftaran.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle" style="width: 100%;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tgl Rencana</th>
                                            <th>Pasien (RM)</th>
                                            <th>Poli</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                            <th>Waktu Input</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($report_result)): ?>
                                            
                                            <!-- Panggilan fungsi yang telah didefinisikan di luar loop -->
                                            <tr>
                                                <td><?php echo $row['tgl_rencana_periksa']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pasien']); ?> (<small class='text-muted'><?php echo htmlspecialchars($row['no_rekam_medis'] ?? '-'); ?></small>)</td>
                                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['nama_poli']); ?></span></td>
                                                <td><?php echo $row['jenis_pendaftaran']; ?></td>
                                                <td><?php echo get_report_status_badge($row['status_pendaftaran']); ?></td>
                                                <td><?php echo date('d/m/y H:i', strtotime($row['tgl_waktu_input'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php 
                            if ($report_result) {
                                mysqli_free_result($report_result);
                            }
                            ?>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                Tidak ditemukan data pendaftaran dalam rentang tanggal tersebut.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted text-end">
                        Total Data: <?php echo $total_rows; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Silakan pilih rentang tanggal dan klik **"Tampilkan Laporan"** di atas untuk melihat data pendaftaran.
                </div>
            <?php endif; ?>

        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php mysqli_close($conn); ?>