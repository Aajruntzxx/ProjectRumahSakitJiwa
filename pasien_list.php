<?php
session_start();

// Cek Otentikasi dan Role
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Front Office dan Super Admin yang diizinkan untuk data pasien)
$allowed_roles = ['Super Admin', 'Front Office'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="admin_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}


include "koneksi.php"; // Include koneksi database

// Variabel sesi untuk Navbar
$nama_lengkap = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role = htmlspecialchars($_SESSION['role'] ?? 'Guest');

// --- MENU ITEMS UNTUK NAV BAR (DIAMBIL DARI FRONTOFFICE DASHBOARD) ---
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pasien | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa; /* Warna latar belakang ringan */
            padding-top: 56px; /* Offset untuk navbar fixed top */
        }
        .content-wrapper {
            flex: 1;
            padding-top: 20px;
            padding-bottom: 20px;
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
                        <span class="nav-link text-warning">Halo, **<?php echo $nama_lengkap; ?>** (<?php echo $role; ?>)</span>
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
            <div class="card shadow-lg">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0">👨‍⚕️ Daftar Pasien Terdaftar</h3>
                </div>
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted">Data Registrasi Pasien</h5>
                        <a href="pasien_form.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill me-1" viewBox="0 0 16 16">
                                <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                <path fill-rule="evenodd" d="M12.5 6a.5.5 0 0 1 .5.5V8h1.5a.5.5 0 0 1 0 1H13v1.5a.5.5 0 0 1-1 0V9h-1.5a.5.5 0 0 1 0-1H12V6.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                            Tambah Pasien Baru
                        </a>
                    </div>

                    <?php
                    // Query untuk mengambil semua data pasien
                    $sql = "SELECT pasien_id, no_rekam_medis, nik, nama_lengkap, tgl_lahir, jenis_kelamin, no_hp, tgl_daftar FROM pasien ORDER BY tgl_daftar DESC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        // Menggunakan table Bootstrap (table-striped, table-hover, responsive)
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped table-hover align-middle">';
                        echo '<thead class="table-dark"><tr><th>No. RM</th><th>NIK</th><th>Nama Lengkap</th><th>Tgl Lahir</th><th>JK</th><th>No HP</th><th>Tgl Daftar</th><th class="text-center">Aksi</th></tr></thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            
                            // PERBAIKAN: Membandingkan dengan string penuh 'Laki-laki'
                            $jk_label = ($row['jenis_kelamin'] == 'Laki-laki') 
                                ? '<span class="badge bg-primary">Laki-laki</span>' 
                                : '<span class="badge bg-danger">Perempuan</span>';

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['no_rekam_medis'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['nik']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                            echo "<td>" . $row['tgl_lahir'] . "</td>";
                            echo "<td>" . $jk_label . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_hp'] ?? '-') . "</td>";
                            echo "<td>" . $row['tgl_daftar'] . "</td>";
                            echo '<td class="text-center">';
                            
                            // Tombol Edit
                            echo "<a href='pasien_form.php?id=" . $row['pasien_id'] . "' class='btn btn-sm btn-warning text-dark'>Edit</a>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // Tutup table-responsive
                    } else {
                        echo '<div class="alert alert-info text-center" role="alert">Belum ada data pasien yang terdaftar.</div>';
                    }

                    // Bebaskan hasil dan tutup koneksi
                    if (isset($result)) {
                        mysqli_free_result($result);
                    }
                    mysqli_close($conn);
                    ?>

                </div>
                <div class="card-footer text-muted text-end">
                    Data diambil dari database per <?php echo date("Y-m-d H:i:s"); ?>
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