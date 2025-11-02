<?php
session_start();
$pesan_error = "";

// Data Login Sederhana (Hanya untuk keperluan contoh!)
$valid_username = "staf_rsj";
$valid_password = "password123"; // Ganti dengan hash di produksi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_panggilan.php");
        exit();
    } else {
        $pesan_error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RS Jiwa Kenangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f7fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-box { max-width: 400px; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-theme { background-color: #00796b; color: white; border: none; }
        .btn-theme:hover { background-color: #005f54; }
    </style>
</head>
<body>
    <div class="login-box w-100">
        <h2 class="text-center text-primary mb-4">Admin Login RSJ</h2>
        
        <?php if ($pesan_error): ?>
            <div class="alert alert-danger"><?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-theme">Login</button>
            </div>
        </form>
    </div>
</body>
</html>