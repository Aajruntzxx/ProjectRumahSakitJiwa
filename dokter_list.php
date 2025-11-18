<?php
session_start();

// Autentikasi dan Cek Role
// Asumsi: Hanya Super Admin yang diizinkan mengelola data dokter
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin yang diizinkan untuk data dokter)
if ($_SESSION['role'] !== 'Super Admin') {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, peran Anda (**' . $_SESSION['role'] . '**), tidak diizinkan mengakses halaman ini.</p><a href="admin_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Dokter | RS Jiwa</title>
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
                RS Jiwa - Data Dokter
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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
                    <h3 class="mb-0">👨‍⚕️ Daftar Dokter Aktif</h3>
                </div>
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted">Manajemen Data Dokter</h5>
                        <a href="dokter_form.php" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill me-1" viewBox="0 0 16 16">
                                <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                <path fill-rule="evenodd" d="M12.5 6a.5.5 0 0 1 .5.5V8h1.5a.5.5 0 0 1 0 1H13v1.5a.5.5 0 0 1-1 0V9h-1.5a.5.5 0 0 1 0-1H12V6.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                            Tambah Dokter Baru
                        </a>
                    </div>

                    <?php
                    // Query data dokter dengan join ke tabel admin
                    $sql = "SELECT d.*, a.username 
                            FROM dokter d 
                            LEFT JOIN admin a ON d.admin_id = a.admin_id
                            ORDER BY d.nama_lengkap ASC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        // Menggunakan table Bootstrap (table-striped, table-hover, responsive)
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped table-hover align-middle">';
                        echo '<thead class="table-dark"><tr><th>Nama Dokter</th><th>Spesialisasi</th><th>No. STR</th><th>No. Telepon</th><th>Status</th><th>User Akun</th><th class="text-center">Aksi</th></tr></thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            // Tentukan Badge untuk Status Aktif
                            $status_badge = $row['status_aktif'] 
                                ? '<span class="badge bg-success">Aktif</span>' 
                                : '<span class="badge bg-danger">Nonaktif</span>';

                            // Tentukan Badge untuk User Akun
                            $user_akun = htmlspecialchars($row['username'] ?? 'Belum ada');
                            $user_badge = $row['username']
                                ? '<span class="badge bg-info text-dark">' . $user_akun . '</span>'
                                : '<span class="badge bg-secondary">' . $user_akun . '</span>';
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['spesialisasi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_str'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_telepon'] ?? '-') . "</td>";
                            echo "<td>" . $status_badge . "</td>";
                            echo "<td>" . $user_badge . "</td>";
                            echo '<td class="text-center">';
                            
                            // Tombol Edit
                            echo "<a href='dokter_form.php?id=" . $row['dokter_id'] . "' class='btn btn-sm btn-warning text-dark'>Edit</a>";
                            
                            // Tambahan: Link hapus (jika diperlukan)
                            // echo "<a href='dokter_delete.php?id=" . $row['dokter_id'] . "' onclick='return confirm(\"Yakin hapus?\")' class='btn btn-sm btn-danger ms-2'>Hapus</a>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // Tutup table-responsive
                    } else {
                        echo '<div class="alert alert-info text-center" role="alert">Belum ada data dokter yang terdaftar.</div>';
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