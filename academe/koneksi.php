<?php
/**
 * koneksi.php — koneksi database, setup otomatis, dan fungsi bantu.
 * Dipakai oleh semua halaman lewat require_once 'koneksi.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ---------- KONFIGURASI (default Laragon) ----------
$host = '127.0.0.1';
$db   = 'academe_db';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ---------- KONEKSI ----------
try {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $options);
    } catch (PDOException $e) {
        // Database belum ada (error 1049) -> buat dulu, lalu sambungkan ulang
        if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
            $tmp = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, $options);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tmp = null;
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $options);
        } else {
            throw $e;
        }
    }

    // ---------- SETUP TABEL + DATA AWAL (hanya sekali per sesi browser) ----------
    if (empty($_SESSION['db_ready'])) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nim_nip VARCHAR(20) NOT NULL UNIQUE,
                nama VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'mahasiswa') DEFAULT 'mahasiswa',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS absensi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mata_kuliah VARCHAR(100) NOT NULL,
                tanggal DATE NOT NULL,
                status ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS jadwal (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kode_mk VARCHAR(15) NOT NULL,
                mata_kuliah VARCHAR(100) NOT NULL,
                hari VARCHAR(15) NOT NULL,
                jam_mulai TIME NOT NULL,
                jam_selesai TIME NOT NULL,
                ruangan VARCHAR(30) NOT NULL,
                dosen VARCHAR(100) NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tugas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mata_kuliah VARCHAR(100) NOT NULL,
                judul_tugas VARCHAR(150) NOT NULL,
                deadline DATE NOT NULL,
                status ENUM('Belum Selesai', 'Proses', 'Selesai') DEFAULT 'Belum Selesai',
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS transkrip (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                kode_mk VARCHAR(15) NOT NULL,
                mata_kuliah VARCHAR(100) NOT NULL,
                sks INT NOT NULL,
                nilai_huruf ENUM('A', 'B', 'C', 'D', 'E') NOT NULL,
                bobot DECIMAL(3,2) NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS pesan_kontak (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                telepon VARCHAR(25) NOT NULL,
                email VARCHAR(100) NOT NULL,
                pesan TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
        ];
        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        // Data awal hanya diisi jika tabel users masih kosong
        if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nim_nip, nama, email, password, role) VALUES
                ('ADM001', 'Administrator Akademik', 'admin@academe.ac.id', ?, 'admin'),
                ('19250444', 'Muhammad Atsnal Ula', 'mahasiswa@academe.ac.id', ?, 'mahasiswa')");
            $stmt->execute([$hash, $hash]);

            $pdo->exec("INSERT INTO jadwal (kode_mk, mata_kuliah, hari, jam_mulai, jam_selesai, ruangan, dosen) VALUES
                ('SI201', 'Pemrograman Web Lanjut', 'Senin', '08:00:00', '10:30:00', 'Lab 301', 'Dr. Hendra Kusuma, M.Kom'),
                ('SI204', 'Aplikasi Basis Data', 'Rabu', '13:00:00', '15:30:00', 'R. 402', 'Ir. Diana Putri, M.T'),
                ('SI208', 'Statistika & Probabilitas', 'Jumat', '09:00:00', '11:30:00', 'R. 205', 'Budi Santoso, M.Si')");

            $pdo->exec("INSERT INTO absensi (user_id, mata_kuliah, tanggal, status) VALUES
                (2, 'Pemrograman Web Lanjut', '2026-10-12', 'Hadir'),
                (2, 'Aplikasi Basis Data', '2026-10-14', 'Hadir'),
                (2, 'Statistika & Probabilitas', '2026-10-16', 'Izin')");

            $pdo->exec("INSERT INTO tugas (user_id, mata_kuliah, judul_tugas, deadline, status) VALUES
                (2, 'Pemrograman Web Lanjut', 'Membangun Portal Akademik AcadeME Full-Stack', '2026-10-25', 'Proses'),
                (2, 'Aplikasi Basis Data', 'Perancangan Skema Relasional & ERD', '2026-10-22', 'Selesai'),
                (2, 'Statistika & Probabilitas', 'Analisis Kuesioner Discovery Learning', '2026-10-30', 'Belum Selesai')");

            $pdo->exec("INSERT INTO transkrip (user_id, kode_mk, mata_kuliah, sks, nilai_huruf, bobot) VALUES
                (2, 'SI101', 'Algoritma & Pemrograman', 4, 'A', 4.00),
                (2, 'SI102', 'Konsep Sistem Informasi', 3, 'A', 4.00),
                (2, 'SI104', 'Konsep Basis Data', 3, 'A', 4.00),
                (2, 'SI105', 'Matematika Diskrit', 3, 'B', 3.00)");
        }

        // Perbaikan satu kali: jika akun demo masih memakai hash lama yang salah
        // (hash bawaan versi database.sql lama), ganti ke hash 'password123' yang benar.
        $hash_lama = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $fix = $pdo->prepare("UPDATE users SET password = ? WHERE password = ?");
        $fix->execute([password_hash('password123', PASSWORD_DEFAULT), $hash_lama]);

        $_SESSION['db_ready'] = true;
    }
} catch (PDOException $e) {
    http_response_code(500);
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        . "<br>Pastikan MySQL di Laragon sudah berjalan (Start All).");
}

// =====================================================
// FUNGSI BANTU
// =====================================================

/** Escape output HTML (anti XSS). */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Redirect lalu berhenti. */
function redirect($url) {
    header("Location: $url");
    exit;
}

/** Token CSRF per sesi. */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Input hidden CSRF untuk ditaruh di dalam <form method="POST">. */
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Cek token CSRF dari $_POST. */
function csrf_verify() {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/** Simpan pesan sekali-tampil (flash) lintas redirect. */
function flash_set($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Ambil lalu hapus pesan flash. */
function flash_get() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Wajib login; kalau belum, lempar ke halaman login. */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}