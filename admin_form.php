<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (hanya Super Admin yang bisa mengakses form ini)
if ($_SESSION['role'] !== 'Super Admin') {
    // Memberikan pesan error yang lebih rapi
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="admin_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php"; // Ganti "koneksi.php" menjadi "config.php" agar konsisten

$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$admin_data = null;
$error_message = "";
$mode = $admin_id > 0 ? 'Edit' : 'Tambah';

// Logika Ambil Data (untuk mode Edit)
if ($mode === 'Edit') {
    // **CATATAN KEAMANAN**: Penggunaan Prepared Statements sangat disarankan
    $sql = "SELECT admin_id, username, nama_lengkap, role, status_aktif FROM admin WHERE admin_id = $admin_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $admin_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data Admin tidak ditemukan.";
        $mode = 'Tambah'; // Ganti mode jika data tidak ditemukan
        $admin_id = 0;
    }
}

// Logika POST (Simpan Data)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan input
    $admin_id_post = (int)$_POST['admin_id'];
    // Asumsi fungsi escape_input sudah ada di config.php
    $username       = escape_input($conn, $_POST['username']);
    $nama_lengkap   = escape_input($conn, $_POST['nama_lengkap']);
    $role           = escape_input($conn, $_POST['role']);
    $status_aktif   = isset($_POST['status_aktif']) ? 1 : 0;
    $password       = $_POST['password']; // Tidak perlu escape_input karena akan di-hash

    if ($admin_id_post == 0) { // Tambah Data Baru
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO admin (username, password_hash, nama_lengkap, role, status_aktif) VALUES ('$username', '$password_hash', '$nama_lengkap', '$role', $status_aktif)";
            if (mysqli_query($conn, $sql_insert)) {
                header("Location: admin_list.php?success=add");
                exit();
            } else {
                $error_message = "Gagal menambah data: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Password harus diisi untuk admin baru.";
        }
    } else { // Edit Data
        $sql_update = "UPDATE admin SET username='$username', nama_lengkap='$nama_lengkap', role='$role', status_aktif=$status_aktif";
        
        // Hanya update password jika kolom password diisi
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash='$password_hash'";
        }
        
        $sql_update .= " WHERE admin_id = $admin_id_post";
        
        if (mysqli_query($conn, $sql_update)) {
            // Jika admin yang diedit adalah diri sendiri, update variabel sesi (role, nama)
            if ($_SESSION['admin_id'] == $admin_id_post) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['role'] = $role;
            }
            header("Location: admin_list.php?success=edit");
            exit();
        } else {
            $error_message = "Gagal mengupdate data: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Pengguna Admin | RS Jiwa</title>
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
            align-items: flex-start; /* Konten di mulai dari atas */
            justify-content: center;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="admin_list.php">
                RS Jiwa Admin Panel
            </a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
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
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> text-white">
                            <h3 class="mb-0">📝 <?php echo $mode; ?> Pengguna Admin</h3>
                        </div>
                        <div class="card-body">

                            <a href="admin_list.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Daftar Admin
                            </a>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required placeholder="Masukkan Username">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        Password 
                                        <?php if ($mode == 'Edit'): ?>
                                            <small class="text-muted">(Kosongkan jika tidak ingin diubah)</small>
                                        <?php else: ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" <?php echo $mode == 'Tambah' ? 'required' : ''; ?> placeholder="Masukkan Password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($admin_data['nama_lengkap'] ?? ''); ?>" required placeholder="Masukkan Nama Lengkap">
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select id="role" name="role" class="form-select" required>
                                        <?php 
                                            // Ambil nilai role saat ini (jika ada) atau default kosong
                                            $current_role = $admin_data['role'] ?? ''; 
                                        ?>
                                        <option value="">-- Pilih Role --</option>
                                        <option value="Super Admin" <?php echo $current_role == 'Super Admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        <option value="Front Office" <?php echo $current_role == 'Front Office' ? 'selected' : ''; ?>>Front Office</option>
                                        <option value="Dokter" <?php echo $current_role == 'Dokter' ? 'selected' : ''; ?>>Dokter</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="status_aktif" name="status_aktif" value="1" <?php echo ($admin_data['status_aktif'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_aktif">Status Aktif</label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save me-2" viewBox="0 0 16 16">
                                            <path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6h-1v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h7v-1z"/>
                                            <path d="M8.5 1.5A1.5 1.5 0 0 1 10 0h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2z"/>
                                        </svg>
                                        <?php echo $mode; ?> Admin
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
mysqli_close($conn); // Tutup koneksi setelah selesai
?>