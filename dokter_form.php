<?php
session_start();

// Autentikasi dan Cek Role (hanya Super Admin yang bisa mengelola data dokter)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'Super Admin') {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="dokter_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

$dokter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dokter_data = [];
$error_message = "";
$mode = $dokter_id > 0 ? 'Edit' : 'Tambah';

// Ambil daftar user Admin dengan role Dokter yang belum terhubung
$admin_list = [];
$sql_admin = "SELECT admin_id, username FROM admin WHERE role='Dokter' AND admin_id NOT IN (SELECT admin_id FROM dokter WHERE admin_id IS NOT NULL)";
$result_admin = mysqli_query($conn, $sql_admin);
if ($result_admin) {
    while($row = mysqli_fetch_assoc($result_admin)) {
        $admin_list[] = $row;
    }
}


if ($mode === 'Edit') {
    $sql = "SELECT * FROM dokter WHERE dokter_id = $dokter_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) == 1) {
        $dokter_data = mysqli_fetch_assoc($result);
        // Jika sedang edit, masukkan user yang terhubung saat ini ke daftar pilihan
        if ($dokter_data['admin_id']) {
            $sql_current_admin = "SELECT admin_id, username FROM admin WHERE admin_id = " . $dokter_data['admin_id'];
            $res_current = mysqli_query($conn, $sql_current_admin);
            if ($row_current = mysqli_fetch_assoc($res_current)) {
                
                // Pastikan admin_id saat ini tidak terduplikasi jika sudah ada di $admin_list
                $is_exist = false;
                foreach ($admin_list as $admin) {
                    if ($admin['admin_id'] == $row_current['admin_id']) {
                        $is_exist = true;
                        break;
                    }
                }
                if (!$is_exist) {
                    $admin_list[] = $row_current;
                }
            }
        }
    } else {
        $error_message = "Data Dokter tidak ditemukan.";
        $dokter_id = 0;
        $mode = 'Tambah';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post         = (int)$_POST['dokter_id'];
    $admin_id        = (int)$_POST['admin_id'];
    $nama_lengkap    = escape_input($conn, $_POST['nama_lengkap']);
    $spesialisasi    = escape_input($conn, $_POST['spesialisasi']);
    $no_str          = escape_input($conn, $_POST['no_str']);
    $no_telepon      = escape_input($conn, $_POST['no_telepon']);
    $status_aktif    = isset($_POST['status_aktif']) ? 1 : 0;

    // Set admin_id menjadi NULL jika nilainya 0 (tidak memilih akun)
    $admin_id_db = ($admin_id > 0) ? $admin_id : 'NULL';

    if ($id_post == 0) { // Tambah
        $sql = "INSERT INTO dokter (admin_id, nama_lengkap, spesialisasi, no_str, no_telepon, status_aktif) 
                VALUES ($admin_id_db, '$nama_lengkap', '$spesialisasi', '$no_str', '$no_telepon', $status_aktif)";
    } else { // Edit
        $sql = "UPDATE dokter SET 
                admin_id=$admin_id_db, nama_lengkap='$nama_lengkap', spesialisasi='$spesialisasi', 
                no_str='$no_str', no_telepon='$no_telepon', status_aktif=$status_aktif
                WHERE dokter_id = $id_post";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: dokter_list.php?success=" . strtolower($mode));
        exit();
    } else {
        $error_message = "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Data Dokter | RS Jiwa</title>
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
            <a class="navbar-brand" href="dokter_list.php">
                RS Jiwa - Manajemen Dokter
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
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow-lg">
                        <div class="card-header bg-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> text-white">
                            <h3 class="mb-0">📝 <?php echo $mode; ?> Data Dokter</h3>
                        </div>
                        <div class="card-body">

                            <a href="dokter_list.php" class="btn btn-sm btn-outline-secondary mb-4">
                                &larr; Kembali ke Daftar Dokter
                            </a>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="dokter_id" value="<?php echo $dokter_id; ?>">

                                <div class="mb-4">
                                    <label for="admin_id" class="form-label">User Akun Dokter (Role 'Dokter')</label>
                                    <select name="admin_id" id="admin_id" class="form-select">
                                        <option value="0">-- Tidak Terhubung ke Akun --</option>
                                        <?php 
                                            // Urutkan admin_list berdasarkan username agar rapi di tampilan
                                            usort($admin_list, function($a, $b) {
                                                return strcmp($a['username'], $b['username']);
                                            });
                                            foreach($admin_list as $admin): 
                                        ?>
                                            <option value="<?php echo $admin['admin_id']; ?>" 
                                                <?php echo ($dokter_data['admin_id'] ?? '') == $admin['admin_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Hanya akun dengan role 'Dokter' yang tersedia dan belum terhubung.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($dokter_data['nama_lengkap'] ?? ''); ?>" required placeholder="Cth: Dr. Siti Aminah">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="spesialisasi" class="form-label">Spesialisasi <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="spesialisasi" name="spesialisasi" value="<?php echo htmlspecialchars($dokter_data['spesialisasi'] ?? ''); ?>" required placeholder="Cth: Psikiatri Anak">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="no_str" class="form-label">No. STR</label>
                                        <input type="text" class="form-control" id="no_str" name="no_str" value="<?php echo htmlspecialchars($dokter_data['no_str'] ?? ''); ?>" placeholder="Nomor Surat Tanda Registrasi">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="no_telepon" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($dokter_data['no_telepon'] ?? ''); ?>" placeholder="Cth: 08xx-xxxx-xxxx">
                                </div>

                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="status_aktif" name="status_aktif" value="1" <?php echo ($dokter_data['status_aktif'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_aktif">Status Aktif</label>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-<?php echo $mode == 'Edit' ? 'warning' : 'success'; ?> btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save me-2" viewBox="0 0 16 16">
                                            <path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6h-1v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h7v-1z"/>
                                            <path d="M8.5 1.5A1.5 1.5 0 0 1 10 0h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2z"/>
                                        </svg>
                                        <?php echo $mode; ?> Data Dokter
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

<?php mysqli_close($conn); ?>