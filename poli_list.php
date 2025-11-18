<?php
session_start();

// Autentikasi dan Cek Role (hanya Super Admin yang diizinkan mengelola poli)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role
if ($_SESSION['role'] !== 'Super Admin') {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><div class="card p-5 shadow-lg"><h3 class="text-danger">Akses Ditolak 🛑</h3><p>Maaf, hanya **Super Admin** yang diizinkan mengakses halaman ini.</p><a href="admin_list.php" class="btn btn-primary">Kembali</a></div></body></html>';
    exit();
}

include "koneksi.php";

// Definisikan variabel sesi untuk Navbar
$nama_lengkap_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$role_admin = htmlspecialchars($_SESSION['role'] ?? 'Guest');

$error_message = "";

// Logika Hapus
if (isset($_GET['delete_id'])) {
    $poli_id_del = (int)$_GET['delete_id'];
    
    $sql_del = "DELETE FROM poli WHERE poli_id = $poli_id_del";
    
    if (mysqli_query($conn, $sql_del)) {
        header("Location: poli_list.php?success=delete");
        exit();
    } else {
        $error_message = "Gagal menghapus poli. Mungkin sudah terhubung dengan data lain: " . mysqli_error($conn);
    }
}

// Logika Tambah/Edit (Inline)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // BARIS KRITIS SUDAH DIBERSIHKAN DARI KARAKTER ANEH
    $poli_id_post = (int)$_POST['poli_id'];
    $nama_poli = escape_input($conn, $_POST['nama_poli']);
    $deskripsi = escape_input($conn, $_POST['deskripsi']);

    if ($poli_id_post == 0) { // Tambah
        $sql = "INSERT INTO poli (nama_poli, deskripsi) VALUES ('$nama_poli', '$deskripsi')";
    } else { // Edit
        $sql = "UPDATE poli SET nama_poli='$nama_poli', deskripsi='$deskripsi' WHERE poli_id = $poli_id_post";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: poli_list.php?success=" . ($poli_id_post == 0 ? 'add' : 'edit'));
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
    <title>Daftar Poli | RS Jiwa</title>
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
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="poli_list.php">
                RS Jiwa - Manajemen Poli
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
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-lg">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0" id="form-title">➕ Tambah Poli Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <input type="hidden" name="poli_id" id="poli_id" value="0">
                                
                                <div class="mb-3">
                                    <label for="nama_poli" class="form-label">Nama Poli <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_poli" name="nama_poli" required placeholder="Cth: Poli Anak">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Penjelasan singkat tentang layanan poli"></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="submit-button">
                                        Simpan Poli
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Batal/Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-lg">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">📜 Daftar Poli Aktif</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $sql = "SELECT poli_id, nama_poli, deskripsi FROM poli ORDER BY poli_id ASC";
                            $result = mysqli_query($conn, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered table-striped table-hover align-middle">';
                                echo '<thead class="table-dark"><tr><th>ID</th><th>Nama Poli</th><th>Deskripsi</th><th class="text-center">Aksi</th></tr></thead>';
                                echo '<tbody>';
                                
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['poli_id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_poli']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['deskripsi'] ?? '-') . "</td>";
                                    echo '<td class="text-center">';
                                    
                                    // Tombol Edit
                                    echo "<button type='button' class='btn btn-sm btn-warning me-2' onclick='editPoli(" . $row['poli_id'] . ", \"" . addslashes(htmlspecialchars($row['nama_poli'])) . "\", \"" . addslashes(htmlspecialchars($row['deskripsi'])) . "\")'>Edit</button>";
                                    
                                    // Tombol Hapus
                                    echo "<a href='poli_list.php?delete_id=" . $row['poli_id'] . "' onclick='return confirm(\"Apakah Anda yakin ingin menghapus Poli: " . addslashes(htmlspecialchars($row['nama_poli'])) . "?\")' class='btn btn-sm btn-danger'>Hapus</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info text-center" role="alert">Belum ada data poli yang terdaftar.</div>';
                            }

                            if (isset($result)) {
                                mysqli_free_result($result);
                            }
                            mysqli_close($conn);
                            ?>
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

    <script>
    // Fungsi untuk mengisi form saat Edit
    function editPoli(id, nama, deskripsi) {
        document.getElementById('poli_id').value = id;
        document.getElementById('nama_poli').value = nama;
        document.getElementById('deskripsi').value = deskripsi;
        
        // Perubahan tampilan
        document.getElementById('form-title').innerHTML = '✍️ Edit Poli (ID: ' + id + ')';
        document.getElementById('submit-button').innerHTML = 'Update Poli';
        document.getElementById('submit-button').classList.remove('btn-primary');
        document.getElementById('submit-button').classList.add('btn-warning');
        
        // Scroll ke atas agar form terlihat
        window.scrollTo(0, 0);
    }
    
    // Fungsi untuk mereset form
    function resetForm() {
        document.getElementById('poli_id').value = 0;
        document.getElementById('nama_poli').value = '';
        document.getElementById('deskripsi').value = '';
        
        // Reset tampilan
        document.getElementById('form-title').innerHTML = '➕ Tambah Poli Baru';
        document.getElementById('submit-button').innerHTML = 'Simpan Poli';
        document.getElementById('submit-button').classList.remove('btn-warning');
        document.getElementById('submit-button').classList.add('btn-primary');
    }
    </script>
</body>
</html>