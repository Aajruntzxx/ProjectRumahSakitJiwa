<?php
session_start();

// --- KONEKSI DATABASE ---
include "koneksi.php"; // Menggunakan file koneksi eksternal yang sudah dibuat
// -----------------------

// Fungsi Helper untuk membersihkan input (Asumsi fungsi escape_input sudah didefinisikan)
if (!function_exists('escape_input')) {
    function escape_input($conn, $data) {
        // Melindungi dari SQL Injection
        return mysqli_real_escape_string($conn, $data);
    }
}
// Helper function (Perlu dipertahankan karena digunakan di HTML)
function get_status_badge_antrian($status) {
    switch ($status) {
        case 'Dipanggil': return '<span class="badge bg-warning text-dark">Dipanggil</span>';
        case 'Sedang Periksa': return '<span class="badge bg-primary">Sedang Periksa</span>';
        case 'Menunggu': return '<span class="badge bg-info">Menunggu</span>';
        case 'Selesai': return '<span class="badge bg-success">Selesai</span>';
        case 'Tidak Hadir': return '<span class="badge bg-danger">Tidak Hadir</span>';
        default: return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}


// 1. Autentikasi dan Cek Role
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['Super Admin', 'Front Office', 'Dokter'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . htmlspecialchars($_SESSION['role'] ?? 'Guest') . '**), tidak diizinkan mengakses halaman ini.</p><a href="login.php" class="btn btn-primary">Halaman Login</a></div></body></html>';
    // Penting: Tutup koneksi jika dieksekusi di sini
    if (isset($conn)) mysqli_close($conn); 
    exit();
}

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

// --- MENU ITEMS UNTUK NAV BAR (Contoh Navigasi Cepat) ---
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---

$error_message = "";
$success_message = "";

// Ambil daftar Poli untuk filter (Tetap digunakan untuk tampilan filter)
$poli_list = [];
$poli_map = []; // Untuk mapping ID ke Nama Poli
$sql_poli = "SELECT poli_id, nama_poli FROM poli ORDER BY nama_poli";
$result_poli = mysqli_query($conn, $sql_poli);
if ($result_poli) {
    while($row = mysqli_fetch_assoc($result_poli)) {
        $poli_list[] = $row;
        $poli_map[$row['poli_id']] = $row['nama_poli'];
    }
    mysqli_free_result($result_poli);
}

// Logika Filter (Jika tidak ada filter poli_id, tampilkan semua, yang akan dikelompokkan di bawah)
$poli_id_filter = 0; 
if (isset($_GET['poli_id'])) {
    $poli_id_filter = (int)$_GET['poli_id'];
}

// Logika Aksi Panggilan
if (isset($_GET['action']) && isset($_GET['antrian_id'])) {
    $action = escape_input($conn, $_GET['action']);
    $antrian_id = (int)$_GET['antrian_id'];
    $new_status = "";
    $update_time = "";

    if ($action === 'call') {
        $new_status = 'Dipanggil';
        $update_time = ", waktu_dipanggil = NOW()";
    } elseif ($action === 'finish') {
        $new_status = 'Selesai';
    } elseif ($action === 'skip') {
        $new_status = 'Tidak Hadir';
    } elseif ($action === 'serve') {
        $new_status = 'Sedang Periksa';
    }

    if (!empty($new_status)) {
        // Query menggunakan variabel $conn
        $sql_update = "UPDATE antrian SET status_antrian = '$new_status' $update_time WHERE antrian_id = $antrian_id";
        if (mysqli_query($conn, $sql_update)) {
            $success_message = "Status antrian **$new_status** berhasil diperbarui.";
            
            // --- PERBAIKAN: Logika Redirect berdasarkan Role ---
            if ($role_admin === 'Dokter') {
                // Redirect Dokter kembali ke Dashboard mereka
                header("Location: dokter_dashboard.php?success_msg=" . urlencode($success_message));
                exit();
            } else {
                // Redirect Super Admin/Front Office kembali ke halaman antrian_call dengan filter poli
                header("Location: antrian_call.php?poli_id=" . $poli_id_filter . "&success_msg=" . urlencode($success_message));
                exit();
            }
            // --- END PERBAIKAN ---

        } else {
            $error_message = "Gagal mengubah status antrian: " . mysqli_error($conn);
        }
    }
}

// Logika Tampilkan Pesan Sukses dari URL
if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars($_GET['success_msg']);
}


// Base Query untuk mengambil Antrian hari ini
$sql_antrian = "SELECT a.*, p.nama_poli, p.poli_id, s.nama_lengkap AS nama_pasien, s.no_rekam_medis 
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                JOIN pasien s ON pf.pasien_id = s.pasien_id
                WHERE a.tgl_layanan = CURDATE()";

if ($poli_id_filter > 0) {
    // Jika ada filter, batasi hasilnya
    $sql_antrian .= " AND a.poli_id = $poli_id_filter";
}

// Urutan prioritas: status, lalu poli, lalu antrian_id
$sql_antrian .= " ORDER BY 
                  FIELD(a.status_antrian, 'Dipanggil', 'Sedang Periksa', 'Menunggu', 'Selesai', 'Tidak Hadir'),
                  p.nama_poli ASC,
                  a.antrian_id ASC";
                  
$result_antrian = mysqli_query($conn, $sql_antrian);

// Array baru untuk menampung antrian yang dikelompokkan
$grouped_antrian = [];
$total_antrian = 0;

