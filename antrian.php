<?php
// Panggil koneksi database
include "koneksi.php";

// Tetapkan tanggal hari ini untuk filtering
$tgl_hari_ini = date('Y-m-d'); 

// Fungsi untuk mendapatkan antrian yang sedang dipanggil dan jumlah yang menunggu
function get_antrian_info($koneksi, $tgl_hari_ini) {
    
    $data_antrian = [];
    
    // Query Utama: Ambil Antrian Dipanggil dan Hitung Jumlah Menunggu HARI INI
    $query = "
        SELECT 
            A.poli_tujuan, 
            -- Ambil nomor antrian tertinggi yang statusnya 'Dipanggil'
            MAX(CASE WHEN A.status = 'Dipanggil' THEN A.no_antrian ELSE NULL END) AS antrian_terakhir_dipanggil,
            -- Hitung jumlah pasien yang masih 'Menunggu'
            SUM(CASE WHEN A.status = 'Menunggu' THEN 1 ELSE 0 END) AS jumlah_menunggu
        FROM antrian A
        WHERE A.tgl_kunjungan = '$tgl_hari_ini'
        GROUP BY A.poli_tujuan
    ";

    $result = mysqli_query($koneksi, $query);

    if (!$result) {
        die("Gagal mengambil data antrian: " . mysqli_error($koneksi));
    }

    // Mengumpulkan data hasil query
    while ($row = mysqli_fetch_assoc($result)) {
        // Logika untuk menampilkan status yang lebih user-friendly
        if ($row['antrian_terakhir_dipanggil'] === NULL && $row['jumlah_menunggu'] > 0) {
            $row['antrian_terakhir_dipanggil'] = 'Siap Panggil 1'; // Ada yang menunggu, tapi belum ada yang dipanggil
        } elseif ($row['antrian_terakhir_dipanggil'] === NULL && $row['jumlah_menunggu'] == 0) {
            $row['antrian_terakhir_dipanggil'] = 'Tutup';
        }
        $data_antrian[] = $row;
    }

    // Jika tidak ada data antrian yang didaftarkan hari ini sama sekali, tampilkan dummy
    if (empty($data_antrian)) {
        $data_antrian = [
            ['poli_tujuan' => 'Poli Jiwa Dewasa', 'antrian_terakhir_dipanggil' => 'Tutup', 'jumlah_menunggu' => 0],
            ['poli_tujuan' => 'Poli Kesehatan Anak & Remaja', 'antrian_terakhir_dipanggil' => 'Tutup', 'jumlah_menunggu' => 0],
            ['poli_tujuan' => 'Poli Psikoterapi & Konsultasi', 'antrian_terakhir_dipanggil' => 'Tutup', 'jumlah_menunggu' => 0],
        ];
    }
    
    return $data_antrian;
}

$data_antrian = get_antrian_info($koneksi, $tgl_hari_ini);

// Daftar poliklinik yang digunakan untuk styling
$poli_colors = [
    'Poli Jiwa Dewasa' => 'bg-info-subtle border-info',
    'Poli Kesehatan Anak & Remaja' => 'bg-warning-subtle border-warning',
    'Poli Psikoterapi & Konsultasi' => 'bg-success-subtle border-success',
    'Poli Geriatri (Lansia)' => 'bg-danger-subtle border-danger',
    'Poli Gangguan Tidur & Cemas' => 'bg-primary-subtle border-primary',
    'Poli Rehabilitasi Mental' => 'bg-secondary-subtle border-secondary'
];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Antrian Real-Time - RS Jiwa Kenangan</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <meta http-equiv="refresh" content="30"> 
    <style>
        body {
            background-color: #f7fafc;
            font-family: 'Nunito Sans', sans-serif;
            color: #2d2d2d;
        }

        .navbar {
            background-color: #fff;
            padding: 14px 0;
            border-bottom: 4px solid #b3e5fc;
        }

        .navbar-brand h1 {
            font-size: 26px;
            font-weight: 700;
            color: #c44d3e;
            margin: 0;
        }

        .header-antrian {
            background: linear-gradient(135deg, #009999, #00b3b3);
            color: white;
            padding: 40px 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .card-antrian {
            border: 2px solid; 
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .card-antrian:hover {
            transform: translateY(-5px);
        }

        .antrian-number {
            font-size: 3.5rem;
            font-weight: 800;
            color: #c44d3e; 
            line-height: 1;
        }
        
        .poli-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #007a7a; 
        }

        footer {
            background: #2d3947;
            color: #ccc;
            padding: 20px;
            font-size: 14px;
            margin-top: 50px;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="rsj.php">
                <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ="
                    alt="Logo RS Jiwa Kenangan" width="60" height="60" class="rounded-3 me-2">
                <div>
                    <h1 class="mb-0">RS Jiwa Kenangan</h1>
                    <small>Jl. Kesumayudha No.29, Bangli</small>
                </div>
            </a>
        </div>
    </nav>

    <div class="header-antrian">
        <h1>🏥 Informasi Antrian Klinik</h1>
        <p class="lead">Data diperbarui setiap 30 detik | Terakhir diperbarui: <?php echo date("H:i:s"); ?></p>
    </div>

    <div class="container mb-5">
        <div class="row g-4 justify-content-center">
            <?php if (!empty($data_antrian)): ?>
                <?php foreach ($data_antrian as $antrian): 
                    $color_class = $poli_colors[$antrian['poli_tujuan']] ?? 'bg-light border-secondary';
                ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-antrian p-3 <?php echo $color_class; ?>">
                        <div class="card-body text-center">
                            <p class="poli-title mb-3"><?php echo htmlspecialchars($antrian['poli_tujuan']); ?></p>
                            <h4 class="mb-2 text-secondary">Antrian Saat Ini:</h4>
                            <div class="antrian-number"><?php echo htmlspecialchars($antrian['antrian_terakhir_dipanggil']); ?></div>
                            <p class="mt-3 fs-5 fw-semibold">
                                <span class="badge text-bg-secondary me-1"><?php echo htmlspecialchars($antrian['jumlah_menunggu']); ?></span> pasien menunggu
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-warning" role="alert">
                        Tidak ada data antrian yang tersedia saat ini.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-5 text-center">
             <a href="pendaftaran.php" class="btn btn-theme btn-lg">Daftar Antrian Online Sekarang</a>
        </div>
    </div>
    
    <footer>
        <p class="mb-0">© 2025 RS Jiwa Kenangan — Semua Hak Dilindungi</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    </body>

</html>