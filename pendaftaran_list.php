<?php
session_start();

// Autentikasi dan Cek Role
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin atau Front Office yang diizinkan)
$allowed_roles = ['Super Admin', 'Front Office'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="pasien_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

// --- MENU ITEMS UNTUK NAV BAR ---
$menu_items = [
    [ 'title' => 'Daftar Pasien', 'icon' => 'bi-people-fill', 'link' => 'pasien_list.php' ],
    [ 'title' => 'Manajemen Pendaftaran', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'link' => 'pendaftaran_list.php' ],
    [ 'title' => 'Pemanggilan Antrian', 'icon' => 'bi-telephone-fill', 'link' => 'antrian_call.php' ],
    [ 'title' => 'Laporan Pendaftaran', 'icon' => 'bi-bar-chart-fill', 'link' => 'report.php' ],
];
// --- END MENU ITEMS ---

$error_message = "";
$total_pendaftaran = 0; // Variabel baru untuk menyimpan total

// =========================================================================
// LOGIKA HAPUS DENGAN PERBAIKAN FOREIGN KEY
// =========================================================================
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // LANGKAH 1: Hapus entri di tabel 'antrian' yang terkait dengan pendaftaran ini
    $sql_del_antrian = "DELETE FROM antrian WHERE pendaftaran_id = $delete_id";
    
    if (mysqli_query($conn, $sql_del_antrian)) {
        // LANGKAH 2: Hapus dari tabel pendaftaran (hanya jika antrian berhasil dihapus)
        $sql_del_pendaftaran = "DELETE FROM pendaftaran WHERE pendaftaran_id = $delete_id";
        
        if (mysqli_query($conn, $sql_del_pendaftaran)) {
            header("Location: pendaftaran_list.php?status_updated=data_dihapus");
            exit();
        } else {
            // Ini adalah fallback error jika langkah 2 gagal
            $error_message = "Gagal menghapus data pendaftaran utama: " . mysqli_error($conn);
        }
    } else {
        // Ini adalah error jika langkah 1 gagal (seharusnya jarang terjadi)
        $error_message = "Gagal menghapus antrian terkait: " . mysqli_error($conn);
    }
}
// =========================================================================


