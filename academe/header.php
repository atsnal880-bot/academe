<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadeME — Portal Akademik Terpadu</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600&family=Space+Grotesk:wght@500;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="styles.css?v=<?= time() ?>">

    <style>
        :root {
            --navy-main: #000080;
            --navy-deep: #020514;
            --navy-card: rgba(4, 12, 48, 0.65);
            --border-subtle: rgba(80, 130, 255, 0.18);
            --border-hover: rgba(138, 180, 248, 0.55);
            --text-white: #f4f7ff;
            --text-muted-navy: #8fa6cb;
            --accent-blue: #8ab4f8;
            --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);
            --font-title: 'Montserrat', sans-serif;
        }

        /* =====================================================
           CUSTOM SCROLLBAR HALUS & COMPACT (GLOBAL)
           ===================================================== */
        html {
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(138, 180, 248, 0.35) #020514;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(2, 5, 20, 0.95);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(30, 64, 175, 0.8), rgba(138, 180, 248, 0.55));
            border-radius: 50px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-blue);
        }

        html, body {
            background-color: var(--navy-deep) !important;
            color: var(--text-white) !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
            scroll-behavior: smooth;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* =====================================================
           HAPUS IKON GITHUB, .01/.02/.03, DAN INDIKATOR SCROLL
           ===================================================== */
        .hero-socials,
        .social-rail,
        .section-indicator,
        .section-number,
        .scroll-indicator,
        .scroll-rail {
            display: none !important;
        }

        .navy-ambient-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 50% 0%, rgba(0, 0, 128, 0.55) 0%, transparent 60%),
                radial-gradient(circle at 85% 30%, rgba(15, 45, 160, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 15% 80%, rgba(0, 0, 128, 0.35) 0%, transparent 50%),
                var(--navy-deep);
        }

        .navy-grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: 
                linear-gradient(to right, rgba(138, 180, 248, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(138, 180, 248, 0.03) 1px, transparent 1px);
            background-size: 64px 64px;
            -webkit-mask-image: radial-gradient(circle at 50% 40%, rgba(0,0,0,1) 30%, rgba(0,0,0,0) 85%);
            mask-image: radial-gradient(circle at 50% 40%, rgba(0,0,0,1) 30%, rgba(0,0,0,0) 85%);
        }

        .navbar-custom {
            background: rgba(2, 5, 20, 0.72) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(80, 130, 255, 0.14);
            transition: all 0.4s var(--ease-smooth);
            z-index: 1000;
        }

        .navbar-custom.scrolled {
            padding-top: 0.6rem !important;
            padding-bottom: 0.6rem !important;
            background: rgba(2, 5, 20, 0.92) !important;
            border-bottom-color: rgba(80, 130, 255, 0.28);
            box-shadow: 0 10px 30px rgba(0, 0, 30, 0.6);
        }

        .brand-stacked {
            display: inline-flex;
            flex-direction: column;
            font-family: var(--font-title);
            font-weight: 900;
            font-size: 0.85rem;
            line-height: 0.92;
            letter-spacing: 3px;
            color: #ffffff !important;
            text-decoration: none;
            transition: transform 0.3s var(--ease-smooth);
        }

        .brand-stacked:hover {
            transform: scale(1.05);
        }

        .nav-link-custom {
            font-size: 0.72rem;
            letter-spacing: 2.5px;
            color: var(--text-muted-navy) !important;
            font-weight: 500;
            text-decoration: none;
            position: relative;
            padding: 4px 0 !important;
            transition: color 0.3s ease;
        }

        .nav-link-custom::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 1.5px;
            background: var(--accent-blue);
            transition: width 0.35s var(--ease-smooth);
        }

        .nav-link-custom:hover {
            color: #ffffff !important;
        }

        .nav-link-custom:hover::after {
            width: 100%;
        }

        .specular-button {
            --sb-radius: 16px;
            --sb-tint: #000080;
            --sb-tint-opacity: 0.32;
            --sb-blur: 10px;
            --sb-text-color: #f4f7ff;

            position: relative !important;
            isolation: isolate;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 0.5em;
            margin: 0;
            border: 1px solid rgba(138, 180, 248, 0.32);
            border-radius: var(--sb-radius);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
            line-height: 1;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            text-decoration: none !important;
            color: var(--sb-text-color) !important;
            background: rgba(0, 0, 128, 0.3);
            box-shadow: 
                inset 0 1px 0 0 rgba(255, 255, 255, 0.18),
                inset 0 0 16px rgba(138, 180, 248, 0.12),
                0 10px 24px -8px rgba(0, 0, 128, 0.75);
            -webkit-backdrop-filter: blur(var(--sb-blur));
            backdrop-filter: blur(var(--sb-blur));
            cursor: pointer;
            outline: none;
            white-space: nowrap;
            transition: all 0.3s var(--ease-smooth);
        }

        .specular-button:hover {
            color: #ffffff !important;
            text-decoration: none !important;
            transform: translateY(-2px);
            border-color: rgba(138, 180, 248, 0.75);
            box-shadow: 
                inset 0 1px 0 0 rgba(255, 255, 255, 0.3),
                inset 0 0 22px rgba(138, 180, 248, 0.22),
                0 16px 36px -8px rgba(30, 80, 220, 0.65);
        }

        .specular-button:active {
            transform: scale(0.97);
        }

        .specular-button--sm { padding: 9px 20px; font-size: 0.7rem; }
        .specular-button--md { padding: 12px 24px; font-size: 0.74rem; }
        .specular-button--lg { padding: 14px 32px; font-size: 0.76rem; }

        .specular-button__light {
            position: absolute !important;
            inset: -24px !important;
            top: -24px !important;
            left: -24px !important;
            z-index: 1 !important;
            display: block !important;
            width: calc(100% + 48px) !important;
            height: calc(100% + 48px) !important;
            pointer-events: none !important;
        }

        .specular-button__label {
            position: relative !important;
            z-index: 2 !important;
            display: inline-flex !important;
            align-items: center;
            gap: inherit;
            color: #f4f7ff !important;
            text-decoration: none !important;
        }

        /* Section lebih compact agar nyaman di-scroll ke bawah */
        .section-full {
            min-height: auto;
            position: relative;
            display: flex;
            align-items: center;
            padding: 85px 0;
            z-index: 2;
        }

        #hero.section-full,
        #beranda.section-full {
            min-height: 92vh;
            padding-top: 110px;
        }

        .hero-overline {
            font-size: 0.68rem;
            letter-spacing: 4.5px;
            color: var(--accent-blue);
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 1rem;
            padding: 5px 15px;
            border-radius: 50px;
            background: rgba(0, 0, 128, 0.35);
            border: 1px solid rgba(138, 180, 248, 0.2);
        }

        .hero-title {
            font-family: var(--font-title);
            font-size: clamp(3rem, 7.5vw, 6.2rem);
            font-weight: 900;
            letter-spacing: 0.14em;
            margin-right: -0.14em;
            background: linear-gradient(180deg, #ffffff 25%, #b6d0ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            filter: drop-shadow(0 18px 40px rgba(0, 0, 128, 0.75)) drop-shadow(0 0 24px rgba(138, 180, 248, 0.2));
        }

        .hero-subtitle {
            font-size: 0.78rem;
            letter-spacing: 3.5px;
            color: var(--text-muted-navy);
            text-transform: uppercase;
            margin-bottom: 2.2rem;
        }

        .card-custom {
            background: var(--navy-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 1.8rem 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.4s var(--ease-smooth), border-color 0.35s ease, box-shadow 0.4s var(--ease-smooth);
        }

        .card-custom::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(380px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(138, 180, 248, 0.11), transparent 40%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .card-custom:hover {
            transform: translateY(-6px);
            border-color: var(--border-hover);
            box-shadow: 0 20px 42px -12px rgba(0, 0, 100, 0.65);
        }

        .card-custom:hover::before {
            opacity: 1;
        }

        .card-number {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.68rem;
            letter-spacing: 2px;
            color: var(--accent-blue);
            margin-bottom: 1rem;
            display: block;
            opacity: 0.85;
        }

        .card-icon {
            font-size: 1.75rem;
            color: #ffffff;
            margin-bottom: 1rem;
            display: inline-block;
            transition: transform 0.4s var(--ease-smooth), color 0.3s ease;
        }

        .card-custom:hover .card-icon {
            transform: scale(1.1) translateY(-2px);
            color: var(--accent-blue);
        }

        .contact-box {
            background: rgba(3, 10, 42, 0.78);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 2.2rem;
            box-shadow: 0 22px 50px rgba(0, 0, 50, 0.65);
            transition: border-color 0.35s ease;
        }

        .contact-box:hover {
            border-color: rgba(138, 180, 248, 0.35);
        }

        .input-minimal {
            background: transparent !important;
            border: none;
            border-bottom: 1px solid rgba(138, 180, 248, 0.25);
            border-radius: 0;
            color: #fff !important;
            padding: 12px 0;
            font-size: 0.84rem;
            transition: border-color 0.3s ease;
        }

        .input-minimal:focus {
            box-shadow: none !important;
            border-bottom-color: var(--accent-blue);
        }

        .input-minimal::placeholder {
            color: rgba(143, 166, 203, 0.55);
        }

        @media (max-width: 768px) {
            .hero-title { letter-spacing: 5px; }
            .section-full { padding: 65px 0; }
        }
    </style>
</head>
<body>

<div class="navy-ambient-bg"></div>
<div class="navy-grid-overlay"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top px-4 px-lg-5 py-2">
    <div class="container-fluid">
        <a class="navbar-brand brand-stacked py-1" href="index.php">
            <span>ACA</span>
            <span>DEME</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3 gap-lg-4">
                <li class="nav-item"><a class="nav-link nav-link-custom" href="index.php#hero">BERANDA</a></li>
                <li class="nav-item"><a class="nav-link nav-link-custom" href="index.php#features">FITUR</a></li>
                <li class="nav-item"><a class="nav-link nav-link-custom" href="index.php#contact">CONTACT</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link nav-link-custom text-white fw-semibold" href="dashboard.php">DASBOR</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom text-info fw-semibold" href="profil.php"><i class="bi bi-person-circle me-1"></i>PROFIL</a></li>
                    <li class="nav-item">
                        <a href="logout.php"
                           class="specular-button specular-button--sm"
                           data-specular-button
                           data-size="sm"
                           data-radius="16"
                           data-tint="#000080"
                           data-tint-opacity="0.32"
                           data-line-color="#8ab4f8"
                           data-base-color="#1e3a8a">
                            LOGOUT
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="dashboard.php">DASBOR</a></li>
                    <li class="nav-item">
                        <a href="login.php"
                           class="specular-button specular-button--sm"
                           data-specular-button
                           data-size="sm"
                           data-radius="16"
                           data-tint="#000080"
                           data-tint-opacity="0.32"
                           data-line-color="#8ab4f8"
                           data-base-color="#1e3a8a">
                            LOGIN
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>