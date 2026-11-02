CREATE DATABASE IF NOT EXISTS academe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academe_db;

-- 1. Tabel Users (Role: admin / mahasiswa)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim_nip VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mahasiswa') DEFAULT 'mahasiswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Absensi
CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Tabel Jadwal
CREATE TABLE jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(15) NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    hari VARCHAR(15) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruangan VARCHAR(30) NOT NULL,
    dosen VARCHAR(100) NOT NULL
);

-- 4. Tabel Tugas (Target CRUD 1)
CREATE TABLE tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    judul_tugas VARCHAR(150) NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('Belum Selesai', 'Proses', 'Selesai') DEFAULT 'Belum Selesai',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Tabel Transkrip (Target CRUD 2)
CREATE TABLE transkrip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kode_mk VARCHAR(15) NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    nilai_huruf ENUM('A', 'B', 'C', 'D', 'E') NOT NULL,
    bobot DECIMAL(3,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Tabel Pesan Kontak (Dari Landing Page Section .03)
CREATE TABLE pesan_kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telepon VARCHAR(25) NOT NULL,
    email VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seeding Akun Demo (Password: password123)
INSERT INTO users (nim_nip, nama, email, password, role) VALUES
('ADM001', 'Administrator Akademik', 'admin@academe.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('19250444', 'Mahasiswa Sistem Informasi', 'mahasiswa@academe.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa');

-- Seeding Data Awal Jadwal, Tugas & Transkrip
INSERT INTO jadwal (kode_mk, mata_kuliah, hari, jam_mulai, jam_selesai, ruangan, dosen) VALUES
('SI201', 'Pemrograman Web Lanjut', 'Senin', '08:00:00', '10:30:00', 'Lab 301', 'Dr. Hendra Kusuma'),
('SI204', 'Sistem Basis Data', 'Rabu', '13:00:00', '15:30:00', 'R. 402', 'Ir. Diana Putri, M.Kom');

INSERT INTO tugas (user_id, mata_kuliah, judul_tugas, deadline, status) VALUES
(2, 'Pemrograman Web Lanjut', 'Implementasi Arsitektur PDO & Bootstrap 5', '2026-10-25', 'Proses'),
(2, 'Sistem Basis Data', 'Perancangan Skema Relasional ERD', '2026-10-28', 'Selesai');

INSERT INTO transkrip (user_id, kode_mk, mata_kuliah, sks, nilai_huruf, bobot) VALUES
(2, 'SI101', 'Algoritma & Struktur Data', 4, 'A', 4.00),
(2, 'SI102', 'Konsep Sistem Informasi', 3, 'A', 4.00),
(2, 'SI105', 'Statistika & Probabilitas', 3, 'B', 3.00);