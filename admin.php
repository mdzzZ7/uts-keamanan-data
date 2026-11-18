<?php
require_once 'config.php';

if (!isset($_SESSION['admin_klinik_auth']) || $_SESSION['admin_klinik_auth'] !== true) {
    header('Location: index.php');
    exit;
}

$kunciSimetris  = $_SESSION['aktif_kunci_sim'];
$kunciPrivate   = $_SESSION['aktif_kunci_private'];
$kunciPublic    = $_SESSION['aktif_kunci_public'];

$notifikasi = '';

if (isset($_POST['simpan_rekam_medis'])) {
    $identitasPasien = trim($_POST['identitas_pasien'] ?? '');
    $poliTujuan      = trim($_POST['poli_tujuan'] ?? '');
    $diagnosaMedis   = trim($_POST['diagnosa_medis'] ?? '');

    if ($identitasPasien !== '' && $diagnosaMedis !== '') {
        $cipherIdentitas = enkripsiAsimetris($identitasPasien, $kunciPublic);
        $cipherDiagnosa  = enkripsiSimetris($diagnosaMedis, $kunciSimetris);

        if ($cipherIdentitas && $cipherDiagnosa) {
            $stmt = $conn->prepare("INSERT INTO rekam_medis (identitas_pasien_rsa, poli_tujuan, diagnosa_tindakan_aes) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $cipherIdentitas, $poliTujuan, $cipherDiagnosa);
            $stmt->execute();
            $notifikasi = "Data rekam medis baru berhasil dienkripsi (RSA-2048 & AES-256) dan disimpan ke database!";
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM rekam_medis WHERE id = $id");
    header('Location: admin.php');
    exit;
}

$query = $conn->query("SELECT * FROM rekam_medis ORDER BY id DESC");
$daftarRekamMedis = [];
while ($row = $query->fetch_assoc()) {
    $row['dekripsi_identitas'] = dekripsiAsimetris($row['identitas_pasien_rsa'], $kunciPrivate) ?: '[Gagal Dekripsi RSA]';
    $row['dekripsi_diagnosa']  = dekripsiSimetris($row['diagnosa_tindakan_aes'], $kunciSimetris) ?: '[Gagal Dekripsi AES]';
    $daftarRekamMedis[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Rekam Medis — Klinik Medika Nusantara</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
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
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen font-sans">

    <!-- Navbar Admin -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-6 py-4 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white flex items-center justify-center font-bold shadow-sm">
                    +
                </div>
                <div>
                    <h1 class="font-bold text-base text-slate-900">Panel Admin — Rekam Medis Terenkripsi</h1>
                    <p class="text-xs text-teal-600 font-semibold">Sesi Aktif: Terverifikasi via Kunci Simetris & Asimetris</p>
                </div>
            </div>
            <a href="logout.php" class="px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-xs font-bold transition">
                Tutup Sesi & Keluar
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-7">

        <?php if ($notifikasi): ?>
            <div class="admin-enter p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold shadow-sm">
                <?= htmlspecialchars($notifikasi) ?>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Statistik -->
        <div class="grid sm:grid-cols-3 gap-5">
            <div class="admin-enter bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <p class="text-xs text-slate-500 font-semibold">Total Arsip Rekam Medis</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1">
                    <span id="statCounter" data-target="<?= count($daftarRekamMedis) ?>">0</span> <span class="text-base font-bold text-slate-500">Pasien</span>
                </p>
            </div>
            <div class="admin-enter bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <p class="text-xs text-slate-500 font-semibold">Enkripsi Identitas Pasien</p>
                <p class="text-sm font-extrabold text-sky-700 mt-2">Asimetris (RSA-2048 Public Key)</p>
                <p class="text-[11px] text-slate-400 mt-0.5">OAEP Padding Base64</p>
            </div>
            <div class="admin-enter bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <p class="text-xs text-slate-500 font-semibold">Enkripsi Diagnosa & Resep</p>
                <p class="text-sm font-extrabold text-teal-700 mt-2">Simetris (AES-256-CBC)</p>
                <p class="text-[11px] text-slate-400 mt-0.5">128-bit Random IV + SHA-256 Key</p>
            </div>
        </div>

        <!-- Form Input Rekam Medis Baru -->
        <section class="admin-enter bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-extrabold text-slate-900">Input Data Rekam Medis Pasien Baru</h2>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Setiap data yang disimpan akan otomatis dienkripsi terlebih dahulu sebelum masuk ke database MySQL.</p>

            <form method="POST" onsubmit="animasiSubmit(this)" class="grid md:grid-cols-12 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NIK & Nama Pasien (RSA)</label>
                    <input type="text" name="identitas_pasien" required placeholder="Contoh: 3202140101980005 - Andi Wijaya"
                        class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2.5 text-sm focus:border-teal-600 focus:bg-white focus:outline-none transition">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Poli Tujuan</label>
                    <select name="poli_tujuan" class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2.5 text-sm focus:border-teal-600 focus:bg-white focus:outline-none transition">
                        <option value="Poli Umum">Poli Umum</option>
                        <option value="Poli Penyakit Dalam">Poli Penyakit Dalam</option>
                        <option value="Poli Gigi">Poli Gigi</option>
                        <option value="Poli Kesehatan Anak">Poli Kesehatan Anak</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Diagnosa, Tindakan & Resep (AES-256)</label>
                    <input type="text" name="diagnosa_medis" required placeholder="Contoh: Diagnosa: Gastritis Akut. Resep: Omeprazole..."
                        class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2.5 text-sm focus:border-teal-600 focus:bg-white focus:outline-none transition">
                </div>
                <div class="md:col-span-2 flex items-end">
                    <button type="submit" name="simpan_rekam_medis" id="btnSimpan"
                        class="w-full py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm transition shadow-sm active:scale-95">
                        Enkripsi & Simpan
                    </button>
                </div>
            </form>
        </section>

        <!-- Tabel Seluruh Konten Hasil Enkripsi -->
        <section class="admin-enter bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-5">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Seluruh Konten Hasil Enkripsi di Database</h2>
                    <p class="text-xs text-slate-500">Menampilkan output ciphertext dari hasil penyimpanan data sebelumnya.</p>
                </div>
                <button type="button" onclick="toggleSemuaDekripsi()"
                    class="px-4 py-2 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 text-xs font-bold transition active:scale-95">
                    Toggle Dekripsi Semua
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-600">
                            <th class="py-3.5 px-3">No</th>
                            <th class="py-3.5 px-3">Poli & Waktu</th>
                            <th class="py-3.5 px-3">Identitas Pasien (Hasil Enkripsi Asimetris)</th>
                            <th class="py-3.5 px-3">Diagnosa & Rekam Medis (Hasil Enkripsi Simetris)</th>
                            <th class="py-3.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs">
                        <?php $no = 1; foreach ($daftarRekamMedis as $item): ?>
                        <tr class="row-cipher hover:bg-slate-50/90 transition align-top">
                            <td class="py-4 px-3 font-bold text-slate-500"><?= $no++ ?></td>
                            <td class="py-4 px-3 whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 font-bold">
                                    <?= htmlspecialchars($item['poli_tujuan']) ?>
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1.5 font-mono"><?= $item['tanggal_periksa'] ?></div>
                            </td>
                            <td class="py-4 px-3 max-w-xs">
                                <div class="font-mono text-[11px] text-sky-900 bg-sky-50/70 p-2.5 rounded-xl border border-sky-200 break-all max-h-24 overflow-y-auto">
                                    <?= htmlspecialchars($item['identitas_pasien_rsa']) ?>
                                </div>
                                <div class="box-dekripsi hidden mt-2 p-2.5 rounded-xl bg-sky-600 text-white font-semibold shadow-sm">
                                    Plaintext: <span class="scramble-target" data-plain="<?= htmlspecialchars($item['dekripsi_identitas']) ?>"><?= htmlspecialchars($item['dekripsi_identitas']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-3 max-w-md">
                                <div class="font-mono text-[11px] text-teal-900 bg-teal-50/70 p-2.5 rounded-xl border border-teal-200 break-all max-h-24 overflow-y-auto">
                                    <?= htmlspecialchars($item['diagnosa_tindakan_aes']) ?>
                                </div>
                                <div class="box-dekripsi hidden mt-2 p-2.5 rounded-xl bg-teal-700 text-white font-semibold shadow-sm">
                                    Plaintext: <span class="scramble-target" data-plain="<?= htmlspecialchars($item['dekripsi_diagnosa']) ?>"><?= htmlspecialchars($item['dekripsi_diagnosa']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-3 text-right whitespace-nowrap space-y-2">
                                <button type="button" onclick="toggleBaris(this)"
                                    class="block w-full px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold transition active:scale-95">
                                    Uji Dekripsi
                                </button>
                                <a href="?hapus=<?= $item['id'] ?>" onclick="return confirm('Hapus data rekam medis ini?')"
                                    class="block w-full px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 text-center font-medium transition">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        // Entrance stagger animation
        gsap.from(".admin-enter", {
            y: 22,
            opacity: 0,
            duration: 0.65,
            stagger: 0.1,
            ease: "power3.out"
        });

        gsap.from(".row-cipher", {
            y: 16,
            opacity: 0,
            duration: 0.5,
            stagger: 0.07,
            delay: 0.25,
            ease: "power2.out"
        });

        // Animated counter
        const counterEl = document.getElementById("statCounter");
        if (counterEl) {
            const target = parseInt(counterEl.dataset.target || "0", 10);
            const obj = { val: 0 };
            gsap.to(obj, {
                val: target,
                duration: 0.9,
                ease: "power2.out",
                onUpdate: () => {
                    counterEl.textContent = Math.round(obj.val);
                }
            });
        }

        // Text scramble effect saat dekripsi dibuka
        function scrambleReveal(spanEl, duration = 420) {
            const finalText = spanEl.dataset.plain || spanEl.textContent;
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
            const start = performance.now();

            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const resolved = Math.floor(progress * finalText.length);
                let result = "";
                for (let i = 0; i < finalText.length; i++) {
                    if (i < resolved || finalText[i] === " ") {
                        result += finalText[i];
                    } else {
                        result += chars[Math.floor(Math.random() * chars.length)];
                    }
                }
                spanEl.textContent = result;
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        function revealBox(box) {
            box.classList.remove("hidden");
            gsap.fromTo(box, { opacity: 0, y: -6 }, { opacity: 1, y: 0, duration: 0.3, ease: "power2.out" });
            const targetSpan = box.querySelector(".scramble-target");
            if (targetSpan) scrambleReveal(targetSpan);
        }

        function toggleBaris(btn) {
            const tr = btn.closest("tr");
            tr.querySelectorAll(".box-dekripsi").forEach(box => {
                if (box.classList.contains("hidden")) {
                    revealBox(box);
                } else {
                    box.classList.add("hidden");
                }
            });
        }

        let allOpened = false;
        function toggleSemuaDekripsi() {
            allOpened = !allOpened;
            document.querySelectorAll(".box-dekripsi").forEach(box => {
                if (allOpened) {
                    revealBox(box);
                } else {
                    box.classList.add("hidden");
                }
            });
        }

        function animasiSubmit(form) {
            const btn = document.getElementById("btnSimpan");
            if (btn) {
                btn.textContent = "Mengenkripsi...";
                btn.classList.add("opacity-80");
            }
        }
    </script>
</body>
</html>