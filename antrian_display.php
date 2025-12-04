<?php
// Pastikan file koneksi.php tersedia
include "koneksi.php"; 

// Set zona waktu
date_default_timezone_set('Asia/Jakarta'); 

// --- Variabel Tampilan ---
$logo_path = "img/logo_rs.png"; // Sesuaikan path logo
$today_date_time = date('l, d F Y | H:i:s'); 
$refresh_interval = 10; // Detik

// --- 1. Query Antrian Dipanggil / Sedang Periksa ---
$sql_called = "SELECT a.nomor_antrian, p.nama_poli, d.nama_lengkap AS nama_dokter, a.poli_id, a.waktu_dipanggil 
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                LEFT JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                LEFT JOIN jadwal_praktik jp ON pf.poli_id = jp.poli_id AND jp.hari_praktik = DAYNAME(CURDATE())
                LEFT JOIN dokter d ON jp.dokter_id = d.dokter_id
                WHERE a.tgl_layanan = CURDATE() AND a.status_antrian IN ('Dipanggil', 'Sedang Periksa')
                ORDER BY a.waktu_dipanggil DESC";
$result_called = mysqli_query($conn, $sql_called);

$calling_antrian = [];
if ($result_called) {
    $temp_calling = [];
    while ($row = mysqli_fetch_assoc($result_called)) {
        // Ambil hanya satu entri terbaru per poli
        if (!isset($temp_calling[$row['poli_id']])) {
            $temp_calling[$row['poli_id']] = $row;
        }
    }
    $calling_antrian = array_values($temp_calling);
    mysqli_free_result($result_called);
}

// --- 2. Query Antrian Menunggu ---
$sql_waiting = "SELECT a.nomor_antrian, p.nama_poli, p.poli_id
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                WHERE a.tgl_layanan = CURDATE() AND a.status_antrian = 'Menunggu'
                ORDER BY p.poli_id, a.antrian_id ASC";
$result_waiting = mysqli_query($conn, $sql_waiting);

$waiting_grouped = [];
if ($result_waiting) {
    while ($antrian = mysqli_fetch_assoc($result_waiting)) {
        $waiting_grouped[$antrian['nama_poli']][] = $antrian['nomor_antrian'];
    }
    mysqli_free_result($result_waiting);
}

