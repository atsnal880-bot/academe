<?php
require_once 'koneksi.php';

// ---------- PROSES FORM KONTAK ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    $nama    = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pesan   = trim($_POST['pesan'] ?? '');

    if (!csrf_verify()) {
        flash_set('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif ($nama === '' || $telepon === '' || $email === '' || $pesan === '') {
        flash_set('danger', 'Seluruh kolom wajib diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('danger', 'Format email tidak valid.');
    } elseif (mb_strlen($nama) > 100 || mb_strlen($email) > 100 || mb_strlen($telepon) > 25 || mb_strlen($pesan) > 2000) {
        flash_set('danger', 'Isi formulir terlalu panjang.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO pesan_kontak (nama, telepon, email, pesan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $telepon, $email, $pesan]);
        flash_set('success', 'Pesan terkirim. Terima kasih, tim kami akan segera menghubungi Anda.');
    }
    redirect('index.php#contact');
}
$flash = flash_get();
?>
<?php include 'header.php'; ?>

<!-- WRAPPER SILK: hero + fitur + contact satu latar nyambung -->
<div id="silk-wrap" style="position: relative; overflow: hidden;">
    <canvas id="silk-canvas" style="position:absolute; inset:0; width:100%; height:100%; z-index:0; display:block;"></canvas>
    <!-- Gradasi: terang di hero, makin gelap ke fitur, tetap berbayang di contact, lebur ke navy di bawah -->
    <div style="position:absolute; inset:0; z-index:1; pointer-events:none; background: linear-gradient(to bottom, rgba(2,5,20,0.15) 0%, rgba(2,5,20,0.30) 23%, rgba(2,5,20,0.62) 39%, rgba(2,5,20,0.78) 55%, rgba(2,5,20,0.66) 72%, rgba(2,5,20,0.74) 88%, #020514 100%);"></div>

<!-- SECTION 1: HERO CLEAN NAVY + SILK -->
<section id="hero" class="section-full justify-content-center text-center" style="min-height: 100vh; position: relative; overflow: hidden;">

    <div class="container position-relative" 
         style="z-index: 3;"
         data-animated-content
         data-distance="70"
         data-direction="vertical"
         data-duration="0.85"
         data-ease="power3.out"
         data-blur="0"
         data-stagger="0.1"
         data-threshold="0.1">
         
        <div><span class="hero-overline">PORTAL AKADEMIK TERPADU</span></div>
        <h1 class="hero-title">AcadeME</h1>
        <p class="hero-subtitle">SELAMAT DATANG DI E-LEARNING ACADEME</p>
        <div>
            <a href="#features"
               class="specular-button specular-button--lg"
               data-specular-button
               data-size="lg"
               data-radius="18"
               data-tint="#000080"
               data-tint-opacity="0.35"
               data-text-color="#f4f7ff"
               data-line-color="#8ab4f8"
               data-base-color="#1e3a8a"
               data-intensity="1.25"
               data-idle-intensity="0.45"
               data-shine-size="12"
               data-shine-fade="36"
               data-thickness="1.4"
               data-glow="1.2"
               data-proximity="260">
                JELAJAHI MODUL
            </a>
        </div>
    </div>

    <!-- Ikon Sosial Vertikal di Kiri Bawah -->
    <div class="hero-socials"
         style="z-index: 3;"
         data-animated-content
         data-distance="40"
         data-direction="vertical"
         data-duration="0.8"
         data-ease="power3.out"
         data-stagger="0.08"
         data-threshold="0.1">
        <a href="https://github.com" target="_blank" title="GitHub"><i class="bi bi-github"></i></a>
        <a href="#hero" title="Website"><i class="bi bi-globe2"></i></a>
        <a href="#contact" title="Email"><i class="bi bi-envelope"></i></a>
    </div>

    <!-- Indikator .01 & Scroll -->
    <div class="section-indicator" style="z-index: 3;">.01</div>
    <div class="scroll-indicator" style="z-index: 3;">
        <span>SCROLL</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- SECTION 2: FITUR UTAMA -->
<section id="features" class="section-full" style="position: relative; z-index: 2; background: transparent;">
    <div class="container">
        <div class="text-center mb-5"
             data-animated-content
             data-distance="60"
             data-direction="vertical"
             data-duration="0.8"
             data-ease="power3.out"
             data-stagger="0.08"
             data-threshold="0.1">
            <div><span class="hero-overline">EKOSISTEM PEMBELAJARAN</span></div>
            <h2 class="fw-bold text-white tracking-wider" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 4px;">MODUL AKADEMIK</h2>
        </div>

        <div class="row g-4"
             data-animated-content
             data-distance="70"
             data-direction="vertical"
             data-duration="0.85"
             data-ease="power3.out"
             data-stagger="0.1"
             data-threshold="0.1">
             
            <div class="col-md-6 col-lg-3">
                <div class="card-custom h-100">
                    <span class="card-number">01 / ATTENDANCE</span>
                    <i class="bi bi-calendar2-check card-icon"></i>
                    <h5 class="text-white fw-bold mb-3">Absensi Mandiri</h5>
                    <p class="small mb-4" style="color: #8fa6cb;">Pencatatan kehadiran perkuliahan secara real-time dengan validasi waktu otomatis.</p>
                    <a href="dashboard.php" class="text-white text-decoration-none small fw-semibold" style="letter-spacing: 2px; font-size: 0.7rem;">BUKA MODUL &rarr;</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card-custom h-100">
                    <span class="card-number">02 / SCHEDULE</span>
                    <i class="bi bi-clock-history card-icon"></i>
                    <h5 class="text-white fw-bold mb-3">Ringkasan Jadwal</h5>
                    <p class="small mb-4" style="color: #8fa6cb;">Manajemen agenda perkuliahan harian dan pengingat ruang kelas terstruktur.</p>
                    <a href="dashboard.php" class="text-white text-decoration-none small fw-semibold" style="letter-spacing: 2px; font-size: 0.7rem;">BUKA MODUL &rarr;</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card-custom h-100">
                    <span class="card-number">03 / TASKS</span>
                    <i class="bi bi-list-check card-icon"></i>
                    <h5 class="text-white fw-bold mb-3">Pelacakan Tugas</h5>
                    <p class="small mb-4" style="color: #8fa6cb;">Pantau tenggat waktu tugas mandiri dan progres penyelesaian setiap mata kuliah.</p>
                    <a href="dashboard.php" class="text-white text-decoration-none small fw-semibold" style="letter-spacing: 2px; font-size: 0.7rem;">BUKA MODUL &rarr;</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card-custom h-100">
                    <span class="card-number">04 / TRANSCRIPT</span>
                    <i class="bi bi-bar-chart-line card-icon"></i>
                    <h5 class="text-white fw-bold mb-3">Dasbor Transkrip</h5>
                    <p class="small mb-4" style="color: #8fa6cb;">Evaluasi capaian indeks prestasi dan rekapitulasi nilai akademik tiap semester.</p>
                    <a href="dashboard.php" class="text-white text-decoration-none small fw-semibold" style="letter-spacing: 2px; font-size: 0.7rem;">BUKA MODUL &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <div class="section-indicator">.02</div>
    <div class="scroll-indicator">
        <span>SCROLL</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- SECTION 3: CONTACT (di dalam latar Silk, gaya kartu sama dengan Modul Akademik) -->
<section id="contact" class="section-full" style="position: relative; z-index: 2; background: transparent;">
    <style>
        /* Khusus section Contact — warna mengikuti tema navy */
        #contact .contact-tile { display: flex; align-items: center; gap: 1.1rem; padding: 1.05rem 1.3rem !important; }
        #contact .contact-tile .card-icon { margin: 0 !important; font-size: 1.6rem; flex-shrink: 0; }
        #contact .contact-tile .card-number { margin-bottom: .25rem !important; }

        #contact .glow-edge { position: relative; border-radius: 16px; }
        #contact .contact-card { border-radius: 16px !important; padding: 2.4rem 2.2rem !important; position: relative; z-index: 1; }
        #contact .contact-card:hover { transform: none !important; }

        @property --ang { syntax: '<angle>'; inherits: false; initial-value: 0deg; }
        #contact .glow-edge::before {
            content: ''; position: absolute; inset: 0; border-radius: 16px; padding: 1px; z-index: 2; pointer-events: none;
            background: conic-gradient(from var(--ang), transparent 0 70%, rgba(138,180,248,.85) 86%, transparent 100%);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#000 0 0) content-box exclude, linear-gradient(#000 0 0);
            animation: contactSpin 7s linear infinite;
        }
        @keyframes contactSpin { to { --ang: 360deg; } }

        /* Input: label melayang + garis bawah yang melebar saat fokus */
        #contact .field { position: relative; margin-bottom: 1.6rem; }
        #contact .field--area { margin-bottom: 2.4rem; }
        #contact .field-icon { position: absolute; left: 0; top: 17px; font-size: .95rem; color: #6f86b3; transition: color .3s ease; }
        #contact .field-input {
            display: block; width: 100%; background: transparent; border: 0;
            border-bottom: 1px solid rgba(138,180,248,.22); border-radius: 0; color: #f4f7ff;
            font-family: inherit; font-size: .9rem; padding: 18px 0 10px 28px; outline: none; resize: none;
        }
        #contact .field-label {
            position: absolute; left: 28px; top: 16px; font-size: .85rem; letter-spacing: .5px; color: #8fa6cb;
            pointer-events: none; transform-origin: left top;
            transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1), color .3s ease;
        }
        #contact .field-input:focus ~ .field-label,
        #contact .field-input:not(:placeholder-shown) ~ .field-label { transform: translateY(-20px) scale(.78); color: #8ab4f8; }
        #contact .field::after {
            content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: #8ab4f8;
            transform: scaleX(0); transition: transform .5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #contact .field:focus-within::after { transform: scaleX(1); }
        #contact .field:focus-within .field-icon { color: #8ab4f8; }
        #contact .field-input:-webkit-autofill { -webkit-text-fill-color: #f4f7ff; caret-color: #fff; transition: background-color 9999s ease-in-out 0s; }
        #contact .field-count { position: absolute; right: 0; bottom: -1.5rem; font-size: .62rem; letter-spacing: 1px; color: #4f6492; }
        #contact .field-count.is-near { color: #e0a84a; }

        /* Chip topik */
        #contact .topic-chip {
            font-family: inherit; font-size: .68rem; letter-spacing: 1px; color: #8fa6cb;
            background: rgba(138,180,248,.05); border: 1px solid rgba(138,180,248,.2); border-radius: 999px;
            padding: .4rem .9rem; cursor: pointer;
            transition: color .25s ease, background .25s ease, border-color .25s ease, transform .25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #contact .topic-chip:hover { color: #fff; background: rgba(138,180,248,.16); border-color: rgba(138,180,248,.5); transform: translateY(-2px); }
        #contact .topic-chip:active { transform: scale(.96); }

        #contact .alert-pop { animation: contactPop .5s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes contactPop { from { opacity: 0; transform: translateY(-8px) scale(.98); } to { opacity: 1; transform: none; } }
        #contact .specular-button.is-sending { opacity: .65; cursor: progress; pointer-events: none; }

        @media (prefers-reduced-motion: reduce) { #contact .glow-edge::before { animation: none; } }
    </style>

    <div class="container">
        <div class="row align-items-center g-5"
             data-animated-content
             data-distance="70"
             data-direction="vertical"
             data-duration="0.85"
             data-ease="power3.out"
             data-stagger="0.12"
             data-threshold="0.1">
             
            <div class="col-lg-5">
                <div><span class="hero-overline">PUSAT BANTUAN</span></div>
                <h2 class="fw-bold text-white mb-4" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 4px;">CONTACT</h2>
                <p class="small mb-4" style="line-height: 1.8; color: #8fa6cb;">
                    Apabila Anda mengalami kendala sinkronisasi data akademik atau autentikasi akun, silakan hubungi tim teknis melalui saluran resmi berikut.
                </p>

                <div class="d-flex flex-column gap-3 mb-4"
                     data-animated-content
                     data-distance="40"
                     data-direction="vertical"
                     data-duration="0.8"
                     data-ease="power3.out"
                     data-stagger="0.1"
                     data-threshold="0.1">
                    <div class="card-custom contact-tile">
                        <i class="bi bi-geo-alt card-icon"></i>
                        <div>
                            <span class="card-number">01 / ADDRESS</span>
                            <p class="small mb-0" style="color: #8fa6cb;">Gedung Rektorat Utama, Kampus Terpadu AcadeME</p>
                        </div>
                    </div>
                    <div class="card-custom contact-tile">
                        <i class="bi bi-telephone card-icon"></i>
                        <div>
                            <span class="card-number">02 / PHONE</span>
                            <p class="small mb-0" style="color: #8fa6cb;">+62 21 8000 1925</p>
                        </div>
                    </div>
                    <div class="card-custom contact-tile">
                        <i class="bi bi-envelope card-icon"></i>
                        <div>
                            <span class="card-number">03 / E-MAIL</span>
                            <p class="small mb-0" style="color: #8fa6cb;">academe@academe.id</p>
                        </div>
                    </div>
                </div>

                <span class="card-number d-block mb-2">TOPIK BANTUAN</span>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="topic-chip" data-topic="Sinkronisasi data">Sinkronisasi data</button>
                    <button type="button" class="topic-chip" data-topic="Autentikasi akun">Autentikasi akun</button>
                    <button type="button" class="topic-chip" data-topic="Nilai transkrip">Nilai transkrip</button>
                    <button type="button" class="topic-chip" data-topic="Presensi">Presensi</button>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="glow-edge">
                    <div class="card-custom contact-card">
                        <span class="card-number">FORM / PESAN</span>
                        <h4 class="text-white fw-bold mb-4" style="font-family: 'Space Grotesk', sans-serif; letter-spacing: 3px; font-size: 1.1rem;">CONTACT FORM</h4>

                        <?php if ($flash): ?>
                            <div class="alert alert-<?= e($flash['type']) ?> alert-pop py-2 small">
                                <i class="bi <?= $flash['type'] === 'success' ? 'bi-check2-circle' : 'bi-exclamation-triangle' ?> me-1"></i>
                                <?= e($flash['message']) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php#contact" id="contactForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="kirim_pesan" value="1">

                            <div class="field">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" id="c-nama" name="nama" maxlength="100" class="field-input" placeholder=" " required>
                                <label for="c-nama" class="field-label">Nama Lengkap Mahasiswa</label>
                            </div>
                            <div class="field">
                                <i class="bi bi-telephone field-icon"></i>
                                <input type="tel" id="c-telepon" name="telepon" maxlength="25" class="field-input" placeholder=" " required>
                                <label for="c-telepon" class="field-label">Nomor Telepon</label>
                            </div>
                            <div class="field">
                                <i class="bi bi-envelope field-icon"></i>
                                <input type="email" id="c-email" name="email" maxlength="100" class="field-input" placeholder=" " required>
                                <label for="c-email" class="field-label">Email Akademik (academe@academe.id)</label>
                            </div>
                            <div class="field field--area">
                                <i class="bi bi-chat-left-text field-icon"></i>
                                <textarea id="c-pesan" name="pesan" maxlength="2000" rows="3" class="field-input" placeholder=" " required></textarea>
                                <label for="c-pesan" class="field-label">Tuliskan pesan atau kendala teknis</label>
                                <span class="field-count" id="pesanCount">0 / 2000</span>
                            </div>

                            <button type="submit"
                                    class="specular-button specular-button--lg w-100"
                                    data-specular-button
                                    data-size="lg"
                                    data-radius="18"
                                    data-tint="#000080"
                                    data-tint-opacity="0.35"
                                    data-line-color="#8ab4f8"
                                    data-base-color="#1e3a8a">
                                SEND MESSAGE &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-indicator">.03</div>
</section>

</div><!-- /silk-wrap -->

<!-- Interaksi form Contact: hitung huruf, chip topik, cegah kirim dobel -->
<script>
(function () {
    var ta = document.getElementById('c-pesan');
    var cnt = document.getElementById('pesanCount');
    if (ta && cnt) {
        var up = function () {
            cnt.textContent = ta.value.length + ' / 2000';
            cnt.classList.toggle('is-near', ta.value.length > 1800);
        };
        ta.addEventListener('input', up);
        up();
    }
    document.querySelectorAll('[data-topic]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!ta) return;
            ta.value = '[' + b.dataset.topic + '] ' + ta.value.replace(/^\[[^\]]*\]\s*/, '');
            ta.dispatchEvent(new Event('input'));
            ta.focus();
            ta.setSelectionRange(ta.value.length, ta.value.length);
        });
    });
    var f = document.getElementById('contactForm');
    if (f) {
        f.addEventListener('submit', function () {
            var b = f.querySelector('button[type=submit]');
            setTimeout(function () {
                b.classList.add('is-sending');
                b.disabled = true;
                var l = b.querySelector('.specular-button__label');
                (l || b).textContent = 'MENGIRIM...';
            }, 0);
        });
    }
})();
</script>

