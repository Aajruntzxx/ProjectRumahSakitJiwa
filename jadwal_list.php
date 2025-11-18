<?php
session_start();

// Autentikasi dan Cek Role
// Asumsi: Hanya Super Admin yang diizinkan mengelola jadwal
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin yang diizinkan untuk data jadwal)
if ($_SESSION['role'] !== 'Super Admin') {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="admin_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

$error_message = "";

// Logika Hapus Jadwal
if (isset($_GET['delete_id'])) {
    $jadwal_id_del = (int)$_GET['delete_id'];
    $sql_del = "DELETE FROM jadwal_praktik WHERE jadwal_id = $jadwal_id_del";
    
    if (mysqli_query($conn, $sql_del)) {
        header("Location: jadwal_list.php?success=delete");
        exit();
    } else {
        $error_message = "Gagal menghapus jadwal: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jadwal Praktik | RS Jiwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
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
        <div class="container-fluid">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">🗓️ Daftar Jadwal Praktik Dokter</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted">Jadwal Pelayanan Klinik</h5>
                        <a href="jadwal_form.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-plus me-1" viewBox="0 0 16 16">
                                <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                            Tambah Jadwal Baru
                        </a>
                    </div>

                    <?php
                    // Query untuk mengambil jadwal beserta nama dokter dan nama poli
                    $sql = "SELECT jp.*, d.nama_lengkap AS nama_dokter, p.nama_poli 
                            FROM jadwal_praktik jp
                            JOIN dokter d ON jp.dokter_id = d.dokter_id
                            JOIN poli p ON jp.poli_id = p.poli_id
                            ORDER BY FIELD(jp.hari_praktik, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai ASC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        // Menggunakan table Bootstrap (table-striped, table-hover, responsive)
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped table-hover align-middle">';
                        echo '<thead class="table-dark"><tr><th>Dokter</th><th>Poli</th><th>Hari</th><th>Jam Mulai</th><th>Jam Selesai</th><th class="text-center">Aksi</th></tr></thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            // Format Waktu
                            $jam_mulai = substr($row['jam_mulai'], 0, 5); // HH:MM
                            $jam_selesai = substr($row['jam_selesai'], 0, 5); // HH:MM

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['nama_dokter']) . "</td>";
                            echo "<td><span class='badge bg-info text-dark'>" . htmlspecialchars($row['nama_poli']) . "</span></td>";
                            echo "<td>" . $row['hari_praktik'] . "</td>";
                            echo "<td>" . $jam_mulai . "</td>";
                            echo "<td>" . $jam_selesai . "</td>";
                            echo '<td class="text-center">';
                            
                            // Tombol Edit
                            echo "<a href='jadwal_form.php?id=" . $row['jadwal_id'] . "' class='btn btn-sm btn-warning text-dark me-2'>Edit</a>";
                            
                            // Tombol Hapus
                            echo "<a href='jadwal_list.php?delete_id=" . $row['jadwal_id'] . "' onclick='return confirm(\"Yakin hapus jadwal " . htmlspecialchars($row['nama_dokter']) . " pada hari " . $row['hari_praktik'] . "?\")' class='btn btn-sm btn-danger'>Hapus</a>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // Tutup table-responsive
                    } else {
                        echo '<div class="alert alert-info text-center" role="alert">Belum ada jadwal praktik yang terdaftar.</div>';
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