// Logika Verifikasi/Update Status Pendaftaran (Tetap dipertahankan)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = escape_input($conn, $_GET['action']);
    $id = (int)$_GET['id'];
    $new_status = "";

    if ($action === 'verify') {
        $new_status = 'Terverifikasi';
    } elseif ($action === 'cancel') {
        $new_status = 'Dibatalkan';
    }
    
    if (!empty($new_status)) {
        
        // 1. Ambil data pendaftaran (pasien_id, poli_id, tgl_rencana_periksa)
        $sql_get_data = "SELECT pasien_id, poli_id, tgl_rencana_periksa FROM pendaftaran WHERE pendaftaran_id = $id";
        $res_data = mysqli_query($conn, $sql_get_data);
        $data = mysqli_fetch_assoc($res_data);
        
        $antrian_success = true;
        
        if ($data && $new_status === 'Terverifikasi') {
            $tgl_layanan = $data['tgl_rencana_periksa'];
            $poli_id = $data['poli_id'];
            $pendaftaran_id = $id;

            // 2. Hitung Nomor Antrian Terakhir untuk Poli dan Tanggal tersebut
            $sql_max_antrian = "SELECT MAX(CAST(SUBSTRING(nomor_antrian, 2) AS SIGNED)) AS max_num 
                                FROM antrian 
                                WHERE poli_id = $poli_id AND tgl_layanan = '$tgl_layanan'";
            
            $res_max = mysqli_query($conn, $sql_max_antrian);
            $max_num = mysqli_fetch_assoc($res_max)['max_num'];
            
            $next_num = $max_num ? $max_num + 1 : 1;
            $nomor_antrian_baru = "A" . str_pad($next_num, 3, '0', STR_PAD_LEFT); // Cth: A001, A002

            // 3. Masukkan entri baru ke tabel antrian
            $sql_insert_antrian = "INSERT INTO antrian (pendaftaran_id, poli_id, tgl_layanan, nomor_antrian, status_antrian)
                                   VALUES ($pendaftaran_id, $poli_id, '$tgl_layanan', '$nomor_antrian_baru', 'Menunggu')";
            
            if (!mysqli_query($conn, $sql_insert_antrian)) {
                $error_message = "Gagal membuat antrian: " . mysqli_error($conn);
                $antrian_success = false;
            }
        }
        
        // 4. Update status pendaftaran utama (Hanya jika tidak ada error antrian)
        if (empty($error_message) || !$antrian_success) {
            $sql_update = "UPDATE pendaftaran SET status_pendaftaran = '$new_status' WHERE pendaftaran_id = $id";
            if (mysqli_query($conn, $sql_update)) {
                $status_redir = strtolower(str_replace(' ', '_', $new_status));
                // Tambahkan nomor antrian ke pesan sukses jika berhasil diverifikasi
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pendaftaran Pasien | RS Jiwa</title>
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
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0">📋 Daftar Pendaftaran Pasien</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php elseif (isset($_GET['status_updated'])): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <?php 
                                $status_text = ucwords(str_replace('_', ' ', $_GET['status_updated']));
                                if ($status_text == 'Data Dihapus') {
                                    echo "Data pendaftaran berhasil dihapus!";
                                } else {
                                    echo "Status berhasil diubah menjadi **$status_text**!";
                                }
                            ?>
                            <?php if (isset($_GET['antrian_no'])): ?>
                                <br>Nomor Antrian: **<span class="text-danger"><?php echo htmlspecialchars($_GET['antrian_no']); ?></span>**
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted">Data Registrasi dan Antrian</h5>
                        <a href="pendaftaran_form.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-plus me-1" viewBox="0 0 16 16">
                                <path d="M8 6.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 .5-.5"/>
                                <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3.5-1v1h3.5L12 3.5h-1zM4 11V3a1 1 0 0 1 1-1h5.5v2h1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/>
                            </svg>
                            Input Pendaftaran Baru
                        </a>
                    </div>

                    <?php
                    // Query dengan JOIN untuk menampilkan nama pasien dan nama poli
                    $sql = "SELECT p.*, s.nama_lengkap AS nama_pasien, s.no_rekam_medis, o.nama_poli 
                            FROM pendaftaran p
                            JOIN pasien s ON p.pasien_id = s.pasien_id
                            JOIN poli o ON p.poli_id = o.poli_id
                            ORDER BY p.tgl_waktu_input DESC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        // **SIMPAN JUMLAH BARIS SEBELUM MEMPROSES DAN MEMBEBASKAN HASIL**
                        $total_pendaftaran = mysqli_num_rows($result);
                        
                        // --- DEFINISI FUNGSI DIPINDAHKAN KE LUAR LOOP ---
                        // Helper function untuk Badge Status
                        function get_status_badge($status) {
                            switch ($status) {
                                case 'Terverifikasi': return '<span class="badge bg-success">Terverifikasi</span>';
                                case 'Menunggu Verifikasi': return '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>';
                                case 'Dibatalkan': return '<span class="badge bg-danger">Dibatalkan</span>';
                                case 'Selesai': return '<span class="badge bg-primary">Selesai</span>';
                                default: return '<span class="badge bg-secondary">' . $status . '</span>';
                            }
                        }
                        // --- END DEFINISI FUNGSI ---

                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped table-hover align-middle">';
                        echo '<thead class="table-dark"><tr><th>ID</th><th>Pasien (RM)</th><th>Poli</th><th>Tgl Rencana</th><th>Jenis</th><th>Status</th><th>Tgl Input</th><th class="text-center">Aksi</th></tr></thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            
                            echo "<tr>";
                            echo "<td>" . $row['pendaftaran_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_pasien']) . " (<small class='text-muted'>" . htmlspecialchars($row['no_rekam_medis'] ?? '-') . "</small>)</td>";
                            echo "<td>" . htmlspecialchars($row['nama_poli']) . "</td>";
                            echo "<td>" . $row['tgl_rencana_periksa'] . "</td>";
                            echo "<td><span class='badge bg-light text-dark border'>" . $row['jenis_pendaftaran'] . "</span></td>";
                            echo "<td>" . get_status_badge($row['status_pendaftaran']) . "</td>";
                            echo "<td>" . date('d/m/y H:i', strtotime($row['tgl_waktu_input'])) . "</td>";
                            echo '<td class="text-center text-nowrap">';
                            
                            // Tombol Edit
                            echo "<a href='pendaftaran_form.php?id=" . $row['pendaftaran_id'] . "' class='btn btn-sm btn-info text-white me-1'>Edit</a>";
                            
                            // Logika Tombol Verifikasi/Pembatalan
                            if ($row['status_pendaftaran'] === 'Menunggu Verifikasi') {
                                echo "<a href='pendaftaran_list.php?action=verify&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Verifikasi pendaftaran ini dan buat antrian?\")' class='btn btn-sm btn-success me-1'>Verifikasi</a>";
                                echo "<a href='pendaftaran_list.php?action=cancel&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Batalkan pendaftaran ini?\")' class='btn btn-sm btn-danger me-1'>Batalkan</a>";
                            } elseif ($row['status_pendaftaran'] === 'Terverifikasi') {
                                // Hanya Batalkan
                                echo "<a href='pendaftaran_list.php?action=cancel&id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Batalkan pendaftaran ini?\")' class='btn btn-sm btn-danger me-1'>Batalkan</a>";
                            } else {
                                echo "<span class='text-muted'>Aksi Selesai</span>";
                            }
                            
                            // Tombol Hapus (selalu tersedia jika belum diproses, atau untuk admin)
                            // Menggunakan script PHP yang diperbaiki (dengan penghapusan antrian terkait)
                            echo "<a href='pendaftaran_list.php?delete_id=" . $row['pendaftaran_id'] . "' onclick='return confirm(\"Yakin hapus pendaftaran ini secara permanen? Antrian terkait juga akan dihapus.\")' class='btn btn-sm btn-secondary ms-1'>Hapus</a>";

                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // Tutup table-responsive

                        // Bebaskan hasil setelah loop selesai
                        mysqli_free_result($result);

                    } else {
                        echo '<div class="alert alert-info text-center" role="alert">Belum ada data pendaftaran.</div>';
                    }

                    mysqli_close($conn);
                    ?>

                </div>
                <div class="card-footer text-muted text-end">
                    Total: <?php echo $total_pendaftaran; ?> Pendaftaran
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