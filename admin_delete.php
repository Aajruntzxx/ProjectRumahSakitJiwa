<?php
session_start();
include "koneksi.php";

// --- 1. CEK KEAMANAN (Sama seperti file utama) ---

// Cek Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Cek Role (Hanya Super Admin yang boleh menghapus)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki izin untuk menghapus data.');
            window.location='admin_list.php';
          </script>";
    exit();
}

// --- 2. PROSES HAPUS DATA ---

if (isset($_GET['id'])) {
    // Ambil ID dari URL dan amankan input
    $id = intval($_GET['id']);

    // (Opsional) Cek apakah admin mencoba menghapus dirinya sendiri
    // Asumsi: $_SESSION['admin_id'] diset saat login. Jika tidak, bagian ini bisa dilewati/dihapus.
    if (isset($_SESSION['admin_id']) && $id == $_SESSION['admin_id']) {
        echo "<script>
            alert('Gagal! Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
            window.location='admin_list.php';
          </script>";
        exit();
    }

    // Query Hapus Data
    $sql = "DELETE FROM admin WHERE admin_id = ?";
    
    // Gunakan Prepared Statement untuk keamanan (mencegah SQL Injection)
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        // Jika Berhasil
        echo "<script>
                alert('Data admin berhasil dihapus!');
                window.location='admin_list.php';
              </script>";
    } else {
        // Jika Gagal
        echo "<script>
                alert('Gagal menghapus data: " . mysqli_error($conn) . "');
                window.location='admin_list.php';
              </script>";
    }

    mysqli_stmt_close($stmt);

} else {
    // Jika file diakses langsung tanpa ID
    header("Location: admin_list.php");
}

mysqli_close($conn);
?>