if ($result_antrian) {
    while($row = mysqli_fetch_assoc($result_antrian)) {
        $total_antrian++;
        $poli_id = $row['poli_id'];
        
        // Kelompokkan berdasarkan poli_id
        if (!isset($grouped_antrian[$poli_id])) {
            $grouped_antrian[$poli_id] = [
                'nama_poli' => $row['nama_poli'],
                'antrian' => []
            ];
        }
        $grouped_antrian[$poli_id]['antrian'][] = $row;
    }
    mysqli_free_result($result_antrian);
}

// Tutup koneksi sebelum output HTML
if (isset($conn)) mysqli_close($conn); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Pemanggilan Antrian | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
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
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .nav-link.active-menu {
            border-bottom: 3px solid #ffc107; 
            font-weight: bold;
        }
        /* Style untuk highlight baris berdasarkan status */
        .status-Dipanggil { background-color: #fff3cd; font-weight: bold; } /* Kuning Muda */
        .status-SedangPeriksa { background-color: #d1ecf1; font-weight: bold; } /* Biru Muda */
        .status-Selesai { background-color: #d4edda; } /* Hijau Muda */
        .status-Menunggu { background-color: #ffffff; } /* Putih */
        .status-TidakHadir { background-color: #f8d7da; } /* Merah Muda */
        .poli-card {
            margin-bottom: 30px;
            border-left: 5px solid #0d6efd; /* Border biru di kiri */
        }
        .poli-header {
            background-color: #e9ecef;
            padding: 15px;
            margin-bottom: 0;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-pulse-fill me-2 text-primary"></i>**Panel Admin**
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavFO" aria-controls="navbarNavFO" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavFO">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
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
            
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">📞 Interface Pemanggilan Antrian Pasien</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="get" action="antrian_call.php" class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label for="poli_id" class="form-label">Filter Poli:</label>
                            </div>
                            <div class="col-md-6">
                                <select name="poli_id" id="poli_id" class="form-select" onchange="this.form.submit()">
                                    <option value="0">-- Semua Poli --</option>
                                    <?php foreach($poli_list as $poli): ?>
                                        <option value="<?php echo $poli['poli_id']; ?>" 
                                            <?php echo $poli_id_filter == $poli['poli_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($poli['nama_poli']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <a href="antrian_call.php" class="btn btn-outline-secondary">Reset Filter</a>
                            </div>
                        </div>
                    </form>
                    <h5 class="mt-4 mb-3">Antrian Hari Ini (<?php echo date('d F Y'); ?>)</h5>
                    
                    <?php if (!empty($grouped_antrian)): ?>
                        
                        <?php foreach($grouped_antrian as $poli_id => $data_poli): ?>
                            
                            <div class="card poli-card shadow-sm">
                                <h4 class="poli-header text-primary fw-bold">
                                    <i class="bi bi-hospital me-2"></i> Poli: <?php echo htmlspecialchars($data_poli['nama_poli']); ?>
                                    <span class="badge bg-primary float-end"><?php echo count($data_poli['antrian']); ?> Antrian</span>
                                </h4>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 10%;">No. Antrian</th>
                                                <th style="width: 25%;">Pasien (RM)</th>
                                                <th style="width: 15%;">Status</th>
                                                <th style="width: 15%;">Waktu Panggil</th>
                                                <th style="width: 35%;" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($data_poli['antrian'] as $row): ?>
                                                <?php 
                                                    $status_class = str_replace(' ', '', $row['status_antrian']);
                                                    $rm_display = htmlspecialchars($row['no_rekam_medis'] ?? '-');
                                                ?>
                                                <tr class="status-<?php echo $status_class; ?>">
                                                    <td><h5 class="mb-0 text-primary">**<?php echo htmlspecialchars($row['nomor_antrian']); ?>**</h5></td>
                                                    <td><?php echo htmlspecialchars($row['nama_pasien']); ?> (<small class='text-muted'><?php echo $rm_display; ?></small>)</td>
                                                    <td><?php echo get_status_badge_antrian($row['status_antrian']); ?></td>
                                                    <td><?php echo $row['waktu_dipanggil'] ? date('H:i', strtotime($row['waktu_dipanggil'])) : '-'; ?></td>
                                                    <td class="text-center text-nowrap">
                                                        <?php if ($row['status_antrian'] === 'Menunggu'): ?>
                                                            <a href="antrian_call.php?action=call&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-sm btn-success me-1">Panggil</a>
                                                            <a href="antrian_call.php?action=skip&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" onclick="return confirm('Tandai sebagai Tidak Hadir?')" class="btn btn-sm btn-danger">Skip</a>
                                                        
                                                        <?php elseif ($row['status_antrian'] === 'Dipanggil'): ?>
                                                            <a href="antrian_call.php?action=serve&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-sm btn-primary">Sedang Periksa</a>
                                                            <a href="antrian_call.php?action=call&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-sm btn-outline-secondary ms-1">Ulangi Panggil</a>
                                                        
                                                        <?php elseif ($row['status_antrian'] === 'Sedang Periksa'): ?>
                                                            <a href="antrian_call.php?action=finish&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" onclick="return confirm('Yakin ingin menyelesaikan antrian ini?')" class="btn btn-sm btn-secondary">Selesaikan Antrian</a>
                                                            <a href="antrian_call.php?action=call&antrian_id=<?php echo $row['antrian_id']; ?>&poli_id=<?php echo $poli_id_filter; ?>" class="btn btn-sm btn-outline-warning ms-1">Ulangi Panggil</a>
                                                        
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <div class="alert alert-info text-center mt-3">
                            Tidak ada data antrian yang sesuai untuk hari ini.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted text-end">
                    Total: <?php echo $total_antrian; ?> Antrian
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-dark">
        <div class="container text-center">
            <span class="text-white">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>