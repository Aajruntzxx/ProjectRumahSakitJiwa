<?php
// Mulai sesi dan cek otentikasi
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin yang bisa melihat/mengelola daftar admin)
if ($_SESSION['role'] !== 'Super Admin') {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a></div></body></html>';
    exit();
}

// Include file konfigurasi
include "koneksi.php";

// Inisialisasi variabel untuk menyimpan total baris
$total_rows = 0;

// Query untuk mengambil semua data admin
$sql = "SELECT admin_id, username, nama_lengkap, role, status_aktif FROM admin ORDER BY admin_id DESC";
$result = mysqli_query($conn, $sql);

// Cek dan simpan jumlah baris
if ($result) {
    $total_rows = mysqli_num_rows($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna Admin | RS Jiwa</title>
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
                RS Jiwa Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-warning">Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?> (<?php echo $_SESSION['role']; ?>)</span>
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
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"> Daftar Pengguna Admin</h3>
                </div>
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted">Manajemen Akun Admin</h5>
                        <a href="admin_form.php" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle me-1" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                            </svg>
                            Tambah Admin Baru
                        </a>
                    </div>

                    <?php
                    // Pastikan $result valid dan memiliki baris untuk ditampilkan
                    if ($result && $total_rows > 0) {
                        // Menggunakan table Bootstrap (table-striped, table-hover, responsive)
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-bordered table-striped table-hover align-middle">';
                        echo '<thead class="table-dark"><tr><th>ID</th><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>';
                        echo '<tbody>';
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            // Tentukan Badge untuk Status
                            $status_badge = $row['status_aktif'] 
                                ? '<span class="badge bg-success">Aktif</span>' 
                                : '<span class="badge bg-danger">Nonaktif</span>';

                            echo "<tr>";
                            echo "<td>" . $row['admin_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                            echo "<td>" . $row['role'] . "</td>";
                            echo "<td>" . $status_badge . "</td>";
                            echo '<td class="text-center">';
                            
                            // Tombol Edit
                            echo "<a href='admin_form.php?id=" . $row['admin_id'] . "' class='btn btn-sm btn-info text-white me-2'>Edit</a>";
                            
                            // Tombol Hapus (dengan konfirmasi JavaScript)
                            echo "<a href='admin_delete.php?id=" . $row['admin_id'] . "' onclick='return confirm(\"Apakah Anda yakin ingin menghapus akun admin \\\"" . htmlspecialchars($row['username']) . "\\\"?\")' class='btn btn-sm btn-danger'>Hapus</a>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>'; // Tutup table-responsive
                    } else {
                        echo '<div class="alert alert-info text-center" role="alert">Belum ada data admin yang tersedia.</div>';
                    }

                    // Bebaskan hasil dan tutup koneksi
                    // Ini dipindahkan ke sini, setelah semua penggunaan $result selesai
                    if ($result) {
                        mysqli_free_result($result);
                    }
                    mysqli_close($conn);
                    ?>

                </div>
                <div class="card-footer text-muted text-end">
                    Total: <?php echo $total_rows; ?> Pengguna
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