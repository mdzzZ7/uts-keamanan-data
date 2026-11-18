CREATE DATABASE IF NOT EXISTS db_klinik_medika;
USE db_klinik_medika;

CREATE TABLE IF NOT EXISTS konfigurasi_kunci (
    id INT PRIMARY KEY DEFAULT 1,
    public_key TEXT NOT NULL,
    token_simetris TEXT NOT NULL,
    token_asimetris TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rekam_medis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identitas_pasien_rsa TEXT NOT NULL,
    poli_tujuan VARCHAR(100) NOT NULL,
    diagnosa_tindakan_aes TEXT NOT NULL,
    tanggal_periksa TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;