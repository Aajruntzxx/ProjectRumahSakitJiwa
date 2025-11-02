<?php
// Mulai session
session_start();

// Panggil koneksi database
include "koneksi.php";

$pesan_sukses = "";
$pesan_error = "";
$data_pasien = null;
$show_form_pendaftaran = false;

// Daftar Poliklinik dan Prefix Antrian yang Didefinisikan
$poli_prefixes = [
    "Poli Jiwa Dewasa" => "J",
    "Poli Kesehatan Anak & Remaja" => "A",
    "Poli Geriatri (Lansia)" => "G",
    "Poli Psikoterapi & Konsultasi" => "P",
    "Poli Gangguan Tidur & Cemas" => "T",
    "Poli Rehabilitasi Mental" => "R"
];

// --- FUNGSI UTAMA: Cek Ketersediaan Jadwal Dokter ---
function check_jadwal_dokter($koneksi, $poli, $tanggal) {
    
    $day_name_en = strtolower(date('l', strtotime($tanggal))); 
    
    // Mapping Nama Hari Inggris ke Nama Kolom Indonesia (Sudah dibersihkan)
    $day_map = [
        'monday' => 'senin', 
        'tuesday' => 'selasa', 
        'wednesday' => 'rabu', 
        'thursday' => 'kamis', 
        'friday' => 'jumat', 
        'saturday' => 'sabtu', 
        'sunday' => 'minggu'
    ];
    $column_name = $day_map[$day_name_en] ?? null;

    if (!$column_name) { return false; }

    $query = "SELECT $column_name FROM jadwal_dokter WHERE poliklinik = '$poli'";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $jadwal = trim($row[$column_name]);
        if (!empty($jadwal) && $jadwal != 'Libur' && $jadwal != '-') {
            return true;
        }
    }
    return false;
}

// --- Bagian 1: Verifikasi Pasien ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'verifikasi') {
    $no_rm = mysqli_real_escape_string($koneksi, $_POST['no_rm']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['tgl_lahir']);

    $sql_verifikasi = "SELECT * FROM pasien WHERE no_rm = '$no_rm' AND tgl_lahir = '$tgl_lahir'";
    $result_verifikasi = mysqli_query($koneksi, $sql_verifikasi);

    if ($result_verifikasi && mysqli_num_rows($result_verifikasi) == 1) {
        $data_pasien = mysqli_fetch_assoc($result_verifikasi);
        $show_form_pendaftaran = true;
        $_SESSION['temp_no_rm'] = $no_rm;
        $_SESSION['temp_nama_pasien'] = $data_pasien['nama'];
    } else {
        $pesan_error = "Verifikasi gagal. Nomor Rekam Medis atau Tanggal Lahir tidak cocok.";
    }
}

// --- Bagian 2: Proses Pendaftaran Antrian ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'daftar_antrian') {
    
    if (!isset($_SESSION['temp_no_rm'])) {
        $pesan_error = "Sesi pendaftaran habis. Silakan verifikasi ulang.";
    } else {
        $no_rm = $_SESSION['temp_no_rm'];
        $poli_tujuan = mysqli_real_escape_string($koneksi, $_POST['poli_tujuan']);
        $tgl_kunjungan = mysqli_real_escape_string($koneksi, $_POST['tgl_kunjungan']);
        
        // 1. Cek Jadwal
        if (!check_jadwal_dokter($koneksi, $poli_tujuan, $tgl_kunjungan)) {
            $pesan_error = "Poli $poli_tujuan tidak memiliki jadwal dokter pada tanggal $tgl_kunjungan. Silakan cek Jadwal Dokter.";
        } 
        
        // 2. Cek Duplikasi Pasien Hari Ini
        if (!$pesan_error) {
            $sql_cek = "SELECT id_antrian FROM antrian WHERE no_rm = '$no_rm' AND poli_tujuan = '$poli_tujuan' AND tgl_kunjungan = '$tgl_kunjungan' AND status != 'Batal'";
            $result_cek = mysqli_query($koneksi, $sql_cek);

            if (mysqli_num_rows($result_cek) > 0) {
                $pesan_error = "Anda sudah terdaftar di $poli_tujuan untuk tanggal $tgl_kunjungan.";
            }
        }
        
        // 3. Logika Penomoran Antrian Anti-Duplikasi
        if (!$pesan_error) {
            $poli_prefix = $poli_prefixes[$poli_tujuan] ?? 'Z'; 
            
            // Hitung nomor urut antrian terakhir yang TIDAK dibatalkan untuk hari ini
            $sql_count = "
                SELECT COUNT(*) as total 
                FROM antrian 
                WHERE poli_tujuan = '$poli_tujuan' 
                AND tgl_kunjungan = '$tgl_kunjungan'
                AND status != 'Batal' 
            ";
            
            $result_count = mysqli_query($koneksi, $sql_count);
            $data_count = mysqli_fetch_assoc($result_count);
            $no_urut_dasar = $data_count['total'] + 1;

            $max_attempts = 5;
            $inserted = false;

            for ($i = 0; $i < $max_attempts; $i++) {
                $no_antrian_baru = $poli_prefix . str_pad($no_urut_dasar + $i, 3, '0', STR_PAD_LEFT);
                
                $sql_insert = "INSERT INTO antrian (no_antrian, no_rm, poli_tujuan, tgl_kunjungan, waktu_daftar, status) 
                               VALUES ('$no_antrian_baru', '$no_rm', '$poli_tujuan', '$tgl_kunjungan', NOW(), 'Menunggu')";
                
                if (mysqli_query($koneksi, $sql_insert)) {
                    $pesan_sukses = "Pendaftaran Antrian Berhasil! <br> <b>No. Antrian Anda: $no_antrian_baru</b> di $poli_tujuan pada $tgl_kunjungan. <br> Silakan datang 30 menit sebelum jadwal perkiraan.";
                    $inserted = true;
                    unset($_SESSION['temp_no_rm']);
                    unset($_SESSION['temp_nama_pasien']);
                    break;
                } else {
                    if (strpos(mysqli_error($koneksi), 'Duplicate entry') !== false) {
                        continue; 
                    } else {
                        $pesan_error = "Gagal mendaftar antrian: " . mysqli_error($koneksi);
                        break; 
                    }
                }
            }

            if (!$inserted && empty($pesan_error)) {
                $pesan_error = "Gagal mendapatkan nomor antrian unik setelah beberapa kali percobaan. Silakan coba sebentar lagi.";
            }
        }
    }
}

