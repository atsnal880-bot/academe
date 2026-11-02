<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error   = '';
$sukses  = '';

// Ambil data pengguna terbaru dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$isAdmin = ($user['role'] === 'admin');

// =====================================================
// PROSES UPDATE PROFIL & PASSWORD
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Update Data Profil (Nama & Email)
    if (isset($_POST['update_profil'])) {
        $nama_baru  = trim($_POST['nama'] ?? '');
        $email_baru = trim($_POST['email'] ?? '');

        if ($nama_baru === '' || $email_baru === '') {
            $error = "Nama lengkap dan email akademik tidak boleh kosong.";
        } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email akademik tidak valid.";
        } else {
            $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $cek->execute([$email_baru, $user_id]);

            if ($cek->fetch()) {
                $error = "Email tersebut sudah digunakan oleh akun lain.";
            } else {
                $upd = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
                if ($upd->execute([$nama_baru, $email_baru, $user_id])) {
                    $_SESSION['nama'] = $nama_baru;
                    $user['nama']     = $nama_baru;
                    $user['email']    = $email_baru;
                    $sukses = "Informasi biodata berhasil diperbarui.";
                } else {
                    $error = "Gagal memperbarui profil.";
                }
            }
        }
    }

    // 2. Update Kata Sandi
    if (isset($_POST['update_password'])) {
        $pass_lama       = $_POST['password_lama'] ?? '';
        $pass_baru       = $_POST['password_baru'] ?? '';
        $pass_konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if ($pass_lama === '' || $pass_baru === '' || $pass_konfirmasi === '') {
            $error = "Seluruh kolom perubahan kata sandi wajib diisi.";
        } elseif (!password_verify($pass_lama, $user['password'])) {
            $error = "Kata sandi saat ini tidak sesuai.";
        } elseif (strlen($pass_baru) < 6) {
            $error = "Kata sandi baru minimal terdiri dari 6 karakter.";
        } elseif ($pass_baru !== $pass_konfirmasi) {
            $error = "Konfirmasi kata sandi baru tidak cocok.";
        } else {
            $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
            $updPass   = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updPass->execute([$hash_baru, $user_id])) {
                $user['password'] = $hash_baru;
                $sukses = "Kata sandi keamanan akun berhasil diperbarui.";
            } else {
                $error = "Terjadi kesalahan saat memperbarui kata sandi.";
            }
        }
    }
}

// =====================================================
// AMBIL RINGKASAN METRIK (DIBEDAKAN ADMIN VS MAHASISWA)
// =====================================================
if ($isAdmin) {
    $total_mhs       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
    $total_jadwal    = (int)$pdo->query("SELECT COUNT(*) FROM jadwal")->fetchColumn();
    $total_transkrip = (int)$pdo->query("SELECT COUNT(*) FROM transkrip")->fetchColumn();
    $total_tugas_all = (int)$pdo->query("SELECT COUNT(*) FROM tugas")->fetchColumn();
} else {
    $stmtTugas = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE user_id = ?");
    $stmtTugas->execute([$user_id]);
    $total_tugas = (int)$stmtTugas->fetchColumn();

    $stmtTugasSelesai = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE user_id = ? AND status = 'Selesai'");
    $stmtTugasSelesai->execute([$user_id]);
    $tugas_selesai = (int)$stmtTugasSelesai->fetchColumn();
    $persen_tugas  = $total_tugas > 0 ? round(($tugas_selesai / $total_tugas) * 100) : 0;

    $stmtAbsen = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE user_id = ? AND status = 'Hadir'");
    $stmtAbsen->execute([$user_id]);
    $total_hadir = (int)$stmtAbsen->fetchColumn();

    $total_sks  = 0;
    $total_mutu = 0.0;
    try {
        $stmtTr = $pdo->prepare("SELECT sks, bobot FROM transkrip WHERE user_id = ?");
        $stmtTr->execute([$user_id]);
        foreach ($stmtTr->fetchAll() as $tr) {
            $total_sks  += (int)$tr['sks'];
            $total_mutu += ((int)$tr['sks'] * (float)$tr['bobot']);
        }
    } catch (Exception $e) {}
    $ipk_val    = $total_sks > 0 ? ($total_mutu / $total_sks) : 0;
    $ipk        = number_format($ipk_val, 2);
    $persen_ipk = min(100, round(($ipk_val / 4.00) * 100));
}