<!-- SILK BACKGROUND (vanilla WebGL, tanpa React/three.js) -->
<script>
(function () {
    // ===== PROPS (sama seperti komponen Silk) =====
    var P = {
        color: '#000080',
        sheenColor: '#ffffff',
        speed: 5,
        scale: 1,
        rotation: 0,
        sheen: 0.5,
        depth: 0.5,
        glow: 0.3,
        noiseIntensity: 1.5,
        mouseInteraction: true,
        clickRipple: true,
        intro: true
    };

    var canvas = document.getElementById('silk-canvas');
    if (!canvas) return;
    var hero = document.getElementById('silk-wrap');
    var contactEl = document.getElementById('contact');
    var gl = canvas.getContext('webgl', { antialias: false, alpha: false });
    if (!gl) return;

    function hex(h) {
        h = h.replace('#', '');
        return [parseInt(h.substr(0, 2), 16) / 255, parseInt(h.substr(2, 2), 16) / 255, parseInt(h.substr(4, 2), 16) / 255];
    }

    var vs = 'attribute vec2 p; varying vec2 vUv; void main(){ vUv = p * 0.5 + 0.5; gl_Position = vec4(p, 0.0, 1.0); }';

    // uK = faktor tinggi (wrapper sekarang ikut memuat Contact). Dipakai agar
    // ukuran motif sutra di hero & fitur tetap sama persis seperti sebelumnya.
    var fs = [
        'precision highp float;',
        'varying vec2 vUv;',
        'uniform float uTime, uSpeed, uScale, uRotation, uNoise, uSheen, uDepth, uGlow, uAspect, uK, uIntro, uRippleT;',
        'uniform vec3 uColor, uSheenColor;',
        'uniform vec2 uMouse, uRippleP;',
        'const float E = 2.71828182845904523536;',
        'float noise(vec2 t){ vec2 r = E * sin(E * t); return fract(r.x * r.y * (1.0 + t.x)); }',
        'vec2 rotateUvs(vec2 uv, float a){ float c = cos(a), s = sin(a); return mat2(c, -s, s, c) * uv; }',
        'void main(){',
        '  float rnd = noise(gl_FragCoord.xy);',
        '  vec2 base = vUv;',
        '  vec2 uv = base * vec2(uAspect, uK);',
        '  uv += (uMouse - 0.5) * vec2(uAspect, uK) * 0.12;',
        '  float ring = 0.0;',
        '  if (uRippleT >= 0.0) {',
        '    float d = distance(base * vec2(uAspect, uK), uRippleP * vec2(uAspect, uK));',
        '    float w = d - uRippleT * 0.7;',
        '    ring = exp(-w * w * 90.0) * exp(-uRippleT * 1.6);',
        '    uv += normalize(base * vec2(uAspect, uK) - uRippleP * vec2(uAspect, uK) + 0.0001) * ring * 0.05;',
        '  }',
        '  uv = rotateUvs(uv * uScale, uRotation);',
        '  vec2 tex = uv * uScale;',
        '  float tOff = uSpeed * uTime;',
        '  tex.y += 0.03 * sin(8.0 * tex.x - tOff);',
        '  float pattern = 0.6 + 0.4 * sin(5.0 * (tex.x + tex.y + cos(3.0 * tex.x + 5.0 * tex.y) + 0.02 * tOff) + sin(20.0 * (tex.x + tex.y - 0.1 * tOff)));',
        '  vec3 col = uColor * pattern;',
        '  col += uSheenColor * uSheen * smoothstep(0.78, 1.0, pattern) * 0.55;',
        '  col += uSheenColor * ring * 0.35;',
        '  float md = distance(base * vec2(uAspect, uK), uMouse * vec2(uAspect, uK));',
        '  col += uColor * uGlow * exp(-md * md * 6.0) * 0.8;',
        '  col *= 1.0 - uDepth * 0.7 * smoothstep(0.25, 0.95, distance(base, vec2(0.5)));',
        '  col -= rnd / 15.0 * uNoise;',
        '  col *= uIntro;',
        '  gl_FragColor = vec4(max(col, 0.0), 1.0);',
        '}'
    ].join('\n');

    function sh(type, src) {
        var s = gl.createShader(type);
        gl.shaderSource(s, src);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { console.error(gl.getShaderInfoLog(s)); return null; }
        return s;
    }
    var v = sh(gl.VERTEX_SHADER, vs), f = sh(gl.FRAGMENT_SHADER, fs);
    if (!v || !f) return;
    var prog = gl.createProgram();
    gl.attachShader(prog, v); gl.attachShader(prog, f);
    gl.linkProgram(prog);
    gl.useProgram(prog);

    var buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

    var U = {};
    ['uTime','uSpeed','uScale','uRotation','uNoise','uSheen','uDepth','uGlow','uAspect','uK','uIntro','uRippleT','uColor','uSheenColor','uMouse','uRippleP']
        .forEach(function (n) { U[n] = gl.getUniformLocation(prog, n); });

    var col = hex(P.color), sCol = hex(P.sheenColor);
    gl.uniform3fv(U.uColor, col);
    gl.uniform3fv(U.uSheenColor, sCol);
    gl.uniform1f(U.uSpeed, P.speed);
    gl.uniform1f(U.uScale, P.scale);
    gl.uniform1f(U.uRotation, P.rotation * Math.PI / 180);
    gl.uniform1f(U.uNoise, P.noiseIntensity);
    gl.uniform1f(U.uSheen, P.sheen);
    gl.uniform1f(U.uDepth, P.depth);
    gl.uniform1f(U.uGlow, P.glow);

    function resize() {
        var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        var w = Math.max(1, Math.floor(canvas.clientWidth * dpr));
        var h = Math.max(1, Math.floor(canvas.clientHeight * dpr));
        if (canvas.width !== w || canvas.height !== h) {
            canvas.width = w; canvas.height = h;
            gl.viewport(0, 0, w, h);
        }
        // Tinggi tanpa Contact = tinggi lama; K menjaga skala motif tetap sama
        var H = hero.clientHeight || 1;
        var Hp = Math.max(1, H - (contactEl ? contactEl.offsetHeight : 0));
        var K = H / Hp;
        gl.uniform1f(U.uAspect, (w / h) * K);
        gl.uniform1f(U.uK, K);
    }
    window.addEventListener('resize', resize);
    window.addEventListener('load', resize);
    resize();

    // Mouse + click ripple
    var mouse = [0.5, 0.5], target = [0.5, 0.5];
    var rippleStart = -1, rippleP = [0.5, 0.5];
    function toUv(e) {
        var r = canvas.getBoundingClientRect();
        return [(e.clientX - r.left) / r.width, 1 - (e.clientY - r.top) / r.height];
    }
    if (P.mouseInteraction) {
        hero.addEventListener('mousemove', function (e) { target = toUv(e); });
        hero.addEventListener('mouseleave', function () { target = [0.5, 0.5]; });
    }
    if (P.clickRipple) {
        hero.addEventListener('click', function (e) {
            rippleP = toUv(e);
            rippleStart = performance.now();
        });
    }

    // Jalan hanya saat wrapper terlihat
    var visible = true;
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }).observe(hero);
    }

    var time = 0, last = performance.now(), t0 = last;
    function frame(now) {
        requestAnimationFrame(frame);
        var dt = Math.min((now - last) / 1000, 0.1);
        last = now;
        if (!visible) return;
        time += 0.1 * dt;
        mouse[0] += (target[0] - mouse[0]) * 0.06;
        mouse[1] += (target[1] - mouse[1]) * 0.06;

        var intro = 1;
        if (P.intro) {
            var k = Math.min((now - t0) / 2200, 1);
            intro = 1 - Math.pow(1 - k, 3);
        }
        var rt = rippleStart < 0 ? -1 : (now - rippleStart) / 1000;
        if (rt > 3) { rippleStart = -1; rt = -1; }

        gl.uniform1f(U.uTime, time);
        gl.uniform1f(U.uIntro, intro);
        gl.uniform1f(U.uRippleT, rt);
        gl.uniform2f(U.uMouse, mouse[0], mouse[1]);
        gl.uniform2f(U.uRippleP, rippleP[0], rippleP[1]);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    }
    requestAnimationFrame(frame);
})();
</script>

<?php include 'footer.php'; ?>