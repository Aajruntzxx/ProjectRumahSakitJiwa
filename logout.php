<?php
// 1. Mulai sesi
session_start();

// 2. Hapus semua variabel sesi
$_SESSION = array();

// 3. Hancurkan sesi (menghapus data sesi dari server)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirect ke halaman login.php
header("Location: login.php");
exit();
?>