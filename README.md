👨‍💻 Informasi Pengembang
Nama: Ahmad Yusuf Abdhillah
Program Studi: Teknik Informatika — Semester 5
Institusi: Universitas Muhammadiyah Sukabumi
Mata Kuliah: Keamanan Data (Project Ujian Tengah Semester)
---

<div align="center">

# 🏥 Klinik Medika Nusantara
### Sistem Informasi Rekam Medis Berbasis Hybrid Cryptography (AES-256 & RSA-2048)

![PHP](https://img.shields.io/badge/PHP-8.x_Native-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Security](https://img.shields.io/badge/Crypto-AES--256_+_RSA--2048-0D9488?style=for-the-badge&logo=letsencrypt&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-UI-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![GSAP](https://img.shields.io/badge/GSAP-Animations-88CE02?style=for-the-badge&logo=greensock&logoColor=black)

*Aplikasi Sistem Informasi Manajemen Rekam Medis yang menggantikan paradigma login password konvensional dengan autentikasi kunci kriptografi ganda serta penyimpanan data berbasis ciphertext.*

</div>

---

## 📌 Latar Belakang & Studi Kasus

Data rekam medis pasien—seperti **Nomor Induk Kependudukan (NIK)**, identitas pribadi, hingga **riwayat diagnosa dan resep obat**—merupakan informasi yang sangat rahasia. Jika aplikasi hanya mengandalkan login *username/password* biasa dan menyimpan data dalam bentuk *plaintext* di database, kebocoran pada sisi server akan langsung mengekspos privasi pasien.

**Klinik Medika Nusantara** dirancang untuk menjawab permasalahan tersebut dengan menerapkan dua lapisan keamanan utama:
1. **Zero-Password Authentication Paradigm:** Gerbang masuk halaman administrator tidak menggunakan *username* dan *password*, melainkan validasi kombinasi **Kunci Simetris** dan **Kunci Asimetris**.
2. **Database-Level Ciphertext Storage:** Seluruh data pasien yang diinput oleh admin langsung dienkripsi sebelum disimpan ke MySQL dan ditampilkan dalam format *ciphertext* pada dashboard admin.

---

## 🔐 Arsitektur Kriptografi (Hybrid Encryption)

Aplikasi ini mengombinasikan keunggulan algoritma simetris dan asimetris:

| Komponen / Data | Algoritma | Mode / Padding | Alasan Penerapan |
| :--- | :--- | :--- | :--- |
| **Autentikasi Sesi & Diagnosa Medis** | **AES-256** (Simetris) | `AES-256-CBC` + Random 16-byte IV + `SHA-256` Key Derivation | Proses enkripsi sangat cepat dan efisien untuk teks catatan medis/resep yang panjang, dengan *Initialization Vector* acak agar *ciphertext* selalu unik. |
| **Validasi Admin & Identitas Pasien (NIK)** | **RSA-2048** (Asimetris) | `OPENSSL_PKCS1_OAEP_PADDING` (Public & Private Key Pair) | Memberikan otorisasi berbasis pasangan kunci privat-publik yang kuat untuk melindungi identitas krusial pasien. |

### Alur Kerja Sistem
```text
[Landing Page / Portal Admin]
       │
       ├──► Input Kunci Simetris (AES-256) ──────► Dekripsi Token Tantangan AES
       └──► Input Private Key (RSA-2048) ────────► Dekripsi Token Tantangan RSA
                                                          │
                                               (Kedua Token Valid?)
                                                          │
                                                          ▼
                                             [Dashboard Admin Terbuka]
                                                          │
       ┌──────────────────────────────────────────────────┴──────────────────────────────────────────────────┐
       ▼                                                                                                     ▼
[Input Data Pasien Baru]                                                                       [Tabel Rekam Medis Database]
 • NIK & Nama ──► Enkripsi RSA Public Key                                                       • Menampilkan seluruh data
 • Diagnosa   ──► Enkripsi AES-256-CBC                                                            dalam bentuk Ciphertext
       │                                                                                        • Fitur Uji Dekripsi (Live)
       └──────────────────────────────► Simpan ke MySQL (`db_klinik_medika`) ◄───────────────────────────────┘
---

🗂️ Struktur Proyek & Database
Struktur Direktori
Plaintext
si-rekam-medis/
├── index.php        # Landing page informasi klinik & gerbang login Dual-Key
├── admin.php        # Dashboard admin (input data auto-encrypt & tabel ciphertext)
├── config.php       # Koneksi MySQL, auto-seeder kunci, & mesin kriptografi OpenSSL
├── logout.php       # Penghapusan sesi otorisasi administrator
├── database.sql     # Skema database db_klinik_medika
└── README.md        # Dokumentasi proyek
Skema Database (db_klinik_medika)
konfigurasi_kunci: Menyimpan public_key RSA-2048 milik server serta token_simetris dan token_asimetris terenkripsi sebagai validator saat admin melakukan login.

rekam_medis: Menyimpan data kunjungan pasien dengan kolom identitas_pasien_rsa (hasil enkripsi asimetris) dan diagnosa_tindakan_aes (hasil enkripsi simetris).

🚀 Cara Menjalankan di Lokal (Laragon)
Clone atau Unduh Repositori
Simpan folder proyek ke dalam direktori document root Laragon:

Bash
C:\laragon\www\si-rekam-medis
Aktifkan Servis Server
Buka aplikasi Laragon dan jalankan servis Apache serta MySQL.

Import Database

Buka phpMyAdmin (http://localhost/phpmyadmin).

Buat database baru atau langsung Import file database.sql untuk membentuk database db_klinik_medika.

Akses Aplikasi
Buka browser dan kunjungi:

Plaintext
http://localhost/si-rekam-medis
(Saat pertama kali diakses, sistem otomatis membangkitkan pasangan kunci RSA-2048 dan mengisi 3 data sampel rekam medis terenkripsi).

🧪 Panduan Pengujian (Demo Presentasi)
Pada bagian Gerbang Masuk Administrator di halaman utama (index.php), klik tombol "Isi Otomatis ke Form Login" pada panel kunci demo (atau masukkan pasangan Kunci Simetris dan RSA Private Key secara manual).

Klik "Autentikasi Kunci & Buka Halaman Admin" untuk masuk ke admin.php.

Tambahkan data rekam medis baru melalui form input, lalu amati bahwa data yang tersimpan dan tampil pada tabel langsung berbentuk Ciphertext (Base64).

Klik tombol "Uji Dekripsi" pada baris data untuk memverifikasi bahwa ciphertext dapat dikembalikan ke bentuk teks asli menggunakan pasangan kunci aktif.