// Buat inisial nama untuk Avatar
$kata_nama  = preg_split('/\s+/', trim($user['nama']));
$inisial    = strtoupper(substr($kata_nama[0] ?? 'A', 0, 1) . (isset($kata_nama[1]) ? substr($kata_nama[1], 0, 1) : ''));
$tgl_gabung = !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : date('d M Y');

include 'header.php';
?>

<style>
    /* =====================================================
       STYLING KHUSUS HALAMAN PROFIL NAVY GLOW GLASS
       ===================================================== */
    @keyframes navyFadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .navy-reveal-1 { animation: navyFadeUp 0.6s var(--ease-smooth) both; }
    .navy-reveal-2 { animation: navyFadeUp 0.6s 0.1s var(--ease-smooth) both; }
    .navy-reveal-3 { animation: navyFadeUp 0.6s 0.2s var(--ease-smooth) both; }

    .profile-wrapper {
        opacity: 1 !important;
        visibility: visible !important;
        position: relative;
        z-index: 5;
    }

    .avatar-ring-wrap {
        position: relative;
        display: inline-block;
        margin-bottom: 1.25rem;
    }

    .avatar-circle {
        width: 106px;
        height: 106px;
        border-radius: 50%;
        background: rgba(0, 0, 128, 0.42);
        border: 1.5px solid rgba(138, 180, 248, 0.52);
        box-shadow: 
            inset 0 0 22px rgba(138, 180, 248, 0.25),
            0 14px 32px rgba(0, 0, 110, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-title, 'Montserrat', sans-serif);
        font-size: 2.15rem;
        font-weight: 900;
        color: #ffffff;
        letter-spacing: 2px;
        position: relative;
        z-index: 2;
    }

    .avatar-status-dot {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #10b981;
        border: 2.5px solid #020514;
        box-shadow: 0 0 10px #10b981;
        z-index: 3;
    }

    .navy-glass-panel {
        background: rgba(4, 12, 48, 0.68);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid var(--border-subtle);
        border-radius: 18px;
        padding: 2.2rem;
        box-shadow: 0 22px 50px rgba(0, 0, 45, 0.65);
        position: relative;
        overflow: hidden;
        transition: border-color 0.35s ease, transform 0.35s var(--ease-smooth);
    }

    .navy-glass-panel::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(460px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(138, 180, 248, 0.08), transparent 45%);
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
    }

    .navy-glass-panel:hover {
        border-color: rgba(138, 180, 248, 0.42);
    }

    .navy-glass-panel:hover::before {
        opacity: 1;
    }

    /* Tombol Glow Glass (Senada dengan Dashboard) */
    .btn-glow-glass {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        padding: 13px 26px;
        border-radius: 14px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.74rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #f4f7ff !important;
        text-decoration: none !important;
        background: rgba(0, 0, 128, 0.28);
        border: 1px solid rgba(138, 180, 248, 0.34);
        box-shadow: 
            inset 0 1px 0 0 rgba(255, 255, 255, 0.18),
            inset 0 0 16px rgba(138, 180, 248, 0.12),
            0 10px 24px -8px rgba(0, 0, 128, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        cursor: pointer;
        transition: all 0.3s var(--ease-smooth);
    }

    .btn-glow-glass:hover {
        background: rgba(15, 45, 160, 0.42);
        border-color: rgba(138, 180, 248, 0.75);
        box-shadow: 
            inset 0 1px 0 0 rgba(255, 255, 255, 0.3),
            inset 0 0 22px rgba(138, 180, 248, 0.25),
            0 14px 30px -6px rgba(30, 80, 220, 0.65);
        transform: translateY(-2px);
        color: #ffffff !important;
    }

    .btn-glow-glass:active {
        transform: scale(0.98);
    }

    .id-badge-card {
        background: rgba(2, 6, 28, 0.65);
        border: 1px solid rgba(138, 180, 248, 0.22);
        border-radius: 14px;
        padding: 15px 18px;
    }

    .stat-mini-box {
        background: rgba(2, 6, 28, 0.62);
        border: 1px solid rgba(138, 180, 248, 0.16);
        border-radius: 14px;
        padding: 15px 10px;
        transition: all 0.3s var(--ease-smooth);
    }

    .stat-mini-box:hover {
        border-color: var(--accent-blue);
        transform: translateY(-3px);
    }

    .navy-progress {
        height: 5px;
        background: rgba(138, 180, 248, 0.12);
        border-radius: 50px;
        overflow: hidden;
    }

    .navy-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #1e40af, #8ab4f8);
        border-radius: 50px;
    }

    .input-readonly {
        background: rgba(138, 180, 248, 0.04) !important;
        border: 1px dashed rgba(138, 180, 248, 0.26) !important;
        color: #8fa6cb !important;
        padding: 12px 16px !important;
        border-radius: 10px !important;
        font-size: 0.85rem;
        cursor: not-allowed;
    }

    .pass-toggle-wrap { position: relative; }
    .pass-toggle-btn {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: var(--text-muted-navy);
        font-size: 1rem;
        padding: 6px 10px;
        cursor: pointer;
    }
    .pass-toggle-btn:hover { color: #ffffff; }

    .navy-alert {
        background: rgba(4, 15, 60, 0.82);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(138, 180, 248, 0.35);
        border-radius: 14px;
        padding: 15px 22px;
        color: #f4f7ff;
    }

    .navy-alert--danger {
        background: rgba(80, 10, 30, 0.72);
        border-color: rgba(248, 113, 113, 0.45);
    }
</style>

<section class="section-full justify-content-center" style="min-height: 100vh; padding-top: 125px; padding-bottom: 95px;">
    <div class="container profile-wrapper">
        
        <!-- Header Halaman Profil -->
        <div class="text-center mb-5 navy-reveal-1">
            <div>
                <span class="hero-overline mb-2">
                    <i class="bi <?= $isAdmin ? 'bi-shield-lock' : 'bi-mortarboard' ?> me-2"></i>
                    <?= $isAdmin ? 'OTORITAS SISTEM ADMINISTRATOR' : 'IDENTITAS ENTITAS MAHASISWA' ?>
                </span>
            </div>
            <h1 class="hero-title mb-2" style="font-size: clamp(2.2rem, 4.5vw, 3.5rem);">
                PROFIL <?= $isAdmin ? 'ADMIN' : 'MAHASISWA' ?>
            </h1>
            <p class="hero-subtitle mb-0" style="font-size: 0.74rem; letter-spacing: 3.5px;">
                KELOLA DATA PRIBADI & OTORISASI KEAMANAN AKUN ACADEME
            </p>
        </div>

        <!-- Notifikasi Sukses / Error -->
        <?php if ($error): ?>
            <div class="navy-alert navy-alert--danger small mb-4 d-flex align-items-center justify-content-center gap-2 navy-reveal-1">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($sukses): ?>
            <div class="navy-alert small mb-4 d-flex align-items-center justify-content-center gap-2 navy-reveal-1">
                <i class="bi bi-check-circle-fill text-info fs-5"></i>
                <span><?= htmlspecialchars($sukses) ?></span>
            </div>
        <?php endif; ?>

        <div class="row g-4 align-items-stretch">

            <!-- KOLOM KIRI: KARTU IDENTITAS (DIBEDAKAN ADMIN VS MAHASISWA) -->
            <div class="col-lg-4 navy-reveal-2">
                <div class="navy-glass-panel h-100 text-center d-flex flex-column justify-content-between">
                    <div>
                        <div class="avatar-ring-wrap">
                            <div class="avatar-circle"><?= htmlspecialchars($inisial) ?></div>
                            <span class="avatar-status-dot" title="Sesi Aktif"></span>
                        </div>

                        <h4 class="text-white fw-bold mb-1" style="font-family: 'Space Grotesk', sans-serif;">
                            <?= htmlspecialchars($user['nama']) ?>
                        </h4>
                        <p class="small mb-3" style="color: var(--text-muted-navy);">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                        
                        <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                            <span class="badge rounded-pill px-3 py-2 text-uppercase" style="background: rgba(0, 0, 128, 0.45); border: 1px solid rgba(138, 180, 248, 0.4); color: #8ab4f8; letter-spacing: 2px; font-size: 0.64rem;">
                                <i class="bi <?= $isAdmin ? 'bi-shield-check' : 'bi-patch-check-fill' ?> me-1"></i> <?= htmlspecialchars($user['role']) ?>
                            </span>
                            <span class="badge rounded-pill px-3 py-2 text-uppercase" style="background: rgba(2, 6, 28, 0.7); border: 1px solid rgba(138, 180, 248, 0.2); color: #8fa6cb; letter-spacing: 1.5px; font-size: 0.64rem;">
                                <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($tgl_gabung) ?>
                            </span>
                        </div>

                        <!-- Nomor Induk Kartu -->
                        <div class="id-badge-card mb-4 text-start">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-uppercase" style="font-size: 0.62rem; letter-spacing: 2px; color: var(--text-muted-navy);">
                                    <?= $isAdmin ? 'KODE OTORITAS / NIP ADMIN' : 'NOMOR INDUK MAHASISWA (NIM)' ?>
                                </span>
                                <i class="bi bi-person-vcard text-info fs-5"></i>
                            </div>
                            <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 2px;">
                                <?= htmlspecialchars($user['nim_nip']) ?>
                            </div>
                        </div>

                        <?php if ($isAdmin): ?>
                            <!-- TAMPILAN KHUSUS ADMIN: Tanpa IPK, SKS, dan Kehadiran Pribadi -->
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_mhs ?></div>
                                        <div style="font-size: 0.58rem; letter-spacing: 1.5px; color: var(--text-muted-navy);">MAHASISWA AKTIF</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_jadwal ?></div>
                                        <div style="font-size: 0.58rem; letter-spacing: 1.5px; color: var(--text-muted-navy);">JADWAL KULIAH</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_transkrip ?></div>
                                        <div style="font-size: 0.58rem; letter-spacing: 1.5px; color: var(--text-muted-navy);">ENTRI TRANSKRIP</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_tugas_all ?></div>
                                        <div style="font-size: 0.58rem; letter-spacing: 1.5px; color: var(--text-muted-navy);">TOTAL TUGAS</div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 rounded-3 text-start mb-4" style="background: rgba(0, 0, 128, 0.22); border: 1px solid rgba(138, 180, 248, 0.22);">
                                <div class="small text-uppercase fw-bold mb-2" style="font-size: 0.62rem; letter-spacing: 1.8px; color: var(--accent-blue);">
                                    <i class="bi bi-cpu me-1"></i> HAK AKSES SISTEM
                                </div>
                                <ul class="list-unstyled small mb-0" style="color: var(--text-muted-navy); font-size: 0.76rem; line-height: 1.85;">
                                    <li><i class="bi bi-check2 text-info me-2"></i>Otorisasi Input & Revisi Transkrip</li>
                                    <li><i class="bi bi-check2 text-info me-2"></i>Manajemen Tugas & Presensi Global</li>
                                    <li><i class="bi bi-check2 text-info me-2"></i>Monitoring Direktori Mahasiswa</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <!-- TAMPILAN KHUSUS MAHASISWA: IPK, SKS, Tugas, Kehadiran -->
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $ipk ?></div>
                                        <div style="font-size: 0.6rem; letter-spacing: 1.8px; color: var(--text-muted-navy);">INDEKS IPK</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_sks ?></div>
                                        <div style="font-size: 0.6rem; letter-spacing: 1.8px; color: var(--text-muted-navy);">TOTAL SKS</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $tugas_selesai ?>/<?= $total_tugas ?></div>
                                        <div style="font-size: 0.6rem; letter-spacing: 1.8px; color: var(--text-muted-navy);">TUGAS SELESAI</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <div class="fs-4 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $total_hadir ?></div>
                                        <div style="font-size: 0.6rem; letter-spacing: 1.8px; color: var(--text-muted-navy);">KEHADIRAN</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-start mb-4 px-1">
                                <div class="d-flex justify-content-between small mb-1" style="font-size: 0.66rem; color: var(--text-muted-navy); letter-spacing: 1px;">
                                    <span>CAPAIAN INDEKS PRESTASI</span>
                                    <span class="text-white fw-semibold"><?= $persen_ipk ?>%</span>
                                </div>
                                <div class="navy-progress mb-3">
                                    <div class="navy-progress-bar" style="width: <?= $persen_ipk ?>%;"></div>
                                </div>

                                <div class="d-flex justify-content-between small mb-1" style="font-size: 0.66rem; color: var(--text-muted-navy); letter-spacing: 1px;">
                                    <span>PENYELESAIAN TUGAS</span>
                                    <span class="text-white fw-semibold"><?= $persen_tugas ?>%</span>
                                </div>
                                <div class="navy-progress">
                                    <div class="navy-progress-bar" style="width: <?= $persen_tugas ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid mt-2">
                        <a href="dashboard.php" class="btn-glow-glass w-100">
                            <i class="bi bi-grid-1x2"></i> <span>Ke Workspace Dasbor</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM EDIT BIODATA & UBAH PASSWORD -->
            <div class="col-lg-8 navy-reveal-3">
                <div class="d-flex flex-column gap-4 h-100 justify-content-between">
                    
                    <!-- Kotak 1: Pengaturan Informasi Profil -->
                    <div class="navy-glass-panel p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: rgba(138, 180, 248, 0.16) !important;">
                            <h5 class="text-white fw-bold mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 2px; font-size: 0.96rem;">
                                <i class="bi bi-person-gear me-2" style="color: var(--accent-blue);"></i>
                                INFORMASI BIODATA <?= $isAdmin ? 'ADMINISTRATOR' : 'MAHASISWA' ?>
                            </h5>
                            <span class="badge rounded-pill px-3 py-1" style="background: rgba(138, 180, 248, 0.1); color: var(--accent-blue); font-size: 0.64rem; letter-spacing: 1.5px;">
                                ENTITAS #<?= (int)$user['id'] ?>
                            </span>
                        </div>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-2" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">
                                        <i class="bi bi-lock-fill me-1"></i><?= $isAdmin ? 'NIP / KODE ADMIN (PERMANEN)' : 'NIM MAHASISWA (PERMANEN)' ?>
                                    </label>
                                    <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($user['nim_nip']) ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-2" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">
                                        <i class="bi bi-shield-shaded me-1"></i>PERAN SISTEM (ROLE)
                                    </label>
                                    <input type="text" class="form-control input-readonly text-uppercase" value="<?= htmlspecialchars($user['role']) ?>" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">NAMA LENGKAP</label>
                                    <input type="text" name="nama" class="form-control input-minimal" value="<?= htmlspecialchars($user['nama']) ?>" placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">EMAIL AKADEMIK</label>
                                    <input type="email" name="email" class="form-control input-minimal" value="<?= htmlspecialchars($user['email']) ?>" placeholder="nama@academe.ac.id" required>
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <button type="submit" name="update_profil" class="btn-glow-glass">
                                    <span>Simpan Biodata &rarr;</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Kotak 2: Ubah Kata Sandi -->
                    <div class="navy-glass-panel p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: rgba(138, 180, 248, 0.16) !important;">
                            <h5 class="text-white fw-bold mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 2px; font-size: 0.96rem;">
                                <i class="bi bi-shield-lock me-2" style="color: var(--accent-blue);"></i>KEAMANAN & KATA SANDI
                            </h5>
                            <span class="badge rounded-pill px-3 py-1" style="background: rgba(138, 180, 248, 0.1); color: var(--text-muted-navy); font-size: 0.64rem; letter-spacing: 1.5px;">
                                ENKRIPSI BCRYPT
                            </span>
                        </div>

                        <form method="POST">
                            <div class="mb-4">
                                <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">KATA SANDI SAAT INI</label>
                                <div class="pass-toggle-wrap">
                                    <input type="password" id="passLama" name="password_lama" class="form-control input-minimal pe-5" placeholder="Masukkan kata sandi lama Anda" required>
                                    <button type="button" class="pass-toggle-btn" onclick="togglePass('passLama', this)" aria-label="Tampilkan Sandi">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">KATA SANDI BARU</label>
                                    <div class="pass-toggle-wrap">
                                        <input type="password" id="passBaru" name="password_baru" class="form-control input-minimal pe-5" placeholder="Minimal 6 karakter" minlength="6" required>
                                        <button type="button" class="pass-toggle-btn" onclick="togglePass('passBaru', this)" aria-label="Tampilkan Sandi">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-uppercase small fw-bold mb-1" style="letter-spacing: 2px; font-size: 0.64rem; color: var(--accent-blue);">KONFIRMASI SANDI BARU</label>
                                    <div class="pass-toggle-wrap">
                                        <input type="password" id="passKonfirm" name="konfirmasi_password" class="form-control input-minimal pe-5" placeholder="Ulangi kata sandi baru" minlength="6" required>
                                        <button type="button" class="pass-toggle-btn" onclick="togglePass('passKonfirm', this)" aria-label="Tampilkan Sandi">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <button type="submit" name="update_password" class="btn-glow-glass">
                                    <span>Perbarui Kata Sandi &rarr;</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Socials, Nomor Section & Scroll Indicator -->
    <div class="hero-socials">
        <a href="index.php" title="Beranda"><i class="bi bi-house-door"></i></a>
        <a href="dashboard.php" title="Dasbor"><i class="bi bi-grid-1x2"></i></a>
        <a href="logout.php" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
    <div class="section-indicator">.04 // PROFILE</div>
    <div class="scroll-indicator">
        <span>PROFILE</span>
        <div class="scroll-line"></div>
    </div>
</section>

<script>
    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    document.querySelectorAll('.navy-glass-panel').forEach(panel => {
        panel.addEventListener('mousemove', e => {
            const rect = panel.getBoundingClientRect();
            panel.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
            panel.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
        });
    });
</script>

<?php include 'footer.php'; ?>