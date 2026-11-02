<?php
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama'] ?? '');
    $nim_nip    = trim($_POST['nim_nip'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (!csrf_verify()) {
        $error = "Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.";
    } elseif ($nama === '' || $nim_nip === '' || $email === '' || $password === '') {
        $error = "Seluruh kolom formulir wajib diisi.";
    } elseif (!preg_match('/^[A-Za-z0-9]{4,20}$/', $nim_nip)) {
        $error = "NIM/NIP harus 4–20 karakter huruf atau angka tanpa spasi.";
    } elseif (mb_strlen($nama) > 100 || mb_strlen($email) > 100) {
        $error = "Nama atau email terlalu panjang.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format alamat email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Kata sandi minimal terdiri dari 6 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi kata sandi tidak cocok.";
    } else {
        // Cek apakah NIM/NIP atau Email sudah terdaftar
        $cek = $pdo->prepare("SELECT id FROM users WHERE nim_nip = ? OR email = ? LIMIT 1");
        $cek->execute([$nim_nip, $email]);

        if ($cek->fetch()) {
            $error = "NIM/NIP atau Email tersebut sudah terdaftar di sistem.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("INSERT INTO users (nama, nim_nip, email, password, role) VALUES (?, ?, ?, ?, 'mahasiswa')");
            $insert->execute([$nama, $nim_nip, $email, $hash]);

            // Auto-login setelah registrasi berhasil
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['nama']    = $nama;
            $_SESSION['nim_nip'] = $nim_nip;
            $_SESSION['role']    = 'mahasiswa';

            flash_set('success', 'Registrasi berhasil. Selamat datang di AcadeME!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Registrasi — AcadeME';
include 'header.php';
?>

<!-- SECTION REGISTER DENGAN TAMPILAN IDENTIK BERANDA -->
<section class="section-full justify-content-center" style="min-height: 100vh; position: relative; overflow: hidden;">
    
    <!-- Komponen DarkVeil Background Navy (#000080) -->
    <div class="dark-veil-wrapper" 
         data-dark-veil 
         data-color="#000080" 
         data-speed="0.65" 
         data-warp="0.45">
    </div>

    <div class="container position-relative" style="z-index: 3;">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-6"
                 data-animated-content
                 data-distance="100"
                 data-direction="vertical"
                 data-duration="0.8"
                 data-ease="power3.out"
                 data-blur="0"
                 data-stagger="0.08"
                 data-threshold="0.1">

                <!-- Header Teks ala Hero Beranda -->
                <div class="text-center mb-4">
                    <span class="hero-overline mb-2">PENDAFTARAN ENTITAS BARU</span>
                    <h1 class="fw-bold text-white mb-2" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 8px; font-size: 2.5rem; text-shadow: 0 15px 45px rgba(0, 0, 128, 0.85);">REGISTRASI</h1>
                    <p class="hero-subtitle mb-0" style="font-size: 0.7rem; letter-spacing: 3px;">BUAT AKUN PORTAL AKADEMIK ACADEME</p>
                </div>

                <!-- Kotak Form Glassmorphism Navy -->
                <div class="contact-box p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-white py-2 small mb-4 text-center">
                            <i class="bi bi-exclamation-triangle me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">NAMA LENGKAP</label>
                                <input type="text" name="nama" maxlength="100" class="form-control input-minimal" placeholder="Nama Mahasiswa" value="<?= e($_POST['nama'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">NIM / NIP</label>
                                <input type="text" name="nim_nip" maxlength="20" class="form-control input-minimal" placeholder="Contoh: 19250444" value="<?= e($_POST['nim_nip'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">EMAIL AKADEMIK</label>
                            <input type="email" name="email" maxlength="100" class="form-control input-minimal" placeholder="nama@academe.id" value="<?= e($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">KATA SANDI</label>
                                <input type="password" name="password" minlength="6" class="form-control input-minimal" placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">KONFIRMASI SANDI</label>
                                <input type="password" name="konfirmasi_password" minlength="6" class="form-control input-minimal" placeholder="Ulangi kata sandi" autocomplete="new-password" required>
                            </div>
                        </div>

                        <button type="submit" 
                                class="specular-button specular-button--lg w-100"
                                data-specular-button
                                data-size="lg"
                                data-radius="18"
                                data-tint="#000080"
                                data-tint-opacity="0.3"
                                data-text-color="#f0f4ff"
                                data-line-color="#8ab4f8"
                                data-base-color="#1e3a8a"
                                data-intensity="1.25"
                                data-idle-intensity="0.45"
                                data-shine-size="12"
                                data-shine-fade="36"
                                data-thickness="1.4"
                                data-glow="1.2"
                                data-proximity="260">
                            DAFTAR AKUN SEKARANG &rarr;
                        </button>
                    </form>

                    <!-- Link kembali ke Login -->
                    <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(100, 149, 237, 0.2) !important;">
                        <span class="small" style="color: #8da2c0; font-size: 0.75rem;">Sudah memiliki akses portal?</span>
                        <a href="login.php" class="text-white text-decoration-none fw-bold ms-1" style="font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid #8ab4f8;">MASUK DI SINI &rarr;</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Ikon Sosial Vertikal di Kiri Bawah -->
    <div class="hero-socials">
        <a href="https://github.com" target="_blank" title="GitHub"><i class="bi bi-github"></i></a>
        <a href="index.php" title="Beranda"><i class="bi bi-globe2"></i></a>
        <a href="index.php#contact" title="Contact"><i class="bi bi-envelope"></i></a>
    </div>

    <!-- Indikator Sudut -->
    <div class="section-indicator">.02 // REG</div>
    <div class="scroll-indicator">
        <span>REGISTER</span>
        <div class="scroll-line"></div>
    </div>
</section>

<?php include 'footer.php'; ?>