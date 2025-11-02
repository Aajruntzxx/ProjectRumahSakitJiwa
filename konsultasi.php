<?php
// Menggunakan koneksi database dari file yang disertakan
include "koneksi.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Konsultasi Lengkap - RS Jiwa Kenangan</title>
    <link rel="icon" href="https://upload.wikimedia.com/wikipedia/commons/6/6e/Hospital_font_awesome.svg" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f7fafc;
            font-family: 'Nunito Sans', sans-serif;
            color: #2d2d2d;
            scroll-behavior: smooth;
        }

        .navbar {
            transition: all 0.3s ease;
            background-color: #fff;
            padding: 14px 0;
            border-bottom: 4px solid #b3e5fc;
        }

        .navbar-brand img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 10px;
        }

        .navbar-brand h1 {
            font-size: 26px;
            font-weight: 700;
            color: #c44d3e;
            margin: 0;
        }

        .navbar-nav .nav-link {
            font-weight: 600;
            color: #333 !important;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link.active,
        .navbar-nav .nav-link:hover {
            color: #c44d3e !important;
        }

        .chatbot-container {
            max-width: 600px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            overflow: hidden;
            height: 75vh; /* Ditingkatkan */
            display: flex;
            flex-direction: column;
        }

        #chat-box {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #e3f2fd;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
            line-height: 1.5;
        }

        .user-message {
            background-color: #c44d3e;
            color: #fff;
            margin-left: auto;
            text-align: right;
            border-bottom-right-radius: 0;
        }

        .bot-message {
            background-color: #ffffff;
            color: #2d2d2d;
            margin-right: auto;
            border: 1px solid #b3e5fc;
            text-align: left;
            border-bottom-left-radius: 0;
        }
        
        .bot-header {
            background-color: #4fa9c6;
            color: white;
            padding: 15px;
            font-size: 1.25rem;
            font-weight: bold;
            text-align: center;
        }

        .input-area {
            padding: 10px;
            border-top: 1px solid #ddd;
            display: flex;
        }

        .input-area input {
            border-radius: 20px;
            border: 1px solid #b3e5fc;
            padding: 10px 15px;
            margin-right: 10px;
        }

        .input-area button {
            background-color: #f8b25c;
            border: none;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            transition: background-color 0.3s;
        }

        .input-area button:hover {
            background-color: #eaa244;
        }
        
        /* Tambahan untuk tombol menu */
        .menu-button {
            background-color: #4fa9c6;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .menu-button:hover {
            background-color: #3b8aa6;
        }

        /* Footer styles */
        .footer-custom {
            background-color: #3f5163;
            color: #fff;
            padding: 70px 6%;
        }

        .footer-bottom {
            background: #2d3947;
            color: #ccc;
            text-align: center;
            padding: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="rsj.php">
                <img src="https://media.istockphoto.com/id/1385829372/id/vektor/logo-kesehatan-mental-dan-terapi-fisik.jpg?s=170667a&w=0&k=20&c=-fc1p2AXFdoZO0SYwCI6fap-3IcYvXUTqAPun0VKlKQ=" alt="Logo RS Jiwa Kenangan">
                <div>
                    <h1 class="mb-0">RS Jiwa Kenangan</h1>
                    <small>Jl. Kaliurang No.12, Sleman, Yogyakarta</small>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav fw-semibold">
                    <li class="nav-item"><a class="nav-link" href="rsj.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="jadwaldokter.php">Jadwal Dokter</a></li>
                    <li class="nav-item"><a class="nav-link" href="pendaftaran.php">Pendaftaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="riwayat.php">Data Pasien</a></li>
                    <li class="nav-item"><a class="nav-link active" href="konsultasi.php">Konsultasi Online</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="chatbot-container">
            <div class="bot-header">
                Chatbot Bantuan Jiwa RS Jiwa Kenangan 🧠
            </div>
            <div id="chat-box">
                </div>
            <div class="input-area">
                <input type="text" id="user-input" class="form-control" placeholder="Ketik pesan atau pilih opsi di atas...">
                <button onclick="sendMessage()">Kirim</button>
            </div>
        </div>
    </div>

    <div class="footer-custom text-white">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="text-warning mb-3">RS Jiwa Kenangan</h5>
                    <p>Menjadi pusat pelayanan kesehatan jiwa yang unggul, humanis, dan terpercaya di Yogyakarta.</p>
                </div>
                <div class="col-md-4">
                    <h5 class="text-warning mb-3">Lokasi Kami</h5>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!..." width="100%" height="200" style="border:0;" allowfullscreen loading="lazy"></iframe>
                </div>
                <div class="col-md-4">
                    <h5 class="text-warning mb-3">Hubungi Kami</h5>
                    <p>Jl. Kaliurang No.12, Sleman, Yogyakarta 55281</p><br>
                    <a href="tel:+62274555555" class="d-block">📞 (0274) 555555</a>
                    <a href="mailto:info@rsjiwakenangan.co.id" class="d-block">✉️ info@rsjiwakenangan.co.id</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-bottom">
        © 2025 RS Jiwa Kenangan Yogyakarta. Seluruh hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        let currentStep = 'main_menu'; // State untuk melacak langkah saat ini
        let screeningData = {}; // Data skrining sementara

        // Fungsi untuk menambahkan pesan ke chat box
        function addMessage(text, sender) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', `${sender}-message`);
            messageElement.innerHTML = text;

            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        
        // Fungsi untuk menambahkan tombol menu
        function addMenu(options) {
            let menuHtml = '<div style="margin-top: 10px;">';
            options.forEach(opt => {
                menuHtml += `<button class="menu-button" onclick="handleMenuClick('${opt.value}')">${opt.text}</button>`;
            });
            menuHtml += '</div>';
            addMessage(menuHtml, 'bot');
        }

        // --- Logika Skrining Depresi Sederhana ---
        const screeningQuestions = [
            "1. Dalam 2 minggu terakhir, seberapa sering Anda merasa **sedih, murung, atau putus asa**?",
            "2. Seberapa sering Anda **kehilangan minat** atau kesenangan dalam melakukan sesuatu?",
            "3. Seberapa sering Anda mengalami **gangguan tidur** (sulit tidur, terlalu banyak tidur, atau tidur tidak nyenyak)?",
            "4. Seberapa sering Anda merasa **lelah** atau memiliki sedikit energi?",
            "5. Seberapa sering Anda memiliki pikiran untuk **menyakiti diri sendiri** atau merasa lebih baik mati?"
        ];
        const screeningOptions = [
            { text: "Tidak Pernah", value: "0" },
            { text: "Beberapa Hari", value: "1" },
            { text: "Lebih dari Separuh Waktu", value: "2" },
            { text: "Hampir Setiap Hari", value: "3" }
        ];
        let currentQuestionIndex = 0;

        function startDepressionScreening() {
            currentStep = 'screening_depresi';
            currentQuestionIndex = 0;
            screeningData = { totalScore: 0 };
            askNextScreeningQuestion();
        }

        function askNextScreeningQuestion() {
            if (currentQuestionIndex < screeningQuestions.length) {
                addMessage(screeningQuestions[currentQuestionIndex], 'bot');
                addMenu(screeningOptions);
            } else {
                showScreeningResult();
            }
        }

        function handleScreeningAnswer(answerValue) {
            const score = parseInt(answerValue);
            screeningData.totalScore += score;
            addMessage(`Jawaban: ${screeningOptions.find(o => o.value === answerValue).text}`, 'user');
            
            // Cek pertanyaan darurat (bunuh diri)
            if (currentQuestionIndex === 4 && score >= 2) { 
                addMessage("⚠️ **PERHATIAN: Jawaban Anda menunjukkan risiko krisis yang tinggi.** ⚠️<br>Mohon segera hubungi hotline darurat RSJ Kenangan: <b>(0274) 555555</b>. Kami sangat peduli. Jangan tunda!", 'bot');
                resetChat();
                return;
            }

            currentQuestionIndex++;
            askNextScreeningQuestion();
        }

        function showScreeningResult() {
            currentStep = 'main_menu';
            let resultMessage = `✅ **Skrining Selesai!** Skor total Anda adalah **${screeningData.totalScore}** (dari maksimal 15).<br><br>`;
            
            if (screeningData.totalScore >= 10) {
                resultMessage += "‼️ **Hasil: Gejala Sedang hingga Parah.** Anda sangat dianjurkan untuk segera menjadwalkan konsultasi tatap muka dengan Psikiater kami. <a href='pendaftaran.php'>Daftar Sekarang</a>.";
            } else if (screeningData.totalScore >= 5) {
                resultMessage += "🔔 **Hasil: Gejala Ringan.** Perhatikan gejala ini. Jika menetap atau memburuk, segera konsultasi. Anda dapat mencoba Konsultasi Online via WhatsApp dengan staf kami.";
            } else {
                resultMessage += "👍 **Hasil: Minimal.** Gejala yang dilaporkan minimal. Tetap jaga kesehatan mental. Jika ada kekhawatiran lain, silakan kembali ke menu utama.";
            }
            addMessage(resultMessage, 'bot');
            showMainMenu();
        }
        
        // --- Logika Menu Utama ---
        function showMainMenu() {
            addMessage("Silakan pilih salah satu opsi di bawah ini:", 'bot');
            const menuOptions = [
                { text: "1. Konsultasi Gejala Awal (Skrining)", value: "konsultasi_gejala" },
                { text: "2. Informasi Penyakit (Depresi, Cemas, dll)", value: "info_penyakit" },
                { text: "3. Info Pelayanan RS (Jadwal/Pendaftaran)", value: "info_rs" },
                { text: "4. Bicara dengan Staf (Live Chat/WA)", value: "bicara_staf" }
            ];
            addMenu(menuOptions);
            currentStep = 'main_menu';
        }

        // --- Logika Respon Penyakit Detil ---
        function showPenyakitMenu() {
            addMessage("Pilih jenis informasi penyakit yang Anda cari:", 'bot');
             const penyakitOptions = [
                { text: "Depresi vs. Bipolar", value: "depresi_bipolar" },
                { text: "Gangguan Kecemasan (Anxiety)", value: "anxiety_info" },
                { text: "Gangguan Psikotik (Skizofrenia)", value: "psikotik_info" },
                { text: "Kembali ke Menu Utama", value: "menu" }
            ];
            addMenu(penyakitOptions);
            currentStep = 'info_penyakit';
        }
        
        function getPenyakitInfo(topic) {
            switch(topic) {
                case 'depresi_bipolar':
                    return "**Depresi** adalah kesedihan yang menetap dan kehilangan minat. **Bipolar** adalah perubahan suasana hati ekstrem antara episode Depresi dan episode Mania (sangat gembira, energi berlebihan, tidur minimal). Jika Anda mengalami perubahan suasana hati drastis, itu mungkin Bipolar. <br>✅ **Rekomendasi:** <a href='jadwaldokter.php'>Konsultasi Psikiater</a>.";
                case 'anxiety_info':
                    return "**Gangguan Kecemasan** ditandai oleh kekhawatiran berlebihan yang sulit dikendalikan. Sering disertai gejala fisik seperti jantung berdebar, keringat dingin, dan sulit bernapas. <br>✅ **Rekomendasi:** <a href='pendaftaran.php'>Daftar sesi Psikoterapi</a>.";
                case 'psikotik_info':
                    return "**Gangguan Psikotik** (seperti Skizofrenia) melibatkan kehilangan kontak dengan realitas, termasuk **Halusinasi** (melihat/mendengar sesuatu yang tidak ada) dan **Delusi** (keyakinan salah yang kuat). <br>🚨 **Penting:** Kondisi ini membutuhkan penanganan segera. Hubungi **UGD RSJ Kenangan** jika gejala parah.";
                default:
                    return "Informasi tidak ditemukan.";
            }
        }
        
        // --- Logika Penanganan Klik Menu ---
        function handleMenuClick(value) {
            addMessage(`Memilih: ${value}`, 'user');
            
            if (currentStep === 'main_menu') {
                if (value === 'konsultasi_gejala') {
                    startDepressionScreening();
                } else if (value === 'info_penyakit') {
                    showPenyakitMenu();
                } else if (value === 'info_rs') {
                    addMessage("RS Jiwa Kenangan adalah RSJ unggulan di Yogyakarta. <br>• **Jadwal Dokter:** Lihat <a href='jadwaldokter.php'>di sini</a>.<br>• **Pendaftaran:** Lakukan pendaftaran online <a href='pendaftaran.php'>di sini</a>.<br>• **Lokasi:** Jl. Kaliurang No.12, Sleman.", 'bot');
                    showMainMenu();
                } else if (value === 'bicara_staf') {
                     addMessage("Anda ingin bicara dengan staf? Silakan hubungi kami melalui **WhatsApp Staf Konsultasi** untuk respons cepat (Hanya jam kerja): <a href='https://wa.me/6281339273714' target='_blank'>0813-3927-3714</a>", 'bot');
                     showMainMenu();
                }
            } else if (currentStep === 'info_penyakit') {
                if (value === 'menu') {
                    showMainMenu();
                } else {
                    addMessage(getPenyakitInfo(value), 'bot');
                    showPenyakitMenu();
                }
            } else if (currentStep === 'screening_depresi') {
                // Semua jawaban skrining ditangani di sini
                handleScreeningAnswer(value);
            }
        }

        // --- Logika Penanganan Input Teks ---
        function getBotResponse(message) {
            const lowerCaseMsg = message.toLowerCase();

            // Cek kata kunci darurat
            if (lowerCaseMsg.includes('bunuh diri') || lowerCaseMsg.includes('akhiri hidup') || lowerCaseMsg.includes('self harm')) {
                return "🆘 **Tunggu!** Jika Anda atau seseorang yang Anda kenal dalam bahaya, **JANGAN TUNDA**. Segera hubungi hotline darurat kami **(0274) 555555** atau layanan darurat terdekat. Anda tidak sendirian.";
            }

            // Arahkan kembali ke menu jika input bebas
            return "Maaf, untuk konsultasi yang lebih terstruktur, silakan **pilih opsi dari menu utama** yang tersedia. Ketik **menu** jika Anda tidak melihat tombol menu.";
        }
        
        // Fungsi untuk reset
        function resetChat() {
            currentStep = 'main_menu';
            currentQuestionIndex = 0;
            screeningData = {};
        }

        // Fungsi utama pengiriman pesan
        function sendMessage() {
            const message = userInput.value.trim();
            if (message === "") return;

            // Jika sedang dalam mode skrining, input teks tidak diizinkan, kecuali untuk angka 0-3 (tapi kita pakai tombol)
            if (currentStep === 'screening_depresi') {
                 addMessage("Mohon gunakan tombol pilihan untuk menjawab pertanyaan skrining.", 'bot');
                 return;
            }
            
            // Pengguna mengirim pesan teks
            addMessage(message, 'user');

            // Cek jika pengguna meminta menu
            if (message.toLowerCase() === 'menu' || message.toLowerCase() === 'kembali') {
                resetChat();
                showMainMenu();
                userInput.value = '';
                return;
            }

            // Dapatkan dan tambahkan respons bot
            setTimeout(() => {
                const botResponse = getBotResponse(message);
                addMessage(botResponse, 'bot');
                // Setelah respons default, kembalikan ke menu
                if (currentStep !== 'main_menu') showMainMenu();
            }, 500); 
            
            userInput.value = '';
        }

        // Agar bisa mengirim pesan dengan tombol Enter
        userInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Pesan sambutan awal dari bot
        window.onload = () => {
             addMessage("👋 Halo! Selamat datang di layanan Chatbot Bantuan Jiwa RS Jiwa Kenangan. Saya akan memandu Anda untuk informasi awal.", 'bot');
             showMainMenu();
        };

    </script>
</body>

</html>