// JSON Data untuk JS
$calling_data_json = json_encode($calling_antrian); 
$waiting_data_json = json_encode($waiting_grouped);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Antrian | RS Jiwa</title>
    
    <!-- Refresh Otomatis -->
    <meta http-equiv="refresh" content="<?php echo $refresh_interval; ?>"> 
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --main-font: 'Poppins', sans-serif;
            --heading-font: 'Montserrat', sans-serif;
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
        }

        body {
            font-family: var(--main-font);
            background-color: var(--bg-color);
            overflow-x: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Modern */
        .display-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 20px 40px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
            margin-bottom: 30px;
        }
        
        .display-title {
            font-family: var(--heading-font);
            font-weight: 900;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        /* Card Styles */
        .card-display {
            border: none;
            border-radius: 20px;
            background-color: var(--card-bg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .card-header-display {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 15px 20px;
            font-weight: 700;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }

        /* Bagian Kiri: Sedang Dipanggil */
        .card-calling {
            border-left: 10px solid #ffc107; /* Kuning Warning */
            height: 100%;
        }
        .highlight-call {
            animation: pulse-highlight 1.5s infinite alternate;
            border-left-color: #dc3545; /* Merah saat baru */
        }
        @keyframes pulse-highlight {
            from { box-shadow: 0 0 10px rgba(220, 53, 69, 0.1); transform: scale(1); }
            to { box-shadow: 0 0 25px rgba(220, 53, 69, 0.4); transform: scale(1.02); }
        }

        .calling-number {
            font-family: var(--heading-font);
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            color: #212529;
            margin: 20px 0;
        }
        .calling-poli {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0d6efd;
        }
        .calling-dokter {
            font-size: 1.1rem;
            color: #6c757d;
            background-color: #f8f9fa;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 50px;
        }

        /* Bagian Kanan: Menunggu */
        .waiting-container {
            background-color: #343a40;
            border-radius: 20px;
            padding: 25px;
            color: white;
            height: 100%;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }
        .waiting-item {
            background-color: rgba(255,255,255,0.1);
            margin-bottom: 15px;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .waiting-header {
            color: #ffc107;
            font-weight: 700;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .badge-waiting {
            font-size: 1.1rem;
            background-color: white;
            color: #212529;
            margin: 3px;
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 700;
        }

        /* Footer Marquee */
        .footer-marquee {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #212529;
            color: white;
            padding: 10px 0;
            z-index: 1000;
            font-size: 1.1rem;
            font-weight: 500;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
        }
        
        /* Helpers */
        .empty-state {
            height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #adb5bd;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="display-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center">
                    <div class="bg-white p-2 rounded-circle me-3 shadow-sm" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-hospital-fill text-primary fs-1"></i>
                    </div>
                    <div>
                        <h1 class="display-title mb-0">RS JIWA GraSHia</h1>
                        <p class="mb-0 fw-light opacity-75" style="letter-spacing: 2px;">SISTEM INFORMASI ANTRIAN TERPADU</p>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <h3 class="fw-bold mb-0" id="clock">00:00:00</h3>
                    <p class="mb-0"><?php echo date('l, d F Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid flex-grow-1 pb-5 mb-5">
        <div class="row h-100 g-4">
            
            <!-- Kolom Kiri: Antrian Dipanggil -->
            <div class="col-lg-7">
                <h4 class="text-secondary fw-bold mb-3 ps-2 border-start border-5 border-warning">
                    <i class="bi bi-megaphone-fill me-2"></i>SEDANG DIPANGGIL / PERIKSA
                </h4>
                
                <div class="row g-4" id="callingContainer">
                    <?php if (!empty($calling_antrian)): ?>
                        <?php foreach($calling_antrian as $antrian): ?>
                            <div class="col-md-12 mb-2" data-poli-id="<?php echo htmlspecialchars($antrian['poli_id']); ?>" data-call-time="<?php echo htmlspecialchars($antrian['waktu_dipanggil']); ?>">
                                <div class="card-display card-calling p-4 text-center d-flex flex-row align-items-center justify-content-between">
                                    <!-- Info Poli & Dokter -->
                                    <div class="text-start w-50">
                                        <div class="calling-poli text-uppercase mb-1">
                                            <?php echo htmlspecialchars($antrian['nama_poli']); ?>
                                        </div>
                                        <div class="calling-dokter">
                                            <i class="bi bi-person-fill me-1"></i> <?php echo htmlspecialchars($antrian['nama_dokter'] ?? 'Dokter Jaga'); ?>
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            <i class="bi bi-clock me-1"></i> Dipanggil: <?php echo date('H:i', strtotime($antrian['waktu_dipanggil'])); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Nomor Besar -->
                                    <div class="w-50 text-end pe-4 border-start">
                                        <div class="text-secondary fw-bold small">NOMOR ANTRIAN</div>
                                        <div class="calling-number text-primary">
                                            <?php echo htmlspecialchars($antrian['nomor_antrian']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card-display card-calling p-5 text-center">
                                <div class="empty-state">
                                    <i class="bi bi-bell-slash display-1 mb-3"></i>
                                    <h3>Belum Ada Panggilan</h3>
                                    <p>Silakan menunggu, antrian akan segera dimulai.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kolom Kanan: Antrian Menunggu -->
            <div class="col-lg-5">
                <div class="waiting-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0 fw-bold text-white"><i class="bi bi-people-fill me-2"></i>NEXT IN LINE</h4>
                        <span class="badge bg-warning text-dark">Live Status</span>
                    </div>

                    <?php if (!empty($waiting_grouped)): ?>
                        <div class="waiting-list-wrapper" style="overflow-y: auto; max-height: 60vh; padding-right: 5px;">
                            <?php foreach ($waiting_grouped as $poli_name => $antrian_numbers): ?>
                                <div class="waiting-item">
                                    <div class="waiting-header d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($poli_name); ?></span>
                                        <span class="badge bg-dark border border-secondary"><?php echo count($antrian_numbers); ?> org</span>
                                    </div>
                                    <div>
                                        <?php 
                                        // Tampilkan max 8 antrian per poli agar tidak penuh
                                        $limit = 8;
                                        $count = 0;
                                        foreach ($antrian_numbers as $num): 
                                            if ($count < $limit):
                                        ?>
                                            <span class="badge badge-waiting"><?php echo htmlspecialchars($num); ?></span>
                                        <?php 
                                            $count++;
                                            endif;
                                        endforeach; 
                                        ?>
                                        <?php if(count($antrian_numbers) > $limit): ?>
                                            <span class="text-white-50 small ms-1">+<?php echo count($antrian_numbers) - $limit; ?> lainnya</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state text-white-50">
                            <i class="bi bi-cup-hot display-4 mb-3"></i>
                            <p>Tidak ada antrian menunggu.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer Marquee -->
    <div class="footer-marquee">
        <marquee scrollamount="8">
             <strong>INFORMASI:</strong> Harap memperhatikan nomor antrian Anda. Pasien yang dipanggil 3 kali tidak hadir akan dilewati. | 
            <strong>JAM PELAYANAN:</strong> Senin - Jumat (08:00 - 14:00 WIB), Sabtu (08:00 - 12:00 WIB). |
             <strong>PROTOKOL:</strong> Tetap jaga jarak dan gunakan masker di area rumah sakit.
        </marquee>
    </div>

    <!-- Audio Element -->
    <audio id="bellAudio" src="assets/audio/bell.mp3"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data PHP ke JS
        const callingData = <?php echo $calling_data_json; ?>;
        const audio = document.getElementById('bellAudio');

        // Update Jam Realtime
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clock').innerText = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Logika Highlight Panggilan Baru
        document.addEventListener('DOMContentLoaded', () => {
            let lastCalled = JSON.parse(localStorage.getItem('lastCalledDisplay')) || {};
            let hasNewCall = false;

            callingData.forEach(antrian => {
                const poliId = antrian.poli_id;
                const callTime = antrian.waktu_dipanggil;

                // Jika waktu panggil beda dengan yang di storage, berarti baru
                if (lastCalled[poliId] !== callTime) {
                    hasNewCall = true;
                    lastCalled[poliId] = callTime;

                    // Cari elemen card dan tambahkan efek
                    const card = document.querySelector(`[data-poli-id="${poliId}"] .card-calling`);
                    if (card) {
                        card.classList.add('highlight-call');
                        
                        // Text to Speech
                        if ('speechSynthesis' in window) {
                            // Tunggu user interaction policy (kadang butuh klik dulu di browser modern)
                            const msg = new SpeechSynthesisUtterance(`Nomor Antrian ${antrian.nomor_antrian}, Silakan ke ${antrian.nama_poli}`);
                            msg.lang = 'id-ID';
                            msg.rate = 0.9;
                            window.speechSynthesis.speak(msg);
                        }
                    }
                }
            });

            // Mainkan bell jika ada panggilan baru
            if (hasNewCall && audio) {
                audio.play().catch(e => console.log("Audio autoplay blocked:", e));
            }

            // Simpan state terbaru
            if (callingData.length > 0) {
                localStorage.setItem('lastCalledDisplay', JSON.stringify(lastCalled));
            }
        });
    </script>
</body>
</html>