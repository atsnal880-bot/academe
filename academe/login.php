<?php
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password  = $_POST['password'] ?? '';   // jangan di-trim: spasi bisa jadi bagian password

    if (!csrf_verify()) {
        $error = "Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR nim_nip = ? LIMIT 1");
        $stmt->execute([$identitas, $identitas]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);   // cegah session fixation
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['nim_nip'] = $user['nim_nip'];
            $_SESSION['role']    = $user['role'];
            redirect('dashboard.php');
        }
        $error = "NIM/NIP/Email atau kata sandi tidak sesuai.";
    }
}
$flash = flash_get();
$pageTitle = 'Login — AcadeME';

include 'header.php';
?>

<!-- SECTION LOGIN DENGAN TAMPILAN IDENTIK REGISTRASI -->
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
            <div class="col-md-8 col-lg-5"
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
                    <span class="hero-overline mb-2">AKSES PORTAL AKADEMIK</span>
                    <h1 class="fw-bold text-white mb-2" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 8px; font-size: 2.5rem; text-shadow: 0 15px 45px rgba(0, 0, 128, 0.85);">LOGIN</h1>
                    <p class="hero-subtitle mb-0" style="font-size: 0.7rem; letter-spacing: 3px;">MASUK KE PORTAL AKADEMIK ACADEME</p>
                </div>

                <!-- Kotak Form Glassmorphism Navy -->
                <div class="contact-box p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-white py-2 small mb-4 text-center">
                            <i class="bi bi-exclamation-triangle me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($flash): ?>
                        <div class="alert alert-info bg-primary bg-opacity-10 border-info border-opacity-25 text-white py-2 small mb-4 text-center">
                            <i class="bi bi-info-circle me-1 text-info"></i> <?= e($flash['message']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="inputIdentitas" class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">NIM / NIP / EMAIL</label>
                            <input type="text" id="inputIdentitas" name="identitas" class="form-control input-minimal" placeholder="Contoh: 19250444" value="<?= e($_POST['identitas'] ?? '') ?>" required autofocus autocomplete="username">
                        </div>

                        <div class="mb-5">
                            <label for="inputPassword" class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.65rem; color: #8ab4f8;">KATA SANDI</label>
                            <input type="password" id="inputPassword" name="password" class="form-control input-minimal" placeholder="Masukkan kata sandi" required autocomplete="current-password">
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
                            MASUK KE PORTAL &rarr;
                        </button>
                    </form>

                    <!-- Link ke Registrasi -->
                    <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(100, 149, 237, 0.2) !important;">
                        <span class="small" style="color: #8da2c0; font-size: 0.75rem;">Belum memiliki akun?</span>
                        <a href="register.php" class="text-white text-decoration-none fw-bold ms-1" style="font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid #8ab4f8;">DAFTAR DI SINI &rarr;</a>
                    </div>

                    <!-- Akun demo: HAPUS blok ini saat aplikasi dipublikasikan -->
                    <div class="mt-4 pt-3 border-top" style="border-color: rgba(100, 149, 237, 0.2) !important;">
                        <p class="text-center mb-2" style="color: #8da2c0; font-size: 0.68rem; letter-spacing: 2px;">ISI OTOMATIS (DEMO)</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm w-50 text-white" style="border: 1px solid rgba(138,180,248,.35); font-size: 0.7rem; letter-spacing: 1.5px;" onclick="isiDemo('19250444', 'password123')">
                                <i class="bi bi-person-badge me-1"></i> MAHASISWA
                            </button>
                            <button type="button" class="btn btn-sm w-50 text-white" style="border: 1px solid rgba(138,180,248,.35); font-size: 0.7rem; letter-spacing: 1.5px;" onclick="isiDemo('ADM001', 'password123')">
                                <i class="bi bi-shield-lock me-1"></i> ADMIN
                            </button>
                        </div>
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
    <div class="section-indicator">.01 // LOGIN</div>
    <div class="scroll-indicator">
        <span>LOGIN</span>
        <div class="scroll-line"></div>
    </div>
</section>

<script>
    function isiDemo(user, pass) {
        document.getElementById('inputIdentitas').value = user;
        document.getElementById('inputPassword').value = pass;
    }
</script>

<?php include 'footer.php'; ?>  