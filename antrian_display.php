<?php
// Pastikan file koneksi.php tersedia
include "koneksi.php"; // Pastikan path file koneksi.php sudah benar

// Set zona waktu (Opsional, tapi disarankan jika belum diset di PHP/MySQL)
date_default_timezone_set('Asia/Jakarta'); 

// --- Variabel Tambahan untuk Tampilan ---
$logo_path = "img/logo_rs.png"; // GANTI dengan path logo rumah sakit Anda yang sebenarnya!
$today_date_time = date('l, d F Y | H:i:s'); // Format: Hari, Tgl Bulan Tahun | Jam:Menit:Detik
$refresh_interval = 10; // Interval refresh meta tag

// Query untuk mendapatkan antrian yang sedang dipanggil ("Dipanggil" atau "Sedang Periksa")
// Query tetap sama karena fungsionalitasnya sudah benar
$sql_called = "SELECT a.nomor_antrian, p.nama_poli, d.nama_lengkap AS nama_dokter, a.poli_id, a.waktu_dipanggil 
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                LEFT JOIN pendaftaran pf ON a.pendaftaran_id = pf.pendaftaran_id
                LEFT JOIN jadwal_praktik jp ON pf.poli_id = jp.poli_id AND jp.hari_praktik = DAYNAME(CURDATE())
                LEFT JOIN dokter d ON jp.dokter_id = d.dokter_id
                WHERE a.tgl_layanan = CURDATE() AND a.status_antrian IN ('Dipanggil', 'Sedang Periksa')
                ORDER BY a.waktu_dipanggil DESC";
$result_called = mysqli_query($conn, $sql_called);

// Ambil semua hasil antrian yang dipanggil ke array
$calling_antrian = [];
if ($result_called) {
    $temp_calling = [];
    while ($row = mysqli_fetch_assoc($result_called)) {
        // Ambil hanya entri pertama per poli (yang paling baru dipanggil/periksa)
        if (!isset($temp_calling[$row['poli_id']])) {
            $temp_calling[$row['poli_id']] = $row;
        }
    }
    $calling_antrian = array_values($temp_calling); // Konversi kembali ke array berindeks numerik
    mysqli_free_result($result_called);
}

// Query untuk mendapatkan semua antrian yang masih menunggu hari ini
$sql_waiting = "SELECT a.nomor_antrian, p.nama_poli, p.poli_id
                FROM antrian a
                JOIN poli p ON a.poli_id = p.poli_id
                WHERE a.tgl_layanan = CURDATE() AND a.status_antrian = 'Menunggu'
                ORDER BY p.poli_id, a.antrian_id ASC";
$result_waiting = mysqli_query($conn, $sql_waiting);

// Ambil semua hasil antrian menunggu ke array dan kelompokkan
$waiting_grouped = [];
if ($result_waiting) {
    while ($antrian = mysqli_fetch_assoc($result_waiting)) {
        $waiting_grouped[$antrian['nama_poli']][] = $antrian['nomor_antrian'];
    }
    mysqli_free_result($result_waiting);
}

