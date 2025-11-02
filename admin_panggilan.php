<?php
session_start();
include "koneksi.php";

// Autentikasi Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// 📌 PERBAIKAN: Definisikan array poli_prefixes agar tidak Undefined
$poli_prefixes = [
    "Poli Jiwa Dewasa" => "J",
    "Poli Kesehatan Anak & Remaja" => "A",
    "Poli Geriatri (Lansia)" => "G",
    "Poli Psikoterapi & Konsultasi" => "P",
    "Poli Gangguan Tidur & Cemas" => "T",
    "Poli Rehabilitasi Mental" => "R"
];

$pesan_status = "";
$tgl_hari_ini = date('Y-m-d');

// --- Logika Pemanggilan Antrian (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_antrian = mysqli_real_escape_string($koneksi, $_POST['id_antrian']);
    $poli_tujuan = mysqli_real_escape_string($koneksi, $_POST['poli_tujuan']);

    $status_baru = '';
    
    if ($action == 'panggil') {
        $status_baru = 'Dipanggil';
    } elseif ($action == 'selesai') {
        $status_baru = 'Selesai';
    }

    if ($status_baru) {
        // Query untuk merubah status antrian yang dipilih
        $sql_update = "UPDATE antrian SET status = '$status_baru' WHERE id_antrian = '$id_antrian'";
        
        if (mysqli_query($koneksi, $sql_update)) {
            $pesan_status = "Status antrian " . ($status_baru == 'Dipanggil' ? "berhasil dipanggil!" : "selesai!");
            
            // Logika Tambahan: Jika memanggil antrian baru, 
            // set antrian sebelumnya di poli yang sama menjadi 'Selesai'
            if ($status_baru == 'Dipanggil') {
                $sql_selesai_sebelumnya = "
                    UPDATE antrian 
                    SET status = 'Selesai' 
                    WHERE poli_tujuan = '$poli_tujuan' 
                    AND status = 'Dipanggil' 
                    AND id_antrian != '$id_antrian'
                    AND tgl_kunjungan = '$tgl_hari_ini'";
                mysqli_query($koneksi, $sql_selesai_sebelumnya);
            }
        } else {
            $pesan_status = "Gagal memperbarui status: " . mysqli_error($koneksi);
        }
    }
}

// --- Query Utama: Mengambil daftar antrian hari ini yang belum Selesai/Batal ---
$sql_list = "
    SELECT 
        A.id_antrian, A.no_antrian, P.nama, A.poli_tujuan, A.status
    FROM antrian A
    JOIN pasien P ON A.no_rm = P.no_rm
    WHERE A.tgl_kunjungan = '$tgl_hari_ini'
    AND A.status IN ('Menunggu', 'Dipanggil') 
    ORDER BY A.no_antrian ASC
";
$result_list = mysqli_query($koneksi, $sql_list);

// Mengelompokkan antrian berdasarkan poli
$antrian_per_poli = [];
$antrian_dipanggil = [];

if ($result_list) {
    while ($row = mysqli_fetch_assoc($result_list)) {
        if ($row['status'] == 'Dipanggil') {
            $antrian_dipanggil[$row['poli_tujuan']] = $row;
        } else {
            // Antrian Menunggu
            $antrian_per_poli[$row['poli_tujuan']][] = $row;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panggilan Antrian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f7fafc; font-family: 'Nunito Sans', sans-serif; }
        .navbar { background-color: #00796b; color: white; }
        .card-panggilan { border-left: 5px solid #c44d3e; }
        .btn-panggil { background-color: #009999; color: white; }
        .btn-panggil:hover { background-color: #007a7a; color: white; }
        .btn-selesai { background-color: #c44d3e; color: white; }
        .btn-selesai:hover { background-color: #a03c30; color: white; }
        .antrian-sekarang { font-size: 2rem; font-weight: bold; color: #00796b; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container">
            <a class="navbar-brand text-white" href="#">Admin RSJ Kenangan</a>
            <span class="navbar-text text-white me-3">Antrian Hari Ini: **<?php echo date('d F Y', strtotime($tgl_hari_ini)); ?>**</span>
            <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4 text-primary">Panel Kontrol Panggilan Antrian</h2>

        <?php if ($pesan_status): ?>
            <div class="alert alert-info"><?php echo $pesan_status; ?></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <?php foreach ($poli_prefixes as $poli => $prefix): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-panggilan h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-success fw-bold"><?php echo htmlspecialchars($poli); ?></h5>
                            
                            <p class="text-muted mb-2">Sedang Dipanggil:</p>
                            <div class="antrian-sekarang mb-3">
                                <?php 
                                $current_antrian = $antrian_dipanggil[$poli] ?? null;
                                echo htmlspecialchars($current_antrian['no_antrian'] ?? '---'); 
                                ?>
                            </div>
                            
                            <?php 
                            $menunggu_count = count($antrian_per_poli[$poli] ?? []);
                            echo "<h6 class='text-danger'>Pasien Menunggu: **{$menunggu_count}**</h6>";
                            
                            // Ambil antrian berikutnya yang statusnya 'Menunggu'
                            $next_antrian = $antrian_per_poli[$poli][0] ?? null;
                            ?>

                            <?php if ($next_antrian): ?>
                                <p class="mb-1 mt-3">Antrian Selanjutnya:</p>
                                <span class="badge text-bg-warning fs-5 me-2"><?php echo $next_antrian['no_antrian']; ?></span>
                                <span>(<?php echo htmlspecialchars($next_antrian['nama']); ?>)</span>
                                
                                <form method="POST" class="d-grid gap-2 mt-3">
                                    <input type="hidden" name="id_antrian" value="<?php echo $next_antrian['id_antrian']; ?>">
                                    <input type="hidden" name="poli_tujuan" value="<?php echo $next_antrian['poli_tujuan']; ?>">
                                    <button type="submit" name="action" value="panggil" class="btn btn-panggil">Panggil Antrian</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-light text-center mt-3">Tidak ada antrian menunggu.</div>
                            <?php endif; ?>

                            <?php if ($current_antrian): ?>
                                <form method="POST" class="d-grid gap-2 mt-2">
                                    <input type="hidden" name="id_antrian" value="<?php echo $current_antrian['id_antrian']; ?>">
                                    <input type="hidden" name="poli_tujuan" value="<?php echo $current_antrian['poli_tujuan']; ?>">
                                    <button type="submit" name="action" value="selesai" class="btn btn-selesai">Selesaikan Antrian</button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>