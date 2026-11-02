<?php
require_once 'koneksi.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];
$isAdmin = ($role === 'admin');

$STATUS_TUGAS   = ['Belum Selesai', 'Proses', 'Selesai'];
$STATUS_ABSENSI = ['Hadir', 'Izin', 'Sakit', 'Alpha'];
$BOBOT_MAP      = ['A' => 4.00, 'B' => 3.00, 'C' => 2.00, 'D' => 1.00, 'E' => 0.00];

function tgl_valid($s) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$s);
    return $d && $d->format('Y-m-d') === $s;
}

function scope_sql($isAdmin) { return $isAdmin ? '' : ' AND user_id = ?'; }
function scope_args($id, $isAdmin, $user_id) { return $isAdmin ? [$id] : [$id, $user_id]; }

$tab_raw = $_GET['tab'] ?? '1';
$tab     = in_array($tab_raw, ['1', '2', '3'], true) ? (int)$tab_raw : 1;

// Ambil data pengguna yang sedang login
$stmtMe = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmtMe->execute([$user_id]);
$me = $stmtMe->fetch();

if (!$me) {
    redirect('logout.php');
}

// =====================================================
// HANDLER POST: TUGAS, TRANSKRIP, ABSENSI & PROFIL
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect('dashboard.php');
    }

    // 1. SIMPAN / UPDATE TUGAS
    if (isset($_POST['simpan_tugas'])) {
        $id          = (int)($_POST['id_tugas'] ?? 0);
        $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
        $judul       = trim($_POST['judul_tugas'] ?? '');
        $deadline    = $_POST['deadline'] ?? '';
        $status      = $_POST['status'] ?? '';

        if ($mata_kuliah === '' || $judul === '' || mb_strlen($mata_kuliah) > 100 || mb_strlen($judul) > 150
            || !tgl_valid($deadline) || !in_array($status, $STATUS_TUGAS, true)) {
            flash_set('danger', 'Data tugas tidak valid. Periksa kembali isian Anda.');
        } elseif ($id) {
            $stmt = $pdo->prepare("UPDATE tugas SET mata_kuliah=?, judul_tugas=?, deadline=?, status=? WHERE id=?" . scope_sql($isAdmin));
            $stmt->execute(array_merge([$mata_kuliah, $judul, $deadline, $status], scope_args($id, $isAdmin, $user_id)));
            flash_set('success', 'Data tugas berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO tugas (user_id, mata_kuliah, judul_tugas, deadline, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $mata_kuliah, $judul, $deadline, $status]);
            flash_set('success', 'Tugas baru berhasil ditambahkan.');
        }
        redirect('dashboard.php?tab=1');
    }

    // 2. HAPUS TUGAS
    if (isset($_POST['hapus_tugas'])) {
        $id = (int)$_POST['hapus_tugas'];
        $stmt = $pdo->prepare("DELETE FROM tugas WHERE id = ?" . scope_sql($isAdmin));
        $stmt->execute(scope_args($id, $isAdmin, $user_id));
        flash_set('success', 'Tugas telah dihapus.');
        redirect('dashboard.php?tab=1');
    }

    // 3. SIMPAN / UPDATE TRANSKRIP (ADMIN)
    if ($isAdmin && isset($_POST['simpan_transkrip'])) {
        $id          = (int)($_POST['id_transkrip'] ?? 0);
        $target_user = (int)($_POST['target_user'] ?? 0);
        $kode_mk     = strtoupper(trim($_POST['kode_mk'] ?? ''));
        $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
        $sks         = (int)($_POST['sks'] ?? 0);
        $nilai       = $_POST['nilai_huruf'] ?? '';

        $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'mahasiswa'");
        $cek->execute([$target_user]);

        if (!$cek->fetchColumn() || $kode_mk === '' || mb_strlen($kode_mk) > 15 || $mata_kuliah === ''
            || mb_strlen($mata_kuliah) > 100 || $sks < 1 || $sks > 6 || !isset($BOBOT_MAP[$nilai])) {
            flash_set('danger', 'Data nilai tidak valid. Periksa kembali isian Anda.');
        } elseif ($id) {
            $stmt = $pdo->prepare("UPDATE transkrip SET user_id=?, kode_mk=?, mata_kuliah=?, sks=?, nilai_huruf=?, bobot=? WHERE id=?");
            $stmt->execute([$target_user, $kode_mk, $mata_kuliah, $sks, $nilai, $BOBOT_MAP[$nilai], $id]);
            flash_set('success', 'Nilai transkrip berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO transkrip (user_id, kode_mk, mata_kuliah, sks, nilai_huruf, bobot) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target_user, $kode_mk, $mata_kuliah, $sks, $nilai, $BOBOT_MAP[$nilai]]);
            flash_set('success', 'Nilai transkrip baru berhasil disimpan.');
        }
        redirect('dashboard.php?tab=1');
    }

    // 4. HAPUS TRANSKRIP (ADMIN)
    if ($isAdmin && isset($_POST['hapus_transkrip'])) {
        $stmt = $pdo->prepare("DELETE FROM transkrip WHERE id = ?");
        $stmt->execute([(int)$_POST['hapus_transkrip']]);
        flash_set('success', 'Nilai transkrip telah dihapus.');
        redirect('dashboard.php?tab=1');
    }

    // 5. SIMPAN ABSENSI
    if (isset($_POST['simpan_absensi'])) {
        $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
        $tanggal     = $_POST['tanggal'] ?? '';
        $status      = $_POST['status'] ?? '';

        if ($mata_kuliah === '' || mb_strlen($mata_kuliah) > 100 || !tgl_valid($tanggal) || !in_array($status, $STATUS_ABSENSI, true)) {
            flash_set('danger', 'Data presensi tidak valid.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO absensi (user_id, mata_kuliah, tanggal, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $mata_kuliah, $tanggal, $status]);
            flash_set('success', 'Presensi berhasil dicatat.');
        }
        redirect('dashboard.php?tab=2');
    }

    // 6. UPDATE BIODATA PROFIL
    if (isset($_POST['update_profil'])) {
        $nama_baru  = trim($_POST['nama'] ?? '');
        $email_baru = trim($_POST['email'] ?? '');

        if ($nama_baru === '' || $email_baru === '' || mb_strlen($nama_baru) > 100 || mb_strlen($email_baru) > 100) {
            flash_set('danger', 'Nama lengkap dan email akademik wajib diisi dengan benar.');
        } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
            flash_set('danger', 'Format email akademik tidak valid.');
        } else {
            $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $cek->execute([$email_baru, $user_id]);

            if ($cek->fetch()) {
                flash_set('danger', 'Email tersebut sudah digunakan oleh akun lain.');
            } else {
                $upd = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
                $upd->execute([$nama_baru, $email_baru, $user_id]);
                $_SESSION['nama'] = $nama_baru;
                flash_set('success', 'Biodata profil berhasil diperbarui.');
            }
        }
        redirect('dashboard.php?tab=3');
    }

    // 7. UPDATE KATA SANDI
    if (isset($_POST['update_password'])) {
        $pass_lama       = $_POST['password_lama'] ?? '';
        $pass_baru       = $_POST['password_baru'] ?? '';
        $pass_konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if ($pass_lama === '' || $pass_baru === '' || $pass_konfirmasi === '') {
            flash_set('danger', 'Seluruh kolom perubahan kata sandi wajib diisi.');
        } elseif (!password_verify($pass_lama, $me['password'])) {
            flash_set('danger', 'Kata sandi saat ini tidak sesuai.');
        } elseif (strlen($pass_baru) < 6) {
            flash_set('danger', 'Kata sandi baru minimal terdiri dari 6 karakter.');
        } elseif ($pass_baru !== $pass_konfirmasi) {
            flash_set('danger', 'Konfirmasi kata sandi baru tidak cocok.');
        } else {
            $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
            $updPass   = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updPass->execute([$hash_baru, $user_id]);
            flash_set('success', 'Kata sandi keamanan akun berhasil diperbarui.');
        }
        redirect('dashboard.php?tab=3');
    }

    redirect('dashboard.php');
}

// =====================================================
// QUERY DATA & METRIK
// =====================================================
$edit_tugas = null;
if (isset($_GET['edit_tugas'])) {
    $stmt = $pdo->prepare("SELECT * FROM tugas WHERE id = ?" . scope_sql($isAdmin));
    $stmt->execute(scope_args((int)$_GET['edit_tugas'], $isAdmin, $user_id));
    $edit_tugas = $stmt->fetch() ?: null;
}

$edit_transkrip = null;
if ($isAdmin && isset($_GET['edit_transkrip'])) {
    $stmt = $pdo->prepare("SELECT * FROM transkrip WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_transkrip']]);
    $edit_transkrip = $stmt->fetch() ?: null;
}

$where  = $isAdmin ? '' : ' WHERE x.user_id = ?';
$params = $isAdmin ? [] : [$user_id];

$q = function ($sql) use ($pdo, $params) { $s = $pdo->prepare($sql); $s->execute($params); return $s->fetchAll(); };

$daftar_tugas     = $q("SELECT x.*, u.nama FROM tugas x JOIN users u ON x.user_id = u.id $where ORDER BY x.deadline ASC");
$daftar_transkrip = $q("SELECT x.*, u.nama, u.nim_nip FROM transkrip x JOIN users u ON x.user_id = u.id $where ORDER BY u.nama ASC, x.kode_mk ASC");
$daftar_absensi   = $q("SELECT x.*, u.nama FROM absensi x JOIN users u ON x.user_id = u.id $where ORDER BY x.tanggal DESC, x.id DESC");
$daftar_jadwal    = $pdo->query("SELECT * FROM jadwal ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai")->fetchAll();
$daftar_mahasiswa = $isAdmin ? $pdo->query("SELECT id, nim_nip, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama")->fetchAll() : [];
$semua_pengguna   = $isAdmin ? $pdo->query("SELECT id, nim_nip, nama, email, role, created_at FROM users ORDER BY role ASC, nama ASC")->fetchAll() : [];

// Metrik Mahasiswa vs Metrik Sistem Admin
$total_sks = 0; $total_mutu = 0.0;
foreach ($daftar_transkrip as $tr) {
    $total_sks  += (int)$tr['sks'];
    $total_mutu += $tr['sks'] * $tr['bobot'];
}
$ipk_num = $total_sks > 0 ? $total_mutu / $total_sks : 0;
$ipk     = number_format($ipk_num, 2);

$jml_tugas     = count($daftar_tugas);
$tugas_selesai = count(array_filter($daftar_tugas, fn($i) => $i['status'] === 'Selesai'));
$persen_tugas  = $jml_tugas > 0 ? (int)round(($tugas_selesai / $jml_tugas) * 100) : 0;

$jml_absen    = count($daftar_absensi);
$jml_hadir    = count(array_filter($daftar_absensi, fn($a) => $a['status'] === 'Hadir'));
$persen_hadir = $jml_absen > 0 ? (int)round(($jml_hadir / $jml_absen) * 100) : 0;

$total_mhs_aktif = count($daftar_mahasiswa);
$total_jadwal_mk = count($daftar_jadwal);
$total_entri_tr  = count($daftar_transkrip);

$kata_nama  = preg_split('/\s+/', trim($me['nama']));
$inisial    = strtoupper(substr($kata_nama[0] ?? 'A', 0, 1) . (isset($kata_nama[1]) ? substr($kata_nama[1], 0, 1) : ''));
$tgl_gabung = !empty($me['created_at']) ? date('d M Y', strtotime($me['created_at'])) : date('d M Y');

$flash = flash_get();
$badge_absensi = ['Hadir' => 'bg-success', 'Izin' => 'bg-info text-dark', 'Sakit' => 'bg-warning text-dark', 'Alpha' => 'bg-danger'];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace E-Learning — AcadeME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy-main: #000080;
            --navy-deep: #020514;
            --navy-card: rgba(4, 12, 48, 0.65);
            --border-subtle: rgba(80, 130, 255, 0.18);
            --border-hover: rgba(138, 180, 248, 0.55);
            --accent-blue: #8ab4f8;
            --text-muted-navy: #8fa6cb;
            --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: 
                radial-gradient(circle at 50% 0%, rgba(0, 0, 128, 0.48) 0%, transparent 65%),
                radial-gradient(circle at 85% 30%, rgba(15, 45, 160, 0.22) 0%, transparent 45%),
                var(--navy-deep);
            color: #f4f7ff; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        .navy-grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: 
                linear-gradient(to right, rgba(138, 180, 248, 0.025) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(138, 180, 248, 0.025) 1px, transparent 1px);
            background-size: 64px 64px;
        }

        .navbar-custom {
            background: rgba(2, 5, 20, 0.78) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(80, 130, 255, 0.14);
        }

        .brand-stacked {
            display: inline-flex;
            flex-direction: column;
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 0.85rem;
            line-height: 0.92;
            letter-spacing: 3px;
            color: #ffffff !important;
            text-decoration: none;
        }

        .card-studio {
            position: relative;
            z-index: 2;
            background: var(--navy-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0, 0, 40, 0.6);
            overflow: hidden;
            transition: all 0.35s var(--ease-smooth);
        }

        .card-studio::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(420px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(138, 180, 248, 0.09), transparent 45%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .card-studio:hover { 
            transform: translateY(-3px); 
            border-color: var(--border-hover);
            box-shadow: 0 24px 50px rgba(0, 0, 90, 0.55);
        }

        .card-studio:hover::before {
            opacity: 1;
        }

        .workspace-banner {
            background: rgba(4, 12, 48, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 1.5rem 1.8rem;
            box-shadow: 0 20px 45px rgba(0, 0, 40, 0.55);
        }

        .avatar-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(0, 0, 128, 0.45);
            border: 1.5px solid rgba(138, 180, 248, 0.5);
            box-shadow: inset 0 0 18px rgba(138, 180, 248, 0.25), 0 10px 25px rgba(0, 0, 100, 0.6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.35rem;
            font-weight: 900;
            color: #ffffff;
            flex-shrink: 0;
            position: relative;
        }

        .avatar-circle--lg {
            width: 96px;
            height: 96px;
            font-size: 2rem;
        }

        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #10b981;
            border: 2px solid #020514;
        }

        /* =====================================================
           GLOW GLASS BUTTON & TAB PILLS (Bukan Solid AI Button)
           ===================================================== */
        .btn-glow-glass {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 12px 24px;
            border-radius: 14px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.74rem;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #f4f7ff !important;
            text-decoration: none !important;
            background: rgba(0, 0, 128, 0.28);
            border: 1px solid rgba(138, 180, 248, 0.32);
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

        /* Tab Navigasi Glow Glass */
        .nav-pills .nav-link {
            color: var(--text-muted-navy);
            background: rgba(3, 10, 42, 0.45);
            border: 1px solid rgba(80, 130, 255, 0.18);
            border-radius: 14px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.74rem;
            font-weight: 500;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            padding: 12px 22px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.3s var(--ease-smooth);
        }

        .nav-pills .nav-link:hover {
            color: #ffffff;
            background: rgba(0, 0, 128, 0.25);
            border-color: rgba(138, 180, 248, 0.4);
        }

        .nav-pills .nav-link.active {
            color: #ffffff !important;
            background: rgba(0, 0, 128, 0.36) !important;
            border: 1px solid rgba(138, 180, 248, 0.65) !important;
            box-shadow: 
                inset 0 1px 0 0 rgba(255, 255, 255, 0.22),
                inset 0 0 18px rgba(138, 180, 248, 0.2),
                0 12px 28px -8px rgba(0, 0, 128, 0.8);
        }

        .form-control, .form-select {
            background-color: rgba(2, 6, 28, 0.72) !important;
            border: 1px solid rgba(80, 130, 255, 0.22) !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            font-size: 0.85rem;
            padding: 0.6rem 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 0 0.2rem rgba(138, 180, 248, 0.15) !important;
        }

        .input-readonly {
            background: rgba(138, 180, 248, 0.04) !important;
            border: 1px dashed rgba(138, 180, 248, 0.25) !important;
            color: var(--text-muted-navy) !important;
            cursor: not-allowed;
        }

        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-hover-bg: rgba(138, 180, 248, 0.04);
            border-color: rgba(80, 130, 255, 0.12);
        }

        .pass-wrap { position: relative; }
        .pass-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted-navy);
            cursor: pointer;
        }
        .pass-toggle:hover { color: #ffffff; }
    </style>
</head>
<body>

    <div class="navy-grid-overlay"></div>

    <!-- NAVBAR DASBOR -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3 sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <div class="brand-stacked">
                    <span>ACA</span>
                    <span>DEME</span>
                </div>
                <span class="badge rounded-pill px-3 py-1 text-uppercase" style="background: rgba(0, 0, 128, 0.35); border: 1px solid rgba(138, 180, 248, 0.35); color: var(--accent-blue); font-size: 0.62rem; letter-spacing: 1.5px;">
                    <i class="bi <?= $isAdmin ? 'bi-shield-lock-fill' : 'bi-mortarboard-fill' ?> me-1"></i><?= e($role) ?>
                </span>
            </a>

            <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
                <button type="button" onclick="bukaTabProfil()" class="btn-glow-glass py-1 px-3" style="border-radius: 50px; font-size: 0.7rem; letter-spacing: 1px;">
                    <i class="bi bi-person-circle text-info"></i>
                    <span class="d-none d-sm-inline"><?= e($me['nama']) ?></span>
                    <span class="badge bg-transparent border border-info border-opacity-50 text-info rounded-pill"><?= e($me['nim_nip']) ?></span>
                </button>
                <a href="index.php" class="btn-glow-glass py-2 px-3" style="font-size: 0.68rem;">
                    <i class="bi bi-house"></i> <span class="d-none d-md-inline">Beranda</span>
                </a>
                <a href="logout.php" class="btn-glow-glass py-2 px-3" style="font-size: 0.68rem; border-color: rgba(248, 113, 113, 0.4);">
                    <i class="bi bi-power text-danger"></i> <span class="d-none d-md-inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- TOAST NOTIFIKASI -->
    <?php if ($flash): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1080;">
        <div class="toast show align-items-center text-bg-dark border-info shadow-lg rounded-3" role="alert">
            <div class="d-flex">
                <div class="toast-body small">
                    <?php if ($flash['type'] === 'success'): ?>
                        <i class="bi bi-check2-circle text-info me-2 fs-5 align-middle"></i>
                    <?php else: ?>
                        <i class="bi bi-exclamation-triangle text-warning me-2 fs-5 align-middle"></i>
                    <?php endif; ?>
                    <?= e($flash['message']) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container py-4 py-lg-5 position-relative" style="z-index: 2;">

        <!-- HERO BANNER E-LEARNING -->
        <div class="workspace-banner mb-4">
            <div class="row align-items-center g-3">
                <div class="col-md-8 d-flex align-items-center gap-3 gap-md-4">
                    <div class="avatar-circle">
                        <?= e($inisial) ?>
                        <span class="status-dot" title="Online"></span>
                    </div>
                    <div>
                        <div class="text-uppercase small mb-1" style="font-size: 0.65rem; letter-spacing: 2.5px; color: var(--accent-blue);">
                            <?= $isAdmin ? 'OTORITAS PENGELOLA SISTEM AKADEMIK' : 'PORTAL PEMBELAJARAN MANDIRI MAHASISWA' ?>
                        </div>
                        <h3 class="fw-bold text-white mb-1" style="font-family: 'Space Grotesk', sans-serif;">
                            <?= e($me['nama']) ?>
                        </h3>
                        <div class="d-flex flex-wrap align-items-center gap-3 small" style="color: var(--text-muted-navy);">
                            <span><i class="bi bi-person-vcard me-1 text-info"></i><?= $isAdmin ? 'ID Admin' : 'NIM' ?>: <strong class="text-white"><?= e($me['nim_nip']) ?></strong></span>
                            <span><i class="bi bi-envelope me-1 text-info"></i><?= e($me['email']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" onclick="bukaTabProfil()" class="btn-glow-glass">
                        <i class="bi bi-sliders"></i> Pengaturan Akun &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- 4 KARTU METRIK UTAMA (BERBEDA ANTARA ADMIN DAN MAHASISWA) -->
        <?php if ($isAdmin): ?>
        <div class="row g-3 g-lg-4 mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Mahasiswa Terdaftar</span>
                        <i class="bi bi-people text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $total_mhs_aktif ?> <span class="fs-6 text-secondary fw-normal">Akun</span>
                    </div>
                    <div class="small text-secondary mt-2" style="font-size: 0.72rem;">Entitas mahasiswa aktif</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Mata Kuliah Terjadwal</span>
                        <i class="bi bi-calendar3 text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $total_jadwal_mk ?> <span class="fs-6 text-secondary fw-normal">Jadwal</span>
                    </div>
                    <div class="small text-secondary mt-2" style="font-size: 0.72rem;">Semester berjalan</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Total Entri Transkrip</span>
                        <i class="bi bi-award text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $total_entri_tr ?> <span class="fs-6 text-secondary fw-normal">Nilai</span>
                    </div>
                    <div class="small text-secondary mt-2" style="font-size: 0.72rem;">Terekam di basis data</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Monitoring Tugas</span>
                        <i class="bi bi-list-check text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $tugas_selesai ?>/<?= $jml_tugas ?> <span class="fs-6 text-secondary fw-normal">Selesai</span>
                    </div>
                    <div class="small text-secondary mt-2" style="font-size: 0.72rem;">Seluruh tugas mahasiswa</div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3 g-lg-4 mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Indeks Prestasi (IPK)</span>
                        <i class="bi bi-graph-up-arrow text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $ipk ?> <span class="fs-6 text-secondary fw-normal">/ 4.00</span>
                    </div>
                    <div class="progress mt-3 rounded-pill bg-dark" style="height: 5px;">
                        <div class="progress-bar bg-info rounded-pill" style="width: <?= round(($ipk_num / 4) * 100) ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Total SKS Tempuh</span>
                        <i class="bi bi-journal-bookmark text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $total_sks ?> <span class="fs-6 text-secondary fw-normal">SKS</span>
                    </div>
                    <div class="progress mt-3 rounded-pill bg-dark" style="height: 5px;">
                        <div class="progress-bar bg-primary rounded-pill" style="width: <?= min(100, round(($total_sks / 24) * 100)) ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Progres Tugas</span>
                        <i class="bi bi-check2-all text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $persen_tugas ?>% <span class="fs-6 text-secondary fw-normal">(<?= $tugas_selesai ?>/<?= $jml_tugas ?>)</span>
                    </div>
                    <div class="progress mt-3 rounded-pill bg-dark" style="height: 5px;">
                        <div class="progress-bar bg-success rounded-pill" style="width: <?= $persen_tugas ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card-studio p-4 h-100">
                    <div class="d-flex justify-content-between small text-uppercase" style="color: var(--text-muted-navy); font-size: 0.68rem; letter-spacing: 1px;">
                        <span>Kehadiran Kuliah</span>
                        <i class="bi bi-person-check text-info fs-5"></i>
                    </div>
                    <div class="display-6 fw-bold text-white my-2" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= $persen_hadir ?>% <span class="fs-6 text-secondary fw-normal">(<?= $jml_hadir ?> Hadir)</span>
                    </div>
                    <div class="progress mt-3 rounded-pill bg-dark" style="height: 5px;">
                        <div class="progress-bar bg-warning rounded-pill" style="width: <?= $persen_hadir ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- NAVIGASI 3 TAB UTAMA (GLOW GLASS PILLS) -->
        <ul class="nav nav-pills gap-3 mb-4 flex-wrap" id="dashTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 1 ? 'active' : '' ?>" id="btn-tab-1" data-bs-toggle="pill" data-bs-target="#tab-crud-tugas" type="button">
                    <i class="bi bi-layers me-2 text-info"></i>01. Tugas & Transkrip
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 2 ? 'active' : '' ?>" id="btn-tab-2" data-bs-toggle="pill" data-bs-target="#tab-jadwal-absensi" type="button">
                    <i class="bi bi-calendar3 me-2 text-info"></i>02. Jadwal & Presensi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 3 ? 'active' : '' ?>" id="btn-tab-3" data-bs-toggle="pill" data-bs-target="#tab-profil-akun" type="button">
                    <i class="bi bi-person-bounding-box me-2 text-info"></i>03. Profil <?= $isAdmin ? 'Administrator' : 'Mahasiswa' ?>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- =====================================================
                 TAB 1: PELACAKAN TUGAS & TRANSKRIP
                 ===================================================== -->
            <div class="tab-pane fade <?= $tab === 1 ? 'show active' : '' ?>" id="tab-crud-tugas">
                
                <div class="row g-4 mb-5">
                    <div class="col-lg-4">
                        <div class="card-studio p-4">
                            <h6 class="fw-bold text-uppercase mb-3" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                <i class="bi bi-pencil-square me-2 text-info"></i><?= $edit_tugas ? 'Update Tugas' : 'Tambah Tugas Baru' ?>
                            </h6>
                            <form method="POST" action="dashboard.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_tugas" value="<?= e($edit_tugas['id'] ?? '') ?>">
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Mata Kuliah</label>
                                    <input type="text" name="mata_kuliah" maxlength="100" class="form-control" placeholder="Contoh: Pemrograman Web" value="<?= e($edit_tugas['mata_kuliah'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Judul / Deskripsi Tugas</label>
                                    <input type="text" name="judul_tugas" maxlength="150" class="form-control" placeholder="Deskripsi modul tugas" value="<?= e($edit_tugas['judul_tugas'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Tenggat Waktu (Deadline)</label>
                                    <input type="date" name="deadline" class="form-control" value="<?= e($edit_tugas['deadline'] ?? date('Y-m-d')) ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small text-secondary">Status Pengerjaan</label>
                                    <select name="status" class="form-select">
                                        <?php foreach ($STATUS_TUGAS as $st): ?>
                                            <option value="<?= $st ?>" <?= (($edit_tugas['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="simpan_tugas" class="btn-glow-glass w-100">
                                        <span>Simpan Tugas &rarr;</span>
                                    </button>
                                    <?php if ($edit_tugas): ?>
                                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm py-2 px-3 rounded-3 d-flex align-items-center">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card-studio p-4 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h6 class="fw-bold text-uppercase mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">Daftar Pelacakan Tugas</h6>
                                <input type="text" id="cariTugas" class="form-control form-control-sm" style="max-width: 240px;" placeholder="Cari mata kuliah / tugas...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle small mb-0" id="tabelTugas">
                                    <thead>
                                        <tr class="text-secondary">
                                            <?php if ($isAdmin): ?><th>Pembuat</th><?php endif; ?>
                                            <th>Mata Kuliah</th>
                                            <th>Judul Tugas</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_tugas as $t): ?>
                                        <tr>
                                            <?php if ($isAdmin): ?><td class="text-info fw-semibold"><?= e($t['nama']) ?></td><?php endif; ?>
                                            <td class="fw-semibold"><?= e($t['mata_kuliah']) ?></td>
                                            <td><?= e($t['judul_tugas']) ?></td>
                                            <td><?= e($t['deadline']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill px-2 py-1 <?= $t['status'] === 'Selesai' ? 'bg-success' : ($t['status'] === 'Proses' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                    <?= e($t['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="?edit_tugas=<?= (int)$t['id'] ?>" class="btn btn-outline-info btn-sm py-0 px-2 rounded-2"><i class="bi bi-pencil"></i></a>
                                                <form method="POST" action="dashboard.php" class="d-inline" onsubmit="return confirm('Hapus tugas ini?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" name="hapus_tugas" value="<?= (int)$t['id'] ?>" class="btn btn-outline-danger btn-sm py-0 px-2 rounded-2"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$daftar_tugas): ?>
                                            <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada tugas terdaftar.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-BAGIAN B: TRANSKRIP -->
                <div class="row g-4">
                    <?php if ($isAdmin): ?>
                    <div class="col-lg-4">
                        <div class="card-studio p-4">
                            <h6 class="fw-bold text-uppercase mb-3" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                <i class="bi bi-award me-2 text-info"></i><?= $edit_transkrip ? 'Edit Nilai Transkrip' : 'Input Nilai Mahasiswa' ?>
                            </h6>
                            <form method="POST" action="dashboard.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_transkrip" value="<?= e($edit_transkrip['id'] ?? '') ?>">
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Pilih Mahasiswa</label>
                                    <select name="target_user" class="form-select" required>
                                        <?php foreach ($daftar_mahasiswa as $mhs): ?>
                                            <option value="<?= (int)$mhs['id'] ?>" <?= (($edit_transkrip['user_id'] ?? '') == $mhs['id']) ? 'selected' : '' ?>>
                                                <?= e($mhs['nim_nip'] . ' - ' . $mhs['nama']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Kode MK</label>
                                    <input type="text" name="kode_mk" maxlength="15" class="form-control" placeholder="SI209" value="<?= e($edit_transkrip['kode_mk'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary">Nama Mata Kuliah</label>
                                    <input type="text" name="mata_kuliah" maxlength="100" class="form-control" placeholder="Nama mata kuliah" value="<?= e($edit_transkrip['mata_kuliah'] ?? '') ?>" required>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <label class="form-label small text-secondary">Beban SKS</label>
                                        <input type="number" name="sks" min="1" max="6" class="form-control" value="<?= e($edit_transkrip['sks'] ?? '3') ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-secondary">Nilai Huruf</label>
                                        <select name="nilai_huruf" class="form-select">
                                            <?php foreach (['A', 'B', 'C', 'D', 'E'] as $nh): ?>
                                                <option value="<?= $nh ?>" <?= (($edit_transkrip['nilai_huruf'] ?? '') === $nh) ? 'selected' : '' ?>><?= $nh ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="simpan_transkrip" class="btn-glow-glass w-100">
                                        <span>Simpan Nilai &rarr;</span>
                                    </button>
                                    <?php if ($edit_transkrip): ?>
                                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm py-2 px-3 rounded-3 d-flex align-items-center">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="<?= $isAdmin ? 'col-lg-8' : 'col-12' ?>">
                        <div class="card-studio p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-uppercase mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">Tabel Transkrip Akademik</h6>
                                <?php if (!$isAdmin): ?>
                                    <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-3 py-1">Transkrip Resmi Mahasiswa</span>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle small mb-0">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th>Kode MK</th>
                                            <th>Mata Kuliah</th>
                                            <?php if ($isAdmin): ?><th>Mahasiswa</th><?php endif; ?>
                                            <th>SKS</th>
                                            <th>Nilai</th>
                                            <th>Bobot</th>
                                            <?php if ($isAdmin): ?><th class="text-end">Aksi</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_transkrip as $tr): ?>
                                        <tr>
                                            <td class="text-secondary"><?= e($tr['kode_mk']) ?></td>
                                            <td class="fw-semibold"><?= e($tr['mata_kuliah']) ?></td>
                                            <?php if ($isAdmin): ?><td class="text-info"><?= e($tr['nama']) ?> <span class="text-secondary">(<?= e($tr['nim_nip']) ?>)</span></td><?php endif; ?>
                                            <td><?= e($tr['sks']) ?></td>
                                            <td><span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 fw-bold px-2"><?= e($tr['nilai_huruf']) ?></span></td>
                                            <td><?= e($tr['bobot']) ?></td>
                                            <?php if ($isAdmin): ?>
                                            <td class="text-end text-nowrap">
                                                <a href="?edit_transkrip=<?= (int)$tr['id'] ?>" class="btn btn-outline-info btn-sm py-0 px-2 rounded-2"><i class="bi bi-pencil"></i></a>
                                                <form method="POST" action="dashboard.php" class="d-inline" onsubmit="return confirm('Hapus nilai transkrip ini?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" name="hapus_transkrip" value="<?= (int)$tr['id'] ?>" class="btn btn-outline-danger btn-sm py-0 px-2 rounded-2"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$daftar_transkrip): ?>
                                            <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data nilai transkrip.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- =====================================================
                 TAB 2: JADWAL & PRESENSI KULIAH
                 ===================================================== -->
            <div class="tab-pane fade <?= $tab === 2 ? 'show active' : '' ?>" id="tab-jadwal-absensi">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card-studio p-4 h-100">
                            <h6 class="fw-bold text-uppercase mb-3" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                <i class="bi bi-calendar3-week me-2 text-info"></i>Ringkasan Jadwal Perkuliahan
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover small align-middle mb-0">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th>Kode</th>
                                            <th>Mata Kuliah</th>
                                            <th>Hari & Jam</th>
                                            <th>Ruangan</th>
                                            <th>Dosen Pengampu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_jadwal as $j): ?>
                                        <tr>
                                            <td class="text-secondary"><?= e($j['kode_mk']) ?></td>
                                            <td class="fw-semibold"><?= e($j['mata_kuliah']) ?></td>
                                            <td><?= e($j['hari']) ?>, <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?></td>
                                            <td><span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-2"><?= e($j['ruangan']) ?></span></td>
                                            <td><?= e($j['dosen']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card-studio p-4 h-100">
                            <h6 class="fw-bold text-uppercase mb-3" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                <i class="bi bi-qr-code-scan me-2 text-info"></i>Catat & Riwayat Presensi
                            </h6>
                            <form method="POST" action="dashboard.php" class="row g-2 mb-4">
                                <?= csrf_field() ?>
                                <div class="col-12">
                                    <input type="text" name="mata_kuliah" maxlength="100" class="form-control form-control-sm" placeholder="Nama Mata Kuliah" required>
                                </div>
                                <div class="col-6">
                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-6">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($STATUS_ABSENSI as $sa): ?>
                                            <option value="<?= $sa ?>"><?= $sa ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="submit" name="simpan_absensi" class="btn-glow-glass w-100 py-2">
                                        <span>Catat Presensi +</span>
                                    </button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-dark table-sm small align-middle mb-0">
                                    <thead>
                                        <tr class="text-secondary">
                                            <?php if ($isAdmin): ?><th>Mahasiswa</th><?php endif; ?>
                                            <th>Mata Kuliah</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_absensi as $ab): ?>
                                        <tr>
                                            <?php if ($isAdmin): ?><td class="text-info"><?= e($ab['nama']) ?></td><?php endif; ?>
                                            <td><?= e($ab['mata_kuliah']) ?></td>
                                            <td><?= e($ab['tanggal']) ?></td>
                                            <td><span class="badge rounded-pill px-2 <?= $badge_absensi[$ab['status']] ?? 'bg-secondary' ?>"><?= e($ab['status']) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 TAB 3: PROFIL PENGGUNA (DIBEDAKAN ADMIN VS MAHASISWA)
                 ===================================================== -->
            <div class="tab-pane fade <?= $tab === 3 ? 'show active' : '' ?>" id="tab-profil-akun">
                <div class="row g-4 align-items-stretch">
                    
                    <!-- KOLOM KIRI: KARTU IDENTITAS PROFIL -->
                    <div class="col-lg-4">
                        <div class="card-studio p-4 text-center h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="avatar-circle avatar-circle--lg mx-auto mb-3">
                                    <?= e($inisial) ?>
                                    <span class="status-dot"></span>
                                </div>
                                <h4 class="fw-bold text-white mb-1" style="font-family: 'Space Grotesk', sans-serif;">
                                    <?= e($me['nama']) ?>
                                </h4>
                                <p class="small mb-3" style="color: var(--text-muted-navy);">
                                    <?= e($me['email']) ?>
                                </p>

                                <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                                    <span class="badge rounded-pill px-3 py-2 text-uppercase" style="background: rgba(0, 0, 128, 0.45); border: 1px solid rgba(138, 180, 248, 0.4); color: var(--accent-blue); font-size: 0.64rem; letter-spacing: 1.5px;">
                                        <i class="bi <?= $isAdmin ? 'bi-shield-check' : 'bi-patch-check-fill' ?> me-1"></i> <?= e($me['role']) ?>
                                    </span>
                                    <span class="badge rounded-pill px-3 py-2 text-uppercase" style="background: rgba(2, 6, 28, 0.75); border: 1px solid rgba(138, 180, 248, 0.2); color: var(--text-muted-navy); font-size: 0.64rem;">
                                        <i class="bi bi-calendar3 me-1"></i> Sejak <?= e($tgl_gabung) ?>
                                    </span>
                                </div>

                                <!-- Box Nomor Induk -->
                                <div class="p-3 rounded-3 mb-4 text-start" style="background: rgba(2, 6, 28, 0.65); border: 1px solid rgba(138, 180, 248, 0.2);">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-uppercase" style="font-size: 0.64rem; letter-spacing: 1.5px; color: var(--text-muted-navy);">
                                            <?= $isAdmin ? 'KODE OTORITAS / NIP ADMIN' : 'NOMOR INDUK MAHASISWA (NIM)' ?>
                                        </span>
                                        <i class="bi bi-person-vcard text-info"></i>
                                    </div>
                                    <div class="fs-5 fw-bold text-white" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 2px;">
                                        <?= e($me['nim_nip']) ?>
                                    </div>
                                </div>

                                <!-- KHUSUS MAHASISWA: Tampilkan IPK, SKS, Hadir | KHUSUS ADMIN: Tampilkan Otoritas Sistem -->
                                <?php if ($isAdmin): ?>
                                <div class="p-3 rounded-3 text-start mb-3" style="background: rgba(0, 0, 128, 0.22); border: 1px solid rgba(138, 180, 248, 0.22);">
                                    <div class="small text-uppercase fw-bold mb-2" style="font-size: 0.64rem; letter-spacing: 1.5px; color: var(--accent-blue);">
                                        <i class="bi bi-cpu me-1"></i> HAK AKSES ADMINISTRATOR
                                    </div>
                                    <ul class="list-unstyled small mb-0" style="color: var(--text-muted-navy); font-size: 0.78rem; line-height: 1.8;">
                                        <li><i class="bi bi-check2 text-info me-2"></i>Manajemen Penuh Transkrip & Nilai</li>
                                        <li><i class="bi bi-check2 text-info me-2"></i>Monitoring Tugas & Presensi Global</li>
                                        <li><i class="bi bi-check2 text-info me-2"></i>Audit Direktori <?= count($semua_pengguna) ?> Entitas Pengguna</li>
                                    </ul>
                                </div>
                                <?php else: ?>
                                <div class="row g-2 text-center mb-3">
                                    <div class="col-4">
                                        <div class="p-2 rounded-3" style="background: rgba(2, 6, 28, 0.6); border: 1px solid rgba(138, 180, 248, 0.15);">
                                            <div class="fw-bold text-white"><?= $ipk ?></div>
                                            <div style="font-size: 0.6rem; color: var(--text-muted-navy);">IPK</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-3" style="background: rgba(2, 6, 28, 0.6); border: 1px solid rgba(138, 180, 248, 0.15);">
                                            <div class="fw-bold text-white"><?= $total_sks ?></div>
                                            <div style="font-size: 0.6rem; color: var(--text-muted-navy);">SKS</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-3" style="background: rgba(2, 6, 28, 0.6); border: 1px solid rgba(138, 180, 248, 0.15);">
                                            <div class="fw-bold text-white"><?= $jml_hadir ?></div>
                                            <div style="font-size: 0.6rem; color: var(--text-muted-navy);">HADIR</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-3 border-top border-secondary border-opacity-25 small text-secondary">
                                Status Sesi: <strong class="text-info">Terautentikasi</strong>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: FORM EDIT BIODATA & KEAMANAN SANDI -->
                    <div class="col-lg-8">
                        <div class="d-flex flex-column gap-4 h-100">
                            
                            <!-- Form 1: Update Biodata -->
                            <div class="card-studio p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                                    <h6 class="fw-bold text-uppercase mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                        <i class="bi bi-person-gear me-2 text-info"></i>Informasi Biodata <?= $isAdmin ? 'Administrator' : 'Mahasiswa' ?>
                                    </h6>
                                    <span class="small text-secondary">ENTITAS #<?= (int)$me['id'] ?></span>
                                </div>

                                <form method="POST" action="dashboard.php">
                                    <?= csrf_field() ?>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small text-secondary"><?= $isAdmin ? 'NIP / Kode Admin (Permanen)' : 'NIM Mahasiswa (Permanen)' ?></label>
                                            <input type="text" class="form-control input-readonly" value="<?= e($me['nim_nip']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-secondary">Peran Sistem (Role)</label>
                                            <input type="text" class="form-control input-readonly text-uppercase" value="<?= e($me['role']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-secondary">Nama Lengkap</label>
                                            <input type="text" name="nama" maxlength="100" class="form-control" value="<?= e($me['nama']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-secondary">Email Akademik</label>
                                            <input type="email" name="email" maxlength="100" class="form-control" value="<?= e($me['email']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="update_profil" class="btn-glow-glass">
                                            <span>Simpan Biodata &rarr;</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Form 2: Ubah Kata Sandi -->
                            <div class="card-studio p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                                    <h6 class="fw-bold text-uppercase mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                        <i class="bi bi-shield-lock me-2 text-info"></i>Keamanan & Kata Sandi Akun
                                    </h6>
                                    <span class="badge bg-transparent border border-info border-opacity-25 text-info">BCRYPT</span>
                                </div>

                                <form method="POST" action="dashboard.php">
                                    <?= csrf_field() ?>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary">Kata Sandi Saat Ini</label>
                                            <div class="pass-wrap">
                                                <input type="password" id="pLama" name="password_lama" class="form-control pe-5" placeholder="Sandi lama" required>
                                                <button type="button" class="pass-toggle" onclick="togglePass('pLama', this)"><i class="bi bi-eye"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary">Kata Sandi Baru</label>
                                            <div class="pass-wrap">
                                                <input type="password" id="pBaru" name="password_baru" minlength="6" class="form-control pe-5" placeholder="Min. 6 karakter" required>
                                                <button type="button" class="pass-toggle" onclick="togglePass('pBaru', this)"><i class="bi bi-eye"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary">Konfirmasi Sandi Baru</label>
                                            <div class="pass-wrap">
                                                <input type="password" id="pKonf" name="konfirmasi_password" minlength="6" class="form-control pe-5" placeholder="Ulangi sandi" required>
                                                <button type="button" class="pass-toggle" onclick="togglePass('pKonf', this)"><i class="bi bi-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="update_password" class="btn-glow-glass">
                                            <span>Perbarui Kata Sandi &rarr;</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>

                    <!-- KHUSUS ADMIN: DIREKTORI ENTITAS AKUN TERDAFTAR -->
                    <?php if ($isAdmin): ?>
                    <div class="col-12 mt-2">
                        <div class="card-studio p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-uppercase mb-0" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 1.5px;">
                                    <i class="bi bi-people-fill me-2 text-info"></i>Direktori Seluruh Akun Terdaftar
                                </h6>
                                <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-3 py-1">
                                    Total: <?= count($semua_pengguna) ?> Akun
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle small mb-0">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th>ID</th>
                                            <th>NIM / NIP</th>
                                            <th>Nama Lengkap</th>
                                            <th>Email Akademik</th>
                                            <th>Peran (Role)</th>
                                            <th>Terdaftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($semua_pengguna as $u): ?>
                                        <tr>
                                            <td class="text-secondary">#<?= (int)$u['id'] ?></td>
                                            <td class="fw-bold text-white"><?= e($u['nim_nip']) ?></td>
                                            <td><?= e($u['nama']) ?></td>
                                            <td><?= e($u['email']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill px-3 <?= $u['role'] === 'admin' ? 'bg-info text-dark fw-bold' : 'bg-secondary' ?>">
                                                    <?= strtoupper(e($u['role'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-secondary"><?= e($u['created_at'] ?? '-') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function bukaTabProfil() {
            const tabTrigger = document.getElementById('btn-tab-3');
            if (tabTrigger) {
                bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
                tabTrigger.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        const cariTugas = document.getElementById('cariTugas');
        if (cariTugas) {
            cariTugas.addEventListener('input', function () {
                const keyword = this.value.toLowerCase();
                document.querySelectorAll('#tabelTugas tbody tr').forEach(row => {
                    row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
                });
            });
        }

        document.querySelectorAll('.card-studio').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
                card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
            });
        });
    </script>
</body>
</html>