// Tutup koneksi di akhir, tapi sebelum itu, siapkan data untuk JavaScript
$calling_data_json = json_encode($calling_antrian); 
$waiting_data_json = json_encode($waiting_grouped);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Antrian RS Jiwa | Live Display Per Poli</title>
    <meta http-equiv="refresh" content="<?php echo $refresh_interval; ?>"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Palet Warna Baru:
         * Primer (Background): #f0f2f5 (Light Gray/Off-White)
         * Sekunder (Header/Aksen): Gradient dari #00bcd4 ke #00897b (Cyan/Teal)
         * Antrian Dipanggil: #dc3545 (Merah) -> Diubah menjadi #e53935 untuk card background
         * Antrian Menunggu: #ffc107 (Kuning)
         * Background Antrian Menunggu: #34495e (Dark Slate Gray/Biru Gelap)
        */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5; /* Light Gray/Off-White */
            color: #34495e; /* Dark Slate Gray */
        }
        
        /* Modifikasi Header dengan Gradasi */
        .header {
            background: linear-gradient(90deg, #00bcd4 0%, #00897b 100%); /* Gradasi dari Cyan ke Dark Teal */
            padding: 15px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Shadow lebih kuat */
            color: #ffffff;
        }
        .logo-img {
            max-height: 70px; 
            margin-right: 15px;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.5));
        }
        .main-title {
            font-weight: 700 !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        /* Akhir Modifikasi Header */

        /* Judul Antrian Dipanggil dengan background */
        .called-title-box {
            background-color: #e53935; /* Merah Marun/Solid Red */
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }


        /* Modifikasi Card Display Antrian Dipanggil */
        .main-display-card {
            background-color: #ffffff; 
            color: #34495e;
            margin-bottom: 20px;
            border-radius: 12px; /* Lebih rounded */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Shadow lebih halus */
            min-height: 200px; /* Lebih tinggi */
            border-top: 8px solid #00bcd4; /* Cyan/Teal Cerah di atas */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            position: relative;
        }
        .highlight-call {
            animation: call-pulse 1s 3 ease-out, scale-up 0.5s ease-in-out; 
            transform: scale(1.05);
            border-top: 8px solid #ffc107 !important; /* Kuning untuk Highlight */
        }

        .antrian-nomor-mini {
            font-family: 'Poppins', sans-serif;
            font-size: 5rem; /* Lebih besar */
            font-weight: 900;
            line-height: 1;
            color: #e53935; /* Merah Marun */
            animation: pulse-number 1.5s infinite;
            display: block;
        }
        .called-poli {
            font-size: 1.5rem; /* Lebih besar */
            color: #00897b; /* Dark Teal */
            font-weight: 700;
        }
        /* Akhir Modifikasi Card */

        .waiting-panel {
            background-color: #34495e; 
            border-radius: 12px; /* Lebih rounded */
            padding: 25px; /* Lebih banyak padding */
            height: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .footer-banner {
            background-color: #212529; /* Darker footer */
            color: #ffffff;
            padding: 8px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
        
        /* Tambahkan style untuk dokter */
        .doctor-name {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Marquee CSS dan Keyframes tetap sama */
        .scroll-text {
            white-space: nowrap;
            overflow: hidden;
            box-sizing: border-box;
        }
        .scroll-text-content {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 20s linear infinite;
        }
        @keyframes marquee {
            0%   { transform: translate(0, 0); }
            100% { transform: translate(-100%, 0); }
        }
        @keyframes call-pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
            50% { box-shadow: 0 0 0 15px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        @keyframes scale-up {
            0% { transform: scale(0.95); opacity: 0.5; }
            100% { transform: scale(1.05); opacity: 1; }
        }
        @keyframes pulse-number { 
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        /* Custom Accordion for Waiting List tetap sama */
        .accordion-item {
            border: none;
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
        }
        .accordion-button {
            background-color: #ffffff !important; 
            color: #34495e !important;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
        }
        .accordion-body {
            background-color: #34495e; 
            color: #ffffff;
            border-top: 1px solid #5a6d80;
        }
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #34495e !important;
            font-weight: 700;
            padding: 8px 12px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <audio id="notifAudio" src="path/to/bell_or_chime.mp3" preload="auto"></audio> 

    <div class="header text-white">
        <div class="container-fluid">
            <div class="row align-items-center">
                
                <div class="col-md-8 d-flex align-items-center">
                    <img src="<?php echo $logo_path; ?>" alt="Logo Rumah Sakit" class="logo-img d-none d-sm-block">
                    <div class="text-left">
                        <h1 class="mb-0 main-title">RS JIWA - ANTRIAN LAYANAN <i class="bi bi-hospital"></i></h1>
                        <p class="mb-0 fw-light small">DISPLAY LIVE PER POLI</p>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <p class="mb-1 fw-bold fs-5" id="currentDateTime">
                        <i class="bi bi-calendar-check me-2"></i> <?php echo $today_date_time; ?> WIB
                    </p>
                    <p class="mb-0 small text-light fw-light">Data diperbarui otomatis setiap **<?php echo $refresh_interval; ?> detik**</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid pt-4">
        <div class="row">
            
            <div class="col-lg-6">
                <div class="called-title-box">
                    <h3 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2"></i> Antrian Sedang Dipanggil</h3>
                </div>
                
                <div class="row" id="callingList">
                    
                    <?php if (!empty($calling_antrian)): ?>
                        <?php foreach($calling_antrian as $antrian): ?>
                            <div class="col-md-6 mb-4" data-poli-id="<?php echo htmlspecialchars($antrian['poli_id']); ?>" data-nomor-antrian="<?php echo htmlspecialchars($antrian['nomor_antrian']); ?>" data-call-time="<?php echo htmlspecialchars($antrian['waktu_dipanggil']); ?>">
                                <div class="main-display-card p-4 text-center">
                                    <p class="text-muted small mb-1">Poli Tujuan</p>
                                    <h4 class="called-poli text-uppercase mb-3">
                                        <?php echo htmlspecialchars($antrian['nama_poli']); ?>
                                    </h4>
                                    
                                    <div class="antrian-nomor-mini mx-auto mb-3">
                                        <?php echo htmlspecialchars($antrian['nomor_antrian']); ?>
                                    </div>
                                    
                                    <p class="mb-0 text-dark small doctor-name">
                                        <i class="bi bi-person-fill me-1"></i> Dokter: **<?php echo htmlspecialchars($antrian['nama_dokter'] ?? 'N/A'); ?>**
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <div class="col-12" id="noCallMessage">
                            <div class="alert alert-light border-0 text-center" role="alert" style="color: #6c757d;">
                                <i class="bi bi-bell-slash fs-2 mb-3"></i>
                                <h4 class="alert-heading">TIDAK ADA PANGGILAN AKTIF</h4>
                                <p>Mohon menunggu, layanan akan segera dimulai.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="waiting-panel">
                    <h4 class="text-warning text-center mb-4 fw-bold"><i class="bi bi-people-fill me-2"></i> Antrian Menunggu (Next in Line)</h4>
                    
                    <?php if (!empty($waiting_grouped)): ?>
                        <div class="accordion" id="accordionWaiting">
                            <?php $i = 0; foreach ($waiting_grouped as $poli_name => $antrian_numbers): $i++; ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                        <button class="accordion-button <?php echo ($i > 1) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>" aria-expanded="<?php echo ($i <= 1) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $i; ?>">
                                            **<?php echo htmlspecialchars($poli_name); ?>** (<?php echo count($antrian_numbers); ?> antrian menunggu)
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo ($i <= 1) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#accordionWaiting">
                                        <div class="accordion-body">
                                            <p class="text-light small mb-2">Antrian Selanjutnya (Max 5 ditampilkan):</p>
                                            <div class="antrian-list-numbers">
                                                <?php 
                                                $display_limit = 5;
                                                $displayed_count = 0;
                                                foreach ($antrian_numbers as $num): 
                                                    if ($displayed_count < $display_limit):
                                                ?>
                                                        <span class="badge bg-warning me-2 mb-2">#<?php echo htmlspecialchars($num); ?></span>
                                                <?php 
                                                        $displayed_count++;
                                                    endif;
                                                endforeach; 
                                                ?>
                                                <?php if (count($antrian_numbers) > $display_limit): ?>
                                                    <span class="badge bg-light text-dark fw-bold">+<?php echo count($antrian_numbers) - $display_limit; ?> lainnya</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light text-center mt-5" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i> Semua poli sedang kosong atau layanan hari ini telah berakhir.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-banner">
        <div class="container-fluid">
            <div class="scroll-text text-center">
                <p class="mb-0 fw-bold small scroll-text-content" id="rotatingMessage">
                    </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        // Data Antrian dari PHP (di-encode menjadi JSON)
        const CALLING_ANTRIAN_DATA = <?php echo $calling_data_json; ?>;
        const audio = document.getElementById('notifAudio');
        const rotatingMessageElement = document.getElementById('rotatingMessage');
        const currentDateTimeElement = document.getElementById('currentDateTime');
        
        // --- IDE #1: Notifikasi Audio (Web Speech API) ---
        function announceQueue(antrian) {
            if (audio) {
                audio.play().catch(e => console.warn("Autoplay audio failed:", e));
            }

            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(
                    `Nomor Antrian ${antrian.nomor_antrian}. ${antrian.nama_poli}. Silakan menuju ruangan poli Anda. Terima kasih.`
                );
                utterance.lang = 'id-ID'; 
                utterance.rate = 0.9; 
                window.speechSynthesis.speak(utterance);
            }
        }

        // --- IDE #2: Animasi dan Highlighting Panggilan Baru ---
        function checkAndHighlightNewCalls() {
            let lastCalled = JSON.parse(localStorage.getItem('lastCalledQueue')) || {};

            CALLING_ANTRIAN_DATA.forEach(antrian => {
                const poliId = antrian.poli_id;
                const currentCallTime = antrian.waktu_dipanggil; 

                if (lastCalled[poliId] !== currentCallTime) {
                    
                    announceQueue(antrian); // Panggil Notifikasi Suara

                    const card = document.querySelector(`[data-poli-id="${poliId}"][data-nomor-antrian="${antrian.nomor_antrian}"] .main-display-card`);
                    if (card) {
                        card.classList.add('highlight-call');
                        setTimeout(() => {
                            card.classList.remove('highlight-call');
                            card.style.transform = 'scale(1)';
                        }, 5000); 
                    }
                    
                    lastCalled[poliId] = currentCallTime;
                }
            });

            if (CALLING_ANTRIAN_DATA.length > 0) {
                localStorage.setItem('lastCalledQueue', JSON.stringify(lastCalled));
            } else {
                 localStorage.removeItem('lastCalledQueue');
            }
        }


        // --- IDE #3: Rotasi Konten/Pesan ---
        const MESSAGES = [
            "⚠️ PERHATIAN: Pastikan nomor antrian Anda sudah sesuai dengan poli tujuan. Kesalahan nomor antrian menjadi tanggung jawab pasien.",
            "🕒 Jam Pelayanan Rawat Jalan dimulai dari jam 08.00 sampai dengan 14.00 WIB.",
            "📱 Silakan nonaktifkan mode dering ponsel selama berada di area pemeriksaan.",
            "💉 Untuk pemeriksaan laboratorium, puasa atau persiapan khusus lainnya harap ditaati sesuai instruksi dokter.",
            "Terima kasih atas kesabaran Anda menunggu. Kami berkomitmen memberikan layanan terbaik."
        ];
        let currentMessageIndex = 0;

        function rotateMessage() {
            if (rotatingMessageElement) {
                const message = MESSAGES[currentMessageIndex];
                rotatingMessageElement.textContent = message;
                rotatingMessageElement.classList.remove('scroll-text-content');
                // Force reflow/redraw
                void rotatingMessageElement.offsetWidth; 
                rotatingMessageElement.classList.add('scroll-text-content');

                currentMessageIndex = (currentMessageIndex + 1) % MESSAGES.length;
            }
        }

        // --- IDE #4: Update Jam Real-time (agar tidak perlu refresh untuk jam) ---
        function updateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            if (currentDateTimeElement) {
                currentDateTimeElement.innerHTML = `<i class="bi bi-calendar-check me-2"></i> ${dateStr} | ${timeStr} WIB`;
            }
        }
        
        // --- INIT & EKSEKUSI ---
        
        document.addEventListener('DOMContentLoaded', () => {
            checkAndHighlightNewCalls();
            
            updateTime();
            setInterval(updateTime, 1000);

            rotateMessage();
            setInterval(rotateMessage, 15000);
        });
        
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>