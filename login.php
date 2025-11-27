<?php
// Mulai sesi
session_start();

// Include file konfigurasi
include "koneksi.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil dan bersihkan input
    // Menggunakan mysqli_real_escape_string untuk keamanan dasar
    $username = isset($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {
        // 2. Query ke database untuk mencari user aktif
        $sql = "SELECT admin_id, password_hash, nama_lengkap, role FROM admin WHERE username = '$username' AND status_aktif = 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // 3. Verifikasi password
            if (password_verify($password, $user['password_hash'])) {
                // Login berhasil: Set variabel sesi
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $user['admin_id'];
                $_SESSION['username']        = $username;
                $_SESSION['nama_lengkap']    = $user['nama_lengkap'];
                $_SESSION['role']            = $user['role'];

                // =========================================================
                // LOGIKA REDIRECT BERDASARKAN ROLE (TERMASUK DOKTER)
                // =========================================================
                if ($user['role'] === 'Super Admin') {
                    header("Location: superadmin_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Front Office') {
                    header("Location: frontoffice_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'Dokter') {
                    // PENTING: Redirect Dokter ke Dashboard Dokter
                    header("Location: dokter_dashboard.php"); 
                    exit();
                } else {
                    // Fallback jika role tidak dikenali
                    header("Location: dashboard.php"); 
                    exit();
                }
            } else {
                $error_message = "Password yang Anda masukkan salah.";
            }
        } else {
            $error_message = "Username tidak ditemukan atau akun belum diaktifkan.";
        }
    } else {
        $error_message = "Mohon isi username dan password.";
    }
}

// Tutup koneksi
if (isset($conn)) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Informasi RS Jiwa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #7c4dff; /* Ungu Super Admin */
            --primary-hover: #651fff;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --main-font: 'Poppins', sans-serif;
            --heading-font: 'Montserrat', sans-serif;
        }

        /* Struktur Flexbox untuk Sticky Footer */
        body {
            font-family: var(--main-font);
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Glassmorphism */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px 0;
        }

        .navbar-brand {
            font-family: var(--heading-font);
            font-size: 1.5rem;
            color: #333 !important;
            letter-spacing: -0.5px;
        }

        /* Wrapper konten agar berada di tengah vertikal */
        .content-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 80px; /* Kompensasi fixed navbar yang lebih tinggi */
            padding-bottom: 20px;
        }

        h1, h2, h3, h4, h5 { font-family: var(--heading-font); }

        /* Card Styles */
        .card-login {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            background-color: white;
        }

        .login-header {
            background: linear-gradient(45deg, #7c4dff, #5345b8);
            color: white;
            padding: 40px 30px;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            margin-bottom: -20px;
        }

        .login-icon {
            font-size: 3.5rem;
            margin-bottom: 10px;
            display: inline-block;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        /* Form Styles */
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(124, 77, 255, 0.25);
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 77, 255, 0.4);
        }

        .input-group-text {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
            color: var(--primary-color);
        }
        
        .form-control { border-left: none; }

        /* Footer Style */
        .footer {
            background: rgba(255, 255, 255, 0.8);
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem 0;
            margin-top: auto;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center mx-auto mx-md-0" href="#">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm me-2" style="width: 40px; height: 40px;">
                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                </div>
                <span style="background: linear-gradient(45deg, #333, #555); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">RS Jiwa GraSHia</span>
            </a>
            </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5 col-lg-4">
                    
                    <div class="card card-login">
                        <div class="login-header">
                            <i class="bi bi-hospital-fill login-icon"></i>
                            <h3 class="fw-bold mb-0"> RS Jiwa GraSHia</h3>
                            <p class="small opacity-75 mb-0">Portal Login Admin, Dokter & Staff</p>
                        </div>

                        <div class="card-body p-4 pt-5">
                            
                            <div class="text-center mb-4">
                                <h5 class="text-muted fw-bold">Selamat Datang</h5>
                                <p class="text-muted small">Silakan masuk dengan akun Anda</p>
                            </div>

                            <?php if ($error_message): ?>
                                <div class="alert alert-danger shadow-sm rounded-3 border-0 d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                                    <div><?php echo $error_message; ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-bold text-secondary small">USERNAME</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username..." autocomplete="username">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-bold text-secondary small">PASSWORD</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password..." autocomplete="current-password">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-primary btn-login text-white">
                                        MASUK SEKARANG <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                                    </button>
                                </div>

                                <div class="text-center">
                                    <a href="#" class="text-decoration-none small text-muted hover-underline">Lupa Password? Hubungi IT Support</a>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <span class="text-muted small">&copy; <?php echo date("Y"); ?> RS Jiwa GraSHia. Hak Cipta Dilindungi.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>