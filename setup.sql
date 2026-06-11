CREATE DATABASE IF NOT EXISTS proyek_web CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE proyek_web;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Mahasiswa') NOT NULL DEFAULT 'Mahasiswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengajuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    signature_data LONGTEXT NOT NULL,
    status ENUM('Draft','Submitted','Approved','Cancelled') NOT NULL DEFAULT 'Draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengajuan_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengajuan_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (pengajuan_id) REFERENCES pengajuan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin Sistem', 'admin@mail.test', '
-- ── Migration: ganti role Pegawai → Mahasiswa (jalankan jika DB sudah ada) ──
-- ALTER TABLE users MODIFY role ENUM('Admin','Mahasiswa') NOT NULL DEFAULT 'Mahasiswa';
-- UPDATE users SET role = 'Mahasiswa' WHERE role = 'Pegawai';