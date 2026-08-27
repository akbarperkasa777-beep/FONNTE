-- =========================================================
-- Database: project_api_whatsapp
-- Tugas: Integrasi Fonnte untuk Notifikasi Setelah Registrasi
-- =========================================================

CREATE DATABASE IF NOT EXISTS project_api_whatsapp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE project_api_whatsapp;

-- Tabel users (Tahap D.2 - Data Disimpan ke Database)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    no_hp VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel log_whatsapp (Fitur Tambahan Q.2 - Log Pengiriman WhatsApp)
-- status: pending | success | failed  (Tantangan X: Terkirim / Gagal / Pending)
CREATE TABLE IF NOT EXISTS log_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    no_tujuan VARCHAR(20) NOT NULL,
    pesan TEXT NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_whatsapp_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Index bantu untuk halaman Laporan Notifikasi WhatsApp (Tantangan X)
CREATE INDEX idx_log_whatsapp_user ON log_whatsapp(user_id);
CREATE INDEX idx_log_whatsapp_status ON log_whatsapp(status);
