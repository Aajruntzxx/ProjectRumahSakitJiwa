<?php
// Pastikan file koneksi.php tersedia
include "koneksi.php";

// Set zona waktu (Opsional, tapi disarankan jika belum diset di PHP/MySQL)
date_default_timezone_set('Asia/Jakarta'); 

// Query untuk mendapatkan antrian yang sedang dipanggil ("Dipanggil" atau "Sedang Periksa")
// Dikelompokkan per Poli, ambil yang paling baru dipanggil
$sql_called = "SELECT a.nomor_antrian, p.nama_poli, d.nama_lengkap AS nama_dokter, a.poli_id
               FROM antrian a
               JOIN poli p ON a.poli_id = p.poli_id
               LEFT JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
               LEFT JOIN jadwal_praktik jp ON pf.poli_id = jp.poli_id AND jp.hari_praktik = DAYNAME(CURDATE())
               LEFT JOIN dokter d ON jp.dokter_id = d.dokter_id
               WHERE a.tgl_layanan = CURDATE() AND a.status_antrian IN ('Dipanggil', 'Sedang Periksa')
               GROUP BY a.poli_id 
               ORDER BY a.waktu_dipanggil DESC";
$result_called = mysqli_query($conn, $sql_called);

// Query untuk mendapatkan semua antrian yang masih menunggu hari ini
$sql_waiting = "SELECT a.nomor_antrian, p.nama_poli, p.poli_id
                 FROM antrian a
                 JOIN poli p ON a.poli_id = p.poli_id
                 WHERE a.tgl_layanan = CURDATE() AND a.status_antrian = 'Menunggu'
                 ORDER BY p.poli_id, a.antrian_id ASC";
$result_waiting = mysqli_query($conn, $sql_waiting);

// Ambil semua hasil antrian yang dipanggil ke array
$calling_antrian = [];
if ($result_called) {
    while ($row = mysqli_fetch_assoc($result_called)) {
        $calling_antrian[] = $row;
    }
    mysqli_free_result($result_called);
}

// Ambil semua hasil antrian menunggu ke array dan kelompokkan
$waiting_grouped = [];
if ($result_waiting) {
    while ($antrian = mysqli_fetch_assoc($result_waiting)) {
        $waiting_grouped[$antrian['nama_poli']][] = $antrian['nomor_antrian'];
    }
    mysqli_free_result($result_waiting);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Antrian RS Jiwa | Live Display</title>
    <meta http-equiv="refresh" content="10"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0d47a1; /* Biru Tua/Navy */
            color: #f8f9fa;
        }
        .header {
            background-color: #17a2b8;
            padding: 15px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .main-display {
            background-color: #ffffff; /* Latar putih cerah */
            color: #343a40;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .antrian-nomor {
            font-size: 8rem; /* Lebih besar */
            font-weight: 900;
            line-height: 1;
            color: #dc3545; /* Merah terang */
            animation: pulse-border 1.5s infinite;
            display: block;
        }
        .called-info {
            font-size: 1.5rem;
            color: #17a2b8;
        }
        .waiting-panel {
            background-color: #1a237e; /* Biru gelap untuk kontras */
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }
        .waiting-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 0;
        }
        .next-numbers {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffc107; /* Kuning */
        }
        
        /* Animasi border untuk antrian yang dipanggil */
        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
</head>
<body>
    <div class="header text-white text-center">
        <h1 class="mb-0 fw-bold">🏥 RS JIWA - INFORMASI ANTRIAN LIVE</h1>
        <p class="mb-0 fw-light small">Data diperbarui secara otomatis | Waktu Server: <?php echo date('H:i:s'); ?> WIB</p>
    </div>

    <div class="container-fluid pt-4">
        <div class="row">
            
            <div class="col-lg-6">
                <div class="main-display text-center h-100">
                    <h2 class="text-uppercase fw-light mb-4">NOMOR ANTRIAN SAAT INI</h2>
                    
                    <?php if (!empty($calling_antrian)): ?>
                        <?php 
                        // Ambil antrian pertama (yang paling baru dipanggil) untuk display besar
                        $latest_antrian = $calling_antrian[0];
                        ?>
                        <div class="p-4">
                            <p class="text-muted lead mb-1">Poli Tujuan</p>
                            <h3 class="called-info text-uppercase mb-4 fw-bold">
                                <?php echo htmlspecialchars($latest_antrian['nama_poli']); ?>
                            </h3>
                            
                            <div class="antrian-nomor mx-auto mb-3">
                                <?php echo htmlspecialchars($latest_antrian['nomor_antrian']); ?>
                            </div>
                            
                            <p class="lead mb-0 text-dark">
                                Dokter: *<?php echo htmlspecialchars($latest_antrian['nama_dokter'] ?? 'N/A'); ?>*
                            </p>
                            
                            <?php 
                            // Tampilkan antrian lain yang juga sedang dipanggil/diperiksa (jika ada lebih dari satu poli)
                            if (count($calling_antrian) > 1):
                            ?>
                            <div class="mt-4 border-top pt-3">
                                <p class="text-muted small">Antrian Lain yang Sedang Dilayani:</p>
                                <?php 
                                array_shift($calling_antrian); // Hapus item pertama yang sudah ditampilkan
                                foreach($calling_antrian as $antrian): ?>
                                    <span class="badge bg-secondary me-2">
                                        <?php echo htmlspecialchars($antrian['nomor_antrian']); ?> (<?php echo htmlspecialchars($antrian['nama_poli']); ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-light border-0" role="alert" style="color: #6c757d;">
                            <i class="bi bi-bell-slash fs-2 mb-3"></i>
                            <h4 class="alert-heading">TIDAK ADA PANGGILAN AKTIF</h4>
                            <p>Mohon menunggu, layanan akan segera dimulai.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="waiting-panel">
                    <h4 class="text-warning text-center mb-4">Antrian Menunggu (Next in Line)</h4>
                    
                    <?php if (!empty($waiting_grouped)): ?>
                        <div class="accordion" id="accordionWaiting">
                            <?php $i = 0; foreach ($waiting_grouped as $poli_name => $antrian_numbers): $i++; ?>
                                <div class="accordion-item bg-transparent">
                                    <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                        <button class="accordion-button bg-light text-dark <?php echo ($i > 1) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>" aria-expanded="<?php echo ($i <= 1) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $i; ?>">
                                            **<?php echo htmlspecialchars($poli_name); ?>** (<?php echo count($antrian_numbers); ?> antrian menunggu)
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo ($i <= 1) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#accordionWaiting">
                                        <div class="accordion-body text-white">
                                            <p class="text-muted small">Antrian Selanjutnya:</p>
                                            <div class="antrian-list-numbers">
                                                <?php 
                                                // Tampilkan hingga 5 antrian pertama secara menonjol
                                                $display_limit = 5;
                                                $displayed_count = 0;
                                                foreach ($antrian_numbers as $num): 
                                                    if ($displayed_count < $display_limit):
                                                ?>
                                                        <span class="next-numbers badge bg-warning text-dark me-2">#<?php echo htmlspecialchars($num); ?></span>
                                                <?php 
                                                    $displayed_count++;
                                                    endif;
                                                endforeach; 
                                                ?>
                                                <?php if (count($antrian_numbers) > $display_limit): ?>
                                                    <span class="badge bg-light text-dark">+<?php echo count($antrian_numbers) - $display_limit; ?> lainnya</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light text-center mt-5" role="alert">
                            Semua poli sedang kosong atau layanan hari ini telah berakhir.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>

<?php mysqli_close($conn); ?>