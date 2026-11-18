<?php
require_once 'config.php';

if (isset($_SESSION['admin_klinik_auth']) && $_SESSION['admin_klinik_auth'] === true) {
    header('Location: admin.php');
    exit;
}

$pesanError  = '';
$pesanSukses = '';

if (isset($_POST['reset_kunci_demo'])) {
    $conn->query("TRUNCATE TABLE rekam_medis");
    inisialisasiSistemKlinik($conn);
    $pesanSukses = "Pasangan Kunci Simetris & Asimetris baru berhasil dibuat beserta 3 data sampel rekam medis terenkripsi.";
}

if (isset($_POST['masuk_admin'])) {
    $inputSimetris  = trim($_POST['kunci_simetris'] ?? '');
    $inputAsimetris = trim($_POST['kunci_asimetris'] ?? '');

    $konfig = $conn->query("SELECT * FROM konfigurasi_kunci WHERE id = 1")->fetch_assoc();

    if (!$konfig) {
        $pesanError = "Konfigurasi kunci sistem belum tersedia di database.";
    } else {
        $hasilDekripsiSim  = dekripsiSimetris($konfig['token_simetris'], $inputSimetris);
        $hasilDekripsiAsym = dekripsiAsimetris($konfig['token_asimetris'], $inputAsimetris);

        if ($hasilDekripsiSim === CHALLENGE_TOKEN_KLINIK && $hasilDekripsiAsym === CHALLENGE_TOKEN_KLINIK) {
            $_SESSION['admin_klinik_auth']   = true;
            $_SESSION['aktif_kunci_sim']     = $inputSimetris;
            $_SESSION['aktif_kunci_private'] = $inputAsimetris;
            $_SESSION['aktif_kunci_public']  =$konfig['public_key'];

            header('Location: admin.php');
            exit;
        } else {
            $gagal = [];
            if ($hasilDekripsiSim !== CHALLENGE_TOKEN_KLINIK) {$gagal[] = "Kunci Simetris tidak sesuai";
            }
            if ($hasilDekripsiAsym !== CHALLENGE_TOKEN_KLINIK) {$gagal[] = "Kunci Asimetris (Private Key) tidak valid";
            }
            $pesanError = "Akses Ditolak: " . implode(" dan ", $gagal) . ".";
        }
    }
}

