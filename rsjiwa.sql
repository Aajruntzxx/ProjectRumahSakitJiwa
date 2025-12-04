-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 06:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rsjiwa`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('Super Admin','Front Office','Dokter') NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password_hash`, `nama_lengkap`, `role`, `status_aktif`) VALUES
(1, 'SuperAdmin', '$2y$10$ZnGKdqFjqwOe9EgwjqNM8e5JKAYsqIGeWbUAyQQcWCSZkLmhhlaqm', 'Admin Master Sistem', 'Super Admin', 1),
(2, 'Administrasi', '$2y$10$szwI.5QdHMH/V3tR2kfywuaO.sDKHajPXUChD6ernJoF.uzZa0kZS', 'Ajruntzxx', 'Front Office', 1),
(3, 'Dokter1', '$2y$10$pC0Sl8MYTvV.oEKOQ8dsV.7jzjrtt1mxF4RDTQZ1Qp0x3xEJdBYoq', 'Dr. Budi Santoso', 'Dokter', 1),
(4, 'Dokter2', '$2y$10$WbTHhegMtmdCWze9QOBLNOlTFQUStkg5YzYWaUfhxMDcGsi18e/dG', 'Dr. Citra Dewi', 'Dokter', 1),
(5, 'Dokter3', '$2y$10$I3MRJnfnwNVam0Vf4k78BeXCIWwEuAdD0FAE/vAl1cizsxO6h/A7G', 'Dr. Agung Pratama', 'Dokter', 1),
(6, 'Dokter4', '$2y$10$BC8EOI0kgowUGGxmq5XdjetY/OID9/EKdFDKAR09nEeuA1zBKIcvu', 'Dr. Rina Wulandari', 'Dokter', 1),
(7, 'Dokter5', '$2y$10$q3LHsAB.v.2lVB28UP1OW.mKOJMIBVIv7GqC1CkUKFdZvYEnsfp.y', 'Dr. Eko Susanto', 'Dokter', 1),
(8, 'Dokter6', '$2y$10$qI8uByeSWRmk2uqyiEqEMexIsFv3VbG4IVdYCMNtO7Sld./87oNIW', 'Dr. Nia Kurniawan', 'Dokter', 1),
(9, 'Dokter7', '$2y$10$dMFzh4iT6Ww4WU7DsEgUdOuOOo7B4DDEI69Fb9akSOL/wvM5L7EoK', 'Dr. Fajar Rahadi', 'Dokter', 1);

-- --------------------------------------------------------

--
-- Table structure for table `antrian`
--

