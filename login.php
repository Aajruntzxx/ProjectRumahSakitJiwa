<?php
// Mulai sesi
session_start();

// Include file konfigurasi
// Pastikan file koneksi.php berisi koneksi ke DB ($conn) dan fungsi escape_input()
include "koneksi.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil dan bersihkan input
    $username = isset($_POST['username']) ? escape_input($conn, $_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {
        // 2. Query ke database
        // **CATATAN KEAMANAN KRITIS: Selalu gunakan Prepared Statements di produksi!**
        $sql = "SELECT admin_id, password_hash, nama_lengkap, role FROM admin WHERE username = '$username' AND status_aktif = 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // 3. Verifikasi password
            if (password_verify($password, $user['password_hash'])) {
                // Login berhasil
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $user['admin_id'];
                $_SESSION['username']        = $username;
                $_SESSION['nama_lengkap']    = $user['nama_lengkap'];
                $_SESSION['role']            = $user['role'];

                // =========================================================
                // LOGIKA REDIRECT BERDASARKAN ROLE
                // =========================================================
                if ($user['role'] === 'Super Admin') {
                    // Redirect Super Admin ke dashboard baru
                    header("Location: superadmin_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Front Office') {
                    // Redirect Front Office ke dashboard FO
                    header("Location: frontoffice_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Dokter') {
                    // Redirect Dokter ke dashboard kerja mereka
                    header("Location: dokter_dashboard.php"); 
                    exit();
                } else {
                    // Fallback (jika ada role lain yang tidak terdefinisi)
                    header("Location: dashboard.php");
                    exit();
                }
                // =========================================================

            } else {
                $error_message = "Username atau password salah.";
            }
        } else {
            $error_message = "Username atau password salah, atau akun tidak aktif.";
        }
    } else {
        $error_message = "Mohon isi username dan password.";
    }
}

// Tutup koneksi setelah selesai
if (isset($conn)) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin RS Jiwa | Modern</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content-wrapper {
            flex: 1; 
            display: flex;
            align-items: center; 
            justify-content: center; 
            padding-top: 56px; 
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shield-lock-fill me-2 text-danger"></i>
                RS Jiwa Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Bantuan</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8 col-sm-10">
                    <div class="card shadow-lg p-4">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4 text-primary">🔑 Login Admin</h3>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger text-center" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username" placeholder="Masukkan Username">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">🔒</span>
                                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan Password">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right me-2" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1H6.5v2h5a.5.5 0 0 1 0 1H6.5v2h5a.5.5 0 0 1 0 1H6.5v2h5a.5.5 0 0 1 0 1H6.5a.5.5 0 0 1-.5-.5v-12z"/>
                                            <path fill-rule="evenodd" d="M11 8a.5.5 0 0 1-.5.5H3a.5.5 0 0 1 0-1h7.5a.5.5 0 0 1 .5.5z"/>
                                        </svg>
                                        Login
                                    </button>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="#" class="text-decoration-none">Lupa Password?</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">&copy; <?php echo date("Y"); ?> RS Jiwa. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>