$demoSym  =$_SESSION['demo_kunci_simetris'] ?? 'MedikaKlinik2026#Kunci';
$demoPriv =$_SESSION['demo_kunci_asimetris'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klinik Medika Nusantara — Sistem Informasi Rekam Medis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.07) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 36px 36px;
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            50% { transform: translateY(-18px) translateX(12px); }
        }
        @keyframes pulseRing {
            0% { transform: scale(0.95); opacity: 0.7; }
            70% { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(1.35); opacity: 0; }
        }
        .orb-float { animation: floatSlow 8s ease-in-out infinite; }
        .orb-float-delayed { animation: floatSlow 10s ease-in-out 2s infinite reverse; }
        .pulse-dot::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 9999px;
            background: rgba(52, 211, 153, 0.5);
            animation: pulseRing 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }
        .spotlight-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease, border-color 0.35s ease;
        }
        .spotlight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px -15px rgba(13, 148, 136, 0.16);
            border-color: rgba(20, 184, 166, 0.5);
        }
        .spotlight-card::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(350px circle at var(--x, 50%) var(--y, 50%), rgba(20, 184, 166, 0.12), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .spotlight-card:hover::after {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans selection:bg-teal-500 selection:text-white">

    <!-- Navbar -->
    <header class="bg-white/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-6 py-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-teal-600/20">
                    +
                </div>
                <div>
                    <h1 class="font-bold text-lg text-slate-900 leading-tight">Klinik Medika Nusantara</h1>
                    <p class="text-xs text-slate-500">Sistem Informasi Rekam Medis Terpadu</p>
                </div>
            </div>
            <nav class="flex items-center gap-6 text-sm font-medium text-slate-600">
                <a href="#alur-kripto" class="hover:text-teal-600 transition">Arsitektur Kunci</a>
                <a href="#layanan" class="hover:text-teal-600 transition">Layanan Poli</a>
                <a href="#login-admin" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm hover:shadow-md hover:shadow-teal-600/25">
                    Portal Admin
                </a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-teal-950 to-emerald-950 text-white">
        <div class="absolute inset-0 bg-grid-pattern"></div>
        <div class="orb-float absolute -top-24 -left-24 w-96 h-96 rounded-full bg-teal-500/20 blur-3xl pointer-events-none"></div>
        <div class="orb-float-delayed absolute -bottom-24 right-10 w-80 h-80 rounded-full bg-emerald-400/15 blur-3xl pointer-events-none"></div>

        <div class="relative max-w-6xl mx-auto px-6 py-16 lg:py-20 grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-7 space-y-6">
                <div class="hero-stagger inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-teal-200 text-xs font-semibold">
                    <span class="relative inline-block w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                    <span>SISTEM INFORMASI KESEHATAN • DUAL-KEY ENCRYPTION</span>
                </div>

                <h2 class="hero-stagger text-3xl sm:text-5xl font-extrabold leading-[1.15] tracking-tight">
                    Rekam Medis Digital dengan Proteksi <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-emerald-300">Kunci Simetris & Asimetris</span>
                </h2>

                <p class="hero-stagger text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl">
                    Menggantikan paradigma login tradisional berbasis password dengan validasi kriptografi hibrida. Setiap identitas pasien dan diagnosa medis dienkripsi sebelum menyentuh database server.
                </p>

                <div class="hero-stagger pt-2 flex flex-wrap gap-3.5">
                    <a href="#login-admin" class="px-6 py-3.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-extrabold text-sm transition shadow-lg shadow-teal-500/25 hover:-translate-y-0.5">
                        Masuk ke Halaman Admin →
                    </a>
                    <a href="#alur-kripto" class="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-white font-semibold text-sm transition">
                        Cara Kerja Sistem
                    </a>
                </div>
            </div>

            <!-- Live Encryption Simulator Card -->
            <div class="lg:col-span-5 hero-stagger">
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/15 rounded-2xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3.5">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span class="text-xs font-mono text-slate-400 ml-1">live_cipher_preview.php</span>
                        </div>
                        <span id="simBadge" class="text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ENCRYPTED
                        </span>
                    </div>

                    <div class="space-y-3 text-xs font-mono">
                        <div class="bg-slate-950/90 p-3.5 rounded-xl border border-slate-800">
                            <p class="text-[11px] text-sky-400 mb-1">// Field 1: Identitas Pasien (RSA-2048)</p>
                            <p id="simFieldRsa" class="text-slate-200 break-all leading-relaxed">
                                Y9kL2mP8xQ4vR7nT1wZ5cB3jH6fD0sA8gK2lM9nP4qR7tV1xZ3bN6mQ8wE2rT5yU=
                            </p>
                        </div>

                        <div class="bg-slate-950/90 p-3.5 rounded-xl border border-slate-800">
                            <p class="text-[11px] text-teal-400 mb-1">// Field 2: Diagnosa Medis (AES-256-CBC)</p>
                            <p id="simFieldAes" class="text-slate-200 break-all leading-relaxed">
                                iv92f8a1c4e7b03d+U2FsdGVkX19xP8mK2vL4nQ7rT9wY1zC3bN5mJ8kL0pQ2rS4t=
                            </p>
                        </div>
                    </div>

                    <button type="button" onclick="toggleSimulasiHero()" class="w-full py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-xs font-semibold text-teal-200 transition">
                        Simulasikan Dekripsi / Enkripsi Live
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Arsitektur Keamanan -->
    <section id="alur-kripto" class="max-w-6xl mx-auto px-6 pt-14 pb-6">
        <div class="reveal-up mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-teal-600">Paradigma Autentikasi Baru</span>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1">Bagaimana Admin Mengakses Sistem?</h3>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="reveal-up spotlight-card bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 font-extrabold flex items-center justify-center mb-4">01</div>
                <h4 class="font-bold text-slate-900">Input Kunci Simetris (AES-256)</h4>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Admin memasukkan kunci rahasia simetris untuk mendekripsi token tantangan server sekaligus membuka kunci enkripsi diagnosa medis.</p>
            </div>
            <div class="reveal-up spotlight-card bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 font-extrabold flex items-center justify-center mb-4">02</div>
                <h4 class="font-bold text-slate-900">Input Kunci Asimetris (RSA-2048)</h4>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Admin memasukkan pasangan <em>Private Key</em> yang cocok dengan <em>Public Key</em> yang tersimpan pada konfigurasi database klinik.</p>
            </div>
            <div class="reveal-up spotlight-card bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 font-extrabold flex items-center justify-center mb-4">03</div>
                <h4 class="font-bold text-slate-900">Penyimpanan & Tampilan Ciphertext</h4>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Seluruh data rekam medis yang disimpan otomatis berubah menjadi <em>ciphertext</em> di database dan ditampilkan di dashboard admin.</p>
            </div>
        </div>
    </section>

    <!-- Section Layanan Poli -->
    <section id="layanan" class="max-w-6xl mx-auto px-6 py-10">
        <div class="reveal-up mb-7">
            <span class="text-xs font-bold uppercase tracking-wider text-teal-600">Unit Pelayanan</span>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1">Poliklinik Terintegrasi</h3>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="reveal-up spotlight-card bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-md">POLI 01</span>
                <h4 class="font-bold text-slate-900 mt-3">Poli Umum</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Pemeriksaan kesehatan umum, konsultasi gejala akut, dan surat keterangan sehat.</p>
            </div>
            <div class="reveal-up spotlight-card bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-[11px] font-bold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-md">POLI 02</span>
                <h4 class="font-bold text-slate-900 mt-3">Poli Penyakit Dalam</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Penanganan hipertensi, diabetes melitus, gangguan metabolik, dan pemeriksaan EKG.</p>
            </div>
            <div class="reveal-up spotlight-card bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">POLI 03</span>
                <h4 class="font-bold text-slate-900 mt-3">Poli Gigi & Mulut</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Perawatan konservasi gigi, scaling, penambalan, dan bedah minor mulut.</p>
            </div>
            <div class="reveal-up spotlight-card bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md">POLI 04</span>
                <h4 class="font-bold text-slate-900 mt-3">Poli Kesehatan Anak</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Pemantauan tumbuh kembang balita, imunisasi dasar, dan konsultasi gizi anak.</p>
            </div>
        </div>
    </section>

    <!-- Section Gerbang Masuk Admin -->
    <section id="login-admin" class="max-w-6xl mx-auto px-6 pb-16 pt-6">
        <div class="reveal-up bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/60 overflow-hidden grid lg:grid-cols-12">
            
            <!-- Sisi Kiri: Panel Kunci Demo Presentasi -->
            <div class="lg:col-span-5 bg-slate-900 text-slate-100 p-8 flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <span class="inline-block px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-semibold">
                        Zero-Password Admin Gate
                    </span>
                    <h3 class="text-2xl font-bold leading-snug">Gerbang Masuk Administrator</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Akses dashboard hanya diberikan apabila kombinasi <strong>Kunci Simetris</strong> dan <strong>Kunci Asimetris</strong> valid mendekripsi token server.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <button type="button" onclick="toggleDemoPanel()" class="text-xs font-semibold text-amber-400 hover:text-amber-300 underline">
                            Tampilkan / Sembunyikan Kunci Demo
                        </button>
                    </div>

                    <div id="panelDemo" class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] font-semibold text-slate-400">Kunci Aktif Sistem:</span>
                            <form method="POST">
                                <button type="submit" name="reset_kunci_demo" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                                    Generate Ulang
                                </button>
                            </form>
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Kunci Simetris (AES-256):</label>
                            <input type="text" id="demoSym" readonly value="<?= htmlspecialchars($demoSym) ?>" class="w-full text-xs font-mono bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-teal-300">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Kunci Asimetris (RSA Private Key):</label>
                            <textarea id="demoPriv" readonly rows="3" class="w-full text-[10px] font-mono bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-sky-300"><?= htmlspecialchars($demoPriv) ?></textarea>
                        </div>
                        <button type="button" id="btnAutoFill" onclick="isiOtomatisKunci()" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition active:scale-95">
                            Isi Otomatis ke Form Login →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sisi Kanan: Form Input Kunci -->
            <div class="lg:col-span-7 p-8 sm:p-10">
                <h4 class="text-xl font-extrabold text-slate-900">Verifikasi Kunci Administrator</h4>
                <p class="text-xs text-slate-500 mt-1 mb-6">Masukkan pasangan Kunci Simetris dan Kunci Asimetris untuk membuka dashboard rekam medis.</p>

                <?php if ($pesanError): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                        <?= htmlspecialchars($pesanError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesanSukses): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                        <?= htmlspecialchars($pesanSukses) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            1. Kunci Simetris (Symmetric Secret Key)
                        </label>
                        <input type="text" name="kunci_simetris" id="inputSym" required
                            placeholder="Masukkan Kunci Simetris klinik..."
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 focus:border-teal-600 focus:ring-4 focus:ring-teal-500/10 focus:bg-white focus:outline-none px-4 py-3 text-sm font-mono text-slate-900 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            2. Kunci Asimetris (RSA Private Key PEM)
                        </label>
                        <textarea name="kunci_asimetris" id="inputPriv" rows="7" required
                            placeholder="-----BEGIN PRIVATE KEY-----
Tempelkan RSA Private Key di sini...
-----END PRIVATE KEY-----"
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 focus:border-teal-600 focus:ring-4 focus:ring-teal-500/10 focus:bg-white focus:outline-none px-4 py-3 text-xs font-mono text-slate-900 transition"></textarea>
                    </div>

                    <button type="submit" name="masuk_admin"
                        class="w-full py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm transition shadow-lg shadow-teal-600/20 active:scale-[0.99]">
                        Autentikasi Kunci & Buka Halaman Admin
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        © 2026 Klinik Medika Nusantara — Sistem Informasi Rekam Medis Terenkripsi
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        // Hero entrance stagger
        gsap.from(".hero-stagger", {
            y: 26,
            opacity: 0,
            duration: 0.75,
            stagger: 0.12,
            ease: "power3.out"
        });

        // Scroll reveal cards
        gsap.utils.toArray(".reveal-up").forEach((el) => {
            gsap.from(el, {
                scrollTrigger: {
                    trigger: el,
                    start: "top 88%",
                    toggleActions: "play none none none"
                },
                y: 28,
                opacity: 0,
                duration: 0.7,
                ease: "power2.out"
            });
        });

        // Cursor spotlight effect
        document.querySelectorAll(".spotlight-card").forEach(card => {
            card.addEventListener("mousemove", e => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty("--x", `${e.clientX - rect.left}px`);
                card.style.setProperty("--y", `${e.clientY - rect.top}px`);
            });
        });

        // Text scramble animation helper
        function scrambleText(el, finalText, duration = 450) {
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
            const start = performance.now();
            function frame(now) {
                const progress = Math.min((now - start) / duration, 1);
                const resolved = Math.floor(progress * finalText.length);
                let out = "";
                for (let i = 0; i < finalText.length; i++) {
                    if (i < resolved || finalText[i] === " ") {
                        out += finalText[i];
                    } else {
                        out += chars[Math.floor(Math.random() * chars.length)];
                    }
                }
                el.textContent = out;
                if (progress < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        }

        // Simulasi enkripsi/dekripsi live di hero
        let isSimulatedPlain = false;
        const simData = {
            cipherRsa: "Y9kL2mP8xQ4vR7nT1wZ5cB3jH6fD0sA8gK2lM9nP4qR7tV1xZ3bN6mQ8wE2rT5yU=",
            plainRsa: "3202140908990001 - Budi Santoso (NIK Terverifikasi)",
            cipherAes: "iv92f8a1c4e7b03d+U2FsdGVkX19xP8mK2vL4nQ7rT9wY1zC3bN5mJ8kL0pQ2rS4t=",
            plainAes: "Diagnosa: Hipertensi Grade 1. Resep: Amlodipine 5mg (1x1)."
        };

        function toggleSimulasiHero() {
            isSimulatedPlain = !isSimulatedPlain;
            const elRsa = document.getElementById("simFieldRsa");
            const elAes = document.getElementById("simFieldAes");
            const badge = document.getElementById("simBadge");

            if (isSimulatedPlain) {
                badge.textContent = "DECRYPTED (PLAINTEXT)";
                badge.className = "text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30";
                scrambleText(elRsa, simData.plainRsa);
                scrambleText(elAes, simData.plainAes);
            } else {
                badge.textContent = "ENCRYPTED (CIPHERTEXT)";
                badge.className = "text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30";
                scrambleText(elRsa, simData.cipherRsa);
                scrambleText(elAes, simData.cipherAes);
            }
        }

        function toggleDemoPanel() {
            const panel = document.getElementById("panelDemo");
            panel.classList.toggle("hidden");
            if (!panel.classList.contains("hidden")) {
                gsap.fromTo(panel, { opacity: 0, y: -8 }, { opacity: 1, y: 0, duration: 0.3 });
            }
        }

        function isiOtomatisKunci() {
            const inSym  = document.getElementById("inputSym");
            const inPriv = document.getElementById("inputPriv");
            const btn    = document.getElementById("btnAutoFill");

            inSym.value  = document.getElementById("demoSym").value;
            inPriv.value = document.getElementById("demoPriv").value;

            gsap.fromTo([inSym, inPriv],
                { scale: 0.985, borderColor: "#0d9488" },
                { scale: 1, borderColor: "#14b8a6", duration: 0.35, ease: "back.out(2)" }
            );

            btn.textContent = "Kunci Berhasil Disalin ke Form!";
            btn.classList.replace("bg-amber-500", "bg-emerald-400");
            setTimeout(() => {
                btn.innerHTML = "Isi Otomatis ke Form Login →";
                btn.classList.replace("bg-emerald-400", "bg-amber-500");
            }, 1800);
        }
    </script>
</body>
</html>