CREATE TABLE `antrian` (
  `antrian_id` int(11) NOT NULL,
  `pendaftaran_id` int(11) NOT NULL,
  `poli_id` int(11) NOT NULL,
  `nomor_antrian` varchar(10) NOT NULL,
  `tgl_layanan` date NOT NULL,
  `status_antrian` enum('Menunggu','Dipanggil','Sedang Periksa','Selesai','Tidak Hadir') NOT NULL,
  `waktu_dipanggil` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `antrian`
--

INSERT INTO `antrian` (`antrian_id`, `pendaftaran_id`, `poli_id`, `nomor_antrian`, `tgl_layanan`, `status_antrian`, `waktu_dipanggil`) VALUES
(4, 4, 3, 'C001', '2025-11-19', 'Selesai', '20:01:16'),
(5, 5, 2, 'B001', '2025-11-19', 'Selesai', '20:02:44'),
(6, 6, 6, 'F001', '2025-11-19', 'Selesai', '20:02:46'),
(7, 7, 3, 'C002', '2025-11-19', 'Selesai', '20:22:05'),
(8, 8, 2, 'B002', '2025-11-19', 'Selesai', '20:22:11'),
(9, 9, 1, 'A001', '2025-11-19', 'Selesai', '20:23:52'),
(10, 10, 3, 'C003', '2025-11-19', 'Selesai', '20:26:41'),
(11, 11, 2, 'B003', '2025-11-19', 'Selesai', '20:25:21'),
(12, 12, 1, 'A002', '2025-11-19', 'Selesai', '20:26:43'),
(13, 13, 3, 'C004', '2025-11-19', 'Selesai', '20:56:07'),
(14, 14, 2, 'B004', '2025-11-19', 'Selesai', '20:53:02'),
(15, 15, 3, 'C005', '2025-11-19', 'Dipanggil', '22:43:59'),
(16, 16, 1, 'A001', '2025-11-24', 'Selesai', '13:06:00'),
(17, 17, 3, 'C001', '2025-11-27', 'Selesai', '12:27:30'),
(18, 18, 2, 'B001', '2025-11-27', 'Selesai', '12:36:52'),
(19, 19, 3, 'C002', '2025-11-27', 'Selesai', '18:03:44');

-- --------------------------------------------------------

--
-- Table structure for table `dokter`
--

CREATE TABLE `dokter` (
  `dokter_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `spesialisasi` varchar(100) NOT NULL,
  `no_str` varchar(20) DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokter`
--

INSERT INTO `dokter` (`dokter_id`, `admin_id`, `nama_lengkap`, `spesialisasi`, `no_str`, `no_telepon`, `status_aktif`) VALUES
(8, 3, 'Dr. Budi Santoso', 'Psikiatri Dewasa', 'STR-001', '08123456701', 1),
(9, 4, 'Dr. Citra Dewi', 'Psikiatri Anak dan Remaja', 'STR-002', '08123456702', 1),
(10, 5, 'Dr. Agung Pratama', 'Neurologi', 'STR-003', '08123456703', 1),
(11, 6, 'Dr. Rina Wulandari', 'Rehabilitasi Mental', 'STR-004', '08123456704', 1),
(12, 7, 'Dr. Eko Susanto', 'Psikologi Klinis', 'STR-005', '08123456705', 1),
(13, 8, 'Dr. Nia Kurniawan', 'Psikiatri Forensik', 'STR-006', '08123456706', 1),
(14, 9, 'Dr. Fajar Rahadi', 'Psikiatri Geriatri', 'STR-007', '08123456707', 1);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_praktik`
--

CREATE TABLE `jadwal_praktik` (
  `jadwal_id` int(11) NOT NULL,
  `dokter_id` int(11) NOT NULL,
  `poli_id` int(11) NOT NULL,
  `hari_praktik` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_praktik`
--

INSERT INTO `jadwal_praktik` (`jadwal_id`, `dokter_id`, `poli_id`, `hari_praktik`, `jam_mulai`, `jam_selesai`) VALUES
(8, 8, 1, 'Senin', '00:00:00', '23:59:00'),
(9, 8, 1, 'Selasa', '00:00:00', '23:59:00'),
(10, 8, 1, 'Rabu', '00:00:00', '23:59:00'),
(11, 8, 1, 'Kamis', '00:00:00', '23:59:00'),
(12, 8, 1, 'Jumat', '00:00:00', '23:59:00'),
(13, 8, 1, 'Sabtu', '00:00:00', '23:59:00'),
(14, 8, 1, 'Minggu', '00:00:00', '23:59:00'),
(15, 9, 2, 'Senin', '00:00:00', '23:59:00'),
(16, 9, 2, 'Selasa', '00:00:00', '23:59:00'),
(17, 9, 2, 'Rabu', '00:00:00', '23:59:00'),
(18, 9, 2, 'Kamis', '00:00:00', '23:59:00'),
(19, 9, 2, 'Jumat', '00:00:00', '23:59:00'),
(20, 9, 2, 'Sabtu', '00:00:00', '23:59:00'),
(21, 9, 2, 'Minggu', '00:00:00', '23:59:00'),
(22, 10, 3, 'Senin', '00:00:00', '23:59:00'),
(23, 10, 3, 'Selasa', '00:00:00', '23:59:00'),
(24, 10, 3, 'Rabu', '00:00:00', '23:59:00'),
(25, 10, 3, 'Kamis', '00:00:00', '23:59:00'),
(26, 10, 3, 'Jumat', '00:00:00', '23:59:00'),
(27, 10, 3, 'Sabtu', '00:00:00', '23:59:00'),
(28, 10, 3, 'Minggu', '00:00:00', '23:59:00'),
(29, 11, 4, 'Senin', '00:00:00', '23:59:00'),
(30, 11, 4, 'Selasa', '00:00:00', '23:59:00'),
(31, 11, 4, 'Rabu', '00:00:00', '23:59:00'),
(32, 11, 4, 'Kamis', '00:00:00', '23:59:00'),
(33, 11, 4, 'Jumat', '00:00:00', '23:59:00'),
(34, 11, 4, 'Sabtu', '00:00:00', '23:59:00'),
(35, 11, 4, 'Minggu', '00:00:00', '23:59:00'),
(36, 12, 5, 'Senin', '00:00:00', '23:59:00'),
(37, 12, 5, 'Selasa', '00:00:00', '23:59:00'),
(38, 12, 5, 'Rabu', '00:00:00', '23:59:00'),
(39, 12, 5, 'Kamis', '00:00:00', '23:59:00'),
(40, 12, 5, 'Jumat', '00:00:00', '23:59:00'),
(41, 12, 5, 'Sabtu', '00:00:00', '23:59:00'),
(42, 12, 5, 'Minggu', '00:00:00', '23:59:00'),
(43, 13, 6, 'Senin', '00:00:00', '23:59:00'),
(44, 13, 6, 'Selasa', '00:00:00', '23:59:00'),
(45, 13, 6, 'Rabu', '00:00:00', '23:59:00'),
(46, 13, 6, 'Kamis', '00:00:00', '23:59:00'),
(47, 13, 6, 'Jumat', '00:00:00', '23:59:00'),
(48, 13, 6, 'Sabtu', '00:00:00', '23:59:00'),
(49, 13, 6, 'Minggu', '00:00:00', '23:59:00'),
(50, 14, 7, 'Senin', '00:00:00', '23:59:00'),
(51, 14, 7, 'Selasa', '00:00:00', '23:59:00'),
(52, 14, 7, 'Rabu', '00:00:00', '23:59:00'),
(53, 14, 7, 'Kamis', '00:00:00', '23:59:00'),
(54, 14, 7, 'Jumat', '00:00:00', '23:59:00'),
(55, 14, 7, 'Sabtu', '00:00:00', '23:59:00'),
(56, 14, 7, 'Minggu', '00:00:00', '23:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `pasien`
--

CREATE TABLE `pasien` (
  `pasien_id` int(11) NOT NULL,
  `no_rekam_medis` varchar(15) DEFAULT NULL,
  `nik` varchar(16) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tgl_daftar` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pasien`
--

INSERT INTO `pasien` (`pasien_id`, `no_rekam_medis`, `nik`, `nama_lengkap`, `tgl_lahir`, `jenis_kelamin`, `alamat`, `no_hp`, `email`, `tgl_daftar`) VALUES
(1, 'RM00001', '3301010000000001', 'Andi Pratama', '1995-03-15', 'Laki-laki', 'Jl. Merdeka No. 10, Jakarta', '08591111111', 'andi.p@gmail.com', '2025-11-19 19:39:23'),
(2, 'RM00002', '3301010000000002', 'Siti Nurhayati', '1988-11-28', 'Perempuan', 'Jl. Sudirman No. 25, Bandung', '08592222222', 'siti.n@gmail.com', '2025-11-19 19:39:23'),
(3, 'RM00003', '3301010000000003', 'Bambang Kusumo', '1975-07-01', 'Laki-laki', 'Perumahan Indah Blok C, Semarang', '08593333333', 'bambang.k@gmail.com', '2025-11-19 19:39:23'),
(4, 'RM00004', '3301010000000004', 'Dewi Lestari', '2001-01-20', 'Perempuan', 'Jl. Pahlawan 45, Surabaya', '08594444444', 'dewi.l@gmail.com', '2025-11-19 19:39:23'),
(5, 'RM00005', '3301010000000005', 'Eko Saputra', '1965-05-10', 'Laki-laki', 'Gg. Mawar No. 7, Yogyakarta', '08595555555', 'eko.s@gmail.com', '2025-11-19 19:39:23'),
(6, 'RM00006', '3301010000000006', 'Fina Amelia', '1999-09-09', 'Perempuan', 'Apartemen Sentosa, Medan', '08596666666', 'fina.a@gmail.com', '2025-11-19 19:39:23'),
(7, 'RM00007', '3301010000000007', 'Gilang Ramadhan', '2005-12-12', 'Laki-laki', 'Kp. Damai RT 05 RW 01, Palembang', '08597777777', 'gilang.r@gmail.com', '2025-11-19 19:39:23'),
(8, 'RM00008', '3301010000000008', 'Hana Kartika', '1992-04-22', 'Perempuan', 'Jl. Anggrek No. 12, Makassar', '08598888888', 'hana.k@gmail.com', '2025-11-19 19:39:23'),
(9, 'RM00009', '3301010000000009', 'Irfan Junaedi', '1980-02-14', 'Laki-laki', 'Komplek Cendana, Denpasar', '08599000000', 'irfan.j@gmail.com', '2025-11-19 19:39:23'),
(10, 'RM00010', '3301010000000010', 'Julianti Putri', '1970-10-30', 'Perempuan', 'Jl. Kenangan Baru, Pontianak', '08599100000', 'julianti.p@gmail.com', '2025-11-19 19:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `pendaftaran_id` int(11) NOT NULL,
  `pasien_id` int(11) NOT NULL,
  `poli_id` int(11) NOT NULL,
  `tgl_rencana_periksa` date NOT NULL,
  `jenis_pendaftaran` enum('Online','Walk-in') NOT NULL,
  `status_pendaftaran` enum('Menunggu Verifikasi','Terverifikasi','Dibatalkan','Selesai') NOT NULL,
  `tgl_waktu_input` datetime NOT NULL,
  `catatan_awal` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pendaftaran`
--

INSERT INTO `pendaftaran` (`pendaftaran_id`, `pasien_id`, `poli_id`, `tgl_rencana_periksa`, `jenis_pendaftaran`, `status_pendaftaran`, `tgl_waktu_input`, `catatan_awal`) VALUES
(4, 1, 3, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 13:57:23', 'jnvcjmnsdkvjnbs'),
(5, 4, 2, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 13:57:50', 'hbjhbvnjksnkv'),
(6, 5, 6, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 13:58:14', 'hbjjhcbghbvchgdscq'),
(7, 1, 3, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:19:13', 'hnnvhjbdnvdbvbjhdsvh'),
(8, 3, 2, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:19:34', 'xzncjhdnjskdvnjhsdv'),
(9, 4, 1, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:20:10', 'lkjjhuigsnhjhaijka'),
(10, 6, 3, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:20:25', 'jknnjk'),
(11, 7, 2, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:20:45', 'jnfhsjdbfhsjfnjs'),
(12, 10, 1, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:21:12', 'hjbcsdbghbnijvn'),
(13, 1, 3, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:41:53', 'jknxxjhbshifnsdkjcjka'),
(14, 7, 2, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 14:43:37', 'hsgdvyasgiasjs'),
(15, 9, 3, '2025-11-19', 'Walk-in', 'Terverifikasi', '2025-11-19 16:43:51', 'uihiufjdoifwelf'),
(16, 9, 1, '2025-11-24', 'Walk-in', 'Terverifikasi', '2025-11-24 07:03:22', 'stress'),
(17, 1, 3, '2025-11-27', 'Walk-in', 'Terverifikasi', '2025-11-27 06:15:52', 'kjhxxnjkdznjk'),
(18, 3, 2, '2025-11-27', 'Walk-in', 'Terverifikasi', '2025-11-27 06:32:19', 'jkhhbyugyu'),
(19, 10, 3, '2025-11-27', 'Walk-in', 'Terverifikasi', '2025-11-27 11:59:58', 'jssabhcbas');

-- --------------------------------------------------------

--
-- Table structure for table `poli`
--

CREATE TABLE `poli` (
  `poli_id` int(11) NOT NULL,
  `nama_poli` varchar(50) NOT NULL,
  `kode_antrian` varchar(5) NOT NULL DEFAULT 'Z',
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poli`
--

INSERT INTO `poli` (`poli_id`, `nama_poli`, `kode_antrian`, `deskripsi`) VALUES
(1, 'Poli Psikiatri Dewasa', 'A', 'Layanan spesialis untuk diagnosis dan penanganan masalah kesehatan mental pada pasien dewasa.'),
(2, 'Poli Psikiatri Anak & Remaja', 'B', 'Layanan spesialis yang berfokus pada kesehatan mental dan perilaku anak-anak dan remaja.'),
(3, 'Poli Neurologi', 'C', 'Poli yang menangani kelainan pada sistem saraf, termasuk otak, sumsum tulang belakang, dan saraf tepi.'),
(4, 'Poli Rehabilitasi Mental', 'D', 'Layanan untuk membantu pemulihan fungsional dan sosial bagi pasien dengan gangguan mental kronis.'),
(5, 'Poli Psikologi Klinis', 'E', 'Poli yang memberikan layanan psikoterapi, konseling, dan asesmen psikologis.'),
(6, 'Poli Psikiatri Forensik', 'F', 'Layanan yang berkaitan dengan isu kesehatan mental di mata hukum (hanya untuk referensi, mungkin tidak selalu menjadi poli umum).'),
(7, 'Poli Psikiatri Geriatri', 'G', 'Layanan yang berfokus pada kesehatan mental lansia dan masalah terkait penuaan.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`antrian_id`),
  ADD UNIQUE KEY `pendaftaran_id` (`pendaftaran_id`),
  ADD KEY `poli_id` (`poli_id`);

--
-- Indexes for table `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`dokter_id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD UNIQUE KEY `no_str` (`no_str`);

--
-- Indexes for table `jadwal_praktik`
--
ALTER TABLE `jadwal_praktik`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD UNIQUE KEY `unique_jadwal` (`dokter_id`,`poli_id`,`hari_praktik`),
  ADD KEY `poli_id` (`poli_id`);

--
-- Indexes for table `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`pasien_id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `no_rekam_medis` (`no_rekam_medis`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`pendaftaran_id`),
  ADD KEY `pasien_id` (`pasien_id`),
  ADD KEY `poli_id` (`poli_id`);

--
-- Indexes for table `poli`
--
ALTER TABLE `poli`
  ADD PRIMARY KEY (`poli_id`),
  ADD UNIQUE KEY `nama_poli` (`nama_poli`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `antrian`
--
ALTER TABLE `antrian`
  MODIFY `antrian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `dokter`
--
ALTER TABLE `dokter`
  MODIFY `dokter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `jadwal_praktik`
--
ALTER TABLE `jadwal_praktik`
  MODIFY `jadwal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `pasien`
--
ALTER TABLE `pasien`
  MODIFY `pasien_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `pendaftaran_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `poli`
--
ALTER TABLE `poli`
  MODIFY `poli_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `antrian`
--
ALTER TABLE `antrian`
  ADD CONSTRAINT `antrian_ibfk_1` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran` (`pendaftaran_id`),
  ADD CONSTRAINT `antrian_ibfk_2` FOREIGN KEY (`poli_id`) REFERENCES `poli` (`poli_id`);

--
-- Constraints for table `dokter`
--
ALTER TABLE `dokter`
  ADD CONSTRAINT `dokter_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `jadwal_praktik`
--
ALTER TABLE `jadwal_praktik`
  ADD CONSTRAINT `jadwal_praktik_ibfk_1` FOREIGN KEY (`dokter_id`) REFERENCES `dokter` (`dokter_id`),
  ADD CONSTRAINT `jadwal_praktik_ibfk_2` FOREIGN KEY (`poli_id`) REFERENCES `poli` (`poli_id`);

--
-- Constraints for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `pendaftaran_ibfk_1` FOREIGN KEY (`pasien_id`) REFERENCES `pasien` (`pasien_id`),
  ADD CONSTRAINT `pendaftaran_ibfk_2` FOREIGN KEY (`poli_id`) REFERENCES `poli` (`poli_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
