<?php
session_start();

$db_host = 'localhost';$db_user = 'root';
$db_pass = '';$db_name = 'db_klinik_medika';

$conn = new mysqli($db_host, $db_user,$db_pass);
if ($conn->connect_error) {
    die("Koneksi ke MySQL gagal: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db_name);

$conn->query("CREATE TABLE IF NOT EXISTS konfigurasi_kunci (
    id INT PRIMARY KEY DEFAULT 1,
    public_key TEXT NOT NULL,
    token_simetris TEXT NOT NULL,
    token_asimetris TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS rekam_medis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identitas_pasien_rsa TEXT NOT NULL,
    poli_tujuan VARCHAR(100) NOT NULL,
    diagnosa_tindakan_aes TEXT NOT NULL,
    tanggal_periksa TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

define('CHALLENGE_TOKEN_KLINIK', 'OTORISASI_ADMIN_MEDIKA_2026');

function buatPasanganKunciRSA(): array {
    $config = [
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    $res = @openssl_pkey_new($config);

    if (!$res) {$tmpCnf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'medika_openssl.cnf';
        file_put_contents($tmpCnf, "[ req ]\ndistinguished_name = req_distinguished_name\n[ req_distinguished_name ]\n");
        $config['config'] =$tmpCnf;
        $res = openssl_pkey_new($config);
    }

    openssl_pkey_export($res, $privateKey, null,$config);
    $details   = openssl_pkey_get_details($res);
    $publicKey =$details['key'];

    return [
        'public_key'  => $publicKey,
        'private_key' => $privateKey
    ];
}

function enkripsiSimetris(string $plaintext, string $kunciSimetris): string {$key = hash('sha256', $kunciSimetris, true);$iv  = random_bytes(16);
    $ciphertextRaw = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv);
    return base64_encode($iv .$ciphertextRaw);
}

function dekripsiSimetris(string $ciphertextBase64, string $kunciSimetris): string|false {
    $raw = base64_decode($ciphertextBase64, true);
    if ($raw === false || strlen($raw) <= 16) {
        return false;
    }
    $key = hash('sha256', $kunciSimetris, true);
    $iv  = substr($raw, 0, 16);
    $ciphertextRaw = substr($raw, 16);
    return openssl_decrypt($ciphertextRaw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

function enkripsiAsimetris(string $plaintext, string$publicKey): string|false {
    if (@openssl_public_encrypt($plaintext, $encrypted,$publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
        return base64_encode($encrypted);
    }
    return false;
}

function dekripsiAsimetris(string $ciphertextBase64, string$privateKey): string|false {
    $raw = base64_decode($ciphertextBase64, true);
    if ($raw === false) {
        return false;
    }
    if (@openssl_private_decrypt($raw, $decrypted,$privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
        return $decrypted;
    }
    return false;
}

function inisialisasiSistemKlinik(mysqli $conn): array {$defaultSymKey = 'MedikaKlinik2026#Kunci';
    $rsaPair       = buatPasanganKunciRSA();
    $pubKey        =$rsaPair['public_key'];
    $privKey       =$rsaPair['private_key'];

    $tokenSym  = enkripsiSimetris(CHALLENGE_TOKEN_KLINIK,$defaultSymKey);
    $tokenAsym = enkripsiAsimetris(CHALLENGE_TOKEN_KLINIK,$pubKey);

    $stmt =$conn->prepare("REPLACE INTO konfigurasi_kunci (id, public_key, token_simetris, token_asimetris) VALUES (1, ?, ?, ?)");
    $stmt->bind_param("sss", $pubKey,$tokenSym, $tokenAsym);$stmt->execute();

    $cekData =$conn->query("SELECT COUNT(*) AS jml FROM rekam_medis")->fetch_assoc();
    if ((int)$cekData['jml'] === 0) {$sampelPasien = [
            [
                '3202140908990001 - Budi Santoso',
                'Poli Penyakit Dalam',
                'Diagnosa: Hipertensi Grade 1. Tindakan: Pemeriksaan EKG & observasi tekanan darah. Resep: Amlodipine 5mg (1x1).'
            ],
            [
                '3202145503010004 - Siti Aminah',
                'Poli Umum',
                'Diagnosa: Infeksi Saluran Pernapasan Akut (ISPA). Tindakan: Nebulisasi ringan. Resep: Paracetamol 500mg, Ambroxol.'
            ],
            [
                '3202141211950002 - Rendra Pratama',
                'Poli Gigi',
                'Diagnosa: Pulpitis Reversibel Molar 1 Kanan. Tindakan: Pembersihan kavitas & tambal sementara. Resep: Asam Mefenamat 500mg.'
            ]
        ];

        $ins =$conn->prepare("INSERT INTO rekam_medis (identitas_pasien_rsa, poli_tujuan, diagnosa_tindakan_aes) VALUES (?, ?, ?)");
        foreach ($sampelPasien as $pasien) {$encIdentitas = enkripsiAsimetris($pasien[0],$pubKey);
            $encDiagnosa  = enkripsiSimetris($pasien[2], $defaultSymKey);$ins->bind_param("sss", $encIdentitas,$pasien[1], $encDiagnosa);$ins->execute();
        }
    }

    $_SESSION['demo_kunci_simetris']  =$defaultSymKey;
    $_SESSION['demo_kunci_asimetris'] =$privKey;

    return [
        'sym_key'  => $defaultSymKey,
        'priv_key' => $privKey
    ];
}

$cekKonfig =$conn->query("SELECT * FROM konfigurasi_kunci WHERE id = 1")->fetch_assoc();
if (!$cekKonfig) {
    inisialisasiSistemKlinik($conn);
}