// Daftar Poliklinik untuk dropdown
$daftar_poli = array_keys($poli_prefixes); 
$min_date = date('Y-m-d');

// Memuat ulang data pasien jika terjadi error
if ($pesan_error && isset($_SESSION['temp_no_rm'])) {
    $no_rm_temp = $_SESSION['temp_no_rm'];
    $sql_pasien_temp = "SELECT * FROM pasien WHERE no_rm = '$no_rm_temp'";
    $result_pasien_temp = mysqli_query($koneksi, $sql_pasien_temp);
    if ($result_pasien_temp && mysqli_num_rows($result_pasien_temp) == 1) {
        $data_pasien = mysqli_fetch_assoc($result_pasien_temp);
        $show_form_pendaftaran = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Antrian Online - RS Jiwa Kenangan</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/6/6e/Hospital_font_awesome.svg"
        type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap"
        rel="stylesheet">

    <style>
        /* CSS Styling */
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

        .form-card {
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .form-card h2 {
            color: #009999;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
        }

        .btn-theme {
            background-color: #009999;
            color: #fff;
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-theme:hover {
            background-color: #007a7a;
            color: #fff;
        }

        .alert-info-pasien {
            background-color: #e0f7fa;
            border-left: 5px solid #00bcd4;
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
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav fw-semibold">
                    <li class="nav-item"><a class="nav-link" href="rsj.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="jadwaldokter.php">Jadwal Dokter</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pendaftaran.php">Pendaftaran</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="form-card">
            <h2>Pendaftaran Antrian Online</h2>
            <p class="text-center text-muted mb-4">Langkah 1: Verifikasi Data Pasien Lama</p>

            <?php
            if ($pesan_sukses) {
                echo '<div class="alert alert-success text-center" role="alert">' . $pesan_sukses . '</div>';
            }
            if ($pesan_error) {
                echo '<div class="alert alert-danger text-center" role="alert">' . $pesan_error . '</div>';
            }
            ?>

            <?php if (!$show_form_pendaftaran && !$pesan_sukses): ?>
                <form method="POST" action="pendaftaran_antrian.php">
                    <input type="hidden" name="action" value="verifikasi">
                    <div class="mb-3">
                        <label for="no_rm" class="form-label fw-semibold">Nomor Rekam Medis (No. RM)</label>
                        <input type="text" class="form-control" id="no_rm" name="no_rm" required
                            placeholder="Contoh: RM000001" value="<?php echo $_POST['no_rm'] ?? ''; ?>">
                    </div>
                    <div class="mb-4">
                        <label for="tgl_lahir" class="form-label fw-semibold">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" required
                            value="<?php echo $_POST['tgl_lahir'] ?? ''; ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-theme">Verifikasi Data Pasien</button>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">Untuk pasien baru, silakan <a href="pasienbaru.php">Daftar Pasien Baru</a> terlebih dahulu.</small>
                    </div>
                </form>

            <?php elseif ($show_form_pendaftaran && $data_pasien): ?>
                <p class="text-center text-success fw-bold">Verifikasi Berhasil! Lanjut ke Pendaftaran Antrian.</p>
                <div class="alert alert-info-pasien p-3 mb-4">
                    <p class="mb-1">Nama Pasien: <b><?php echo htmlspecialchars($data_pasien['nama']); ?></b></p>
                    <p class="mb-0">No. RM: <b><?php echo htmlspecialchars($data_pasien['no_rm']); ?></b></p>
                </div>

                <form method="POST" action="pendaftaran_antrian.php">
                    <input type="hidden" name="action" value="daftar_antrian">
                    <div class="mb-3">
                        <label for="tgl_kunjungan" class="form-label">Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="tgl_kunjungan" name="tgl_kunjungan" required
                            min="<?php echo $min_date; ?>">
                    </div>
                    <div class="mb-4">
                        <label for="poli_tujuan" class="form-label">Poli Tujuan</label>
                        <select id="poli_tujuan" name="poli_tujuan" class="form-select" required>
                            <option value="">Pilih Poliklinik...</option>
                            <?php foreach ($daftar_poli as $poli): ?>
                                <option value="<?php echo htmlspecialchars($poli); ?>"><?php echo htmlspecialchars($poli); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Pendaftaran akan ditolak jika tidak ada jadwal dokter.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-theme">Ambil Nomor Antrian</button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <footer>
        <p class="mb-0">© 2025 RS Jiwa Kenangan — Semua Hak Dilindungi</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>