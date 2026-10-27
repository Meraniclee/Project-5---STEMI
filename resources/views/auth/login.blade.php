<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to the STEMI clinical portal.">
    <title>Sign In | STEMI</title>
    <link rel="icon" href="{{ asset('assets/images/webicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public-pulsewise.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public-home.css') }}">
    <script defer src="{{ asset('assets/js/stemi-public-login.js') }}"></script>
</head>
<body class="stemi-public-page">
    <header class="sp-nav">
        <div class="sp-nav-inner">
            <a href="{{ route('home') }}" class="sp-mmu-brand" aria-label="Multimedia University — Stemi homepage">
                <img src="{{ asset('assets/images/mmu-logo.png') }}" alt="Multimedia University">
            </a>

            <nav class="sp-header-links" aria-label="Main navigation">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('home') }}#about">About</a>
                <a href="{{ route('home') }}#features">Features</a>
                <a href="{{ route('home') }}#contact">Contact</a>
            </nav>

            <a class="sp-header-login" href="{{ route('home') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m14 18-6-6 6-6"/>
                    <path d="M8 12h13"/>
                </svg>
                <span>Home</span>
            </a>
        </div>
    </header>

    <main class="sp-main sp-main-login">
        <div class="sp-main-inner">
            <section class="sp-hero-copy" aria-labelledby="sp-hero-heading">
                <div class="sp-site-brand">
                    <svg class="sp-brand-mark" viewBox="0 0 80 74" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M40 64 9 33a17 17 0 0 1 25-24l6 7 6-7a17 17 0 0 1 25 24L40 64Z"/>
                        <path d="M13 36h15l7-14 9 29 6-15h17"/>
                    </svg>
                    <div class="sp-site-brand-copy">
                        <div class="sp-site-name">STE<span>MI</span></div>
                        <div class="sp-site-caption">ECG Image Examination Management System</div>
                    </div>
                </div>

                <p class="sp-kicker">SECURE MEDICAL PORTAL</p>
                <h1 id="sp-hero-heading">Welcome to <span>STEMI.</span></h1>
                <p class="sp-lead">Review ECG image examinations, manage patient records and collaborate in a trusted clinical workspace.</p>
            </section>

            <div class="sp-heart-art" aria-hidden="true">
                <div class="sp-heart-ring sp-heart-ring-outer"></div>
                <div class="sp-heart-ring sp-heart-ring-middle"></div>
                <div class="sp-heart-ring sp-heart-ring-inner"></div>
                <svg class="sp-heart-svg" viewBox="0 0 280 275" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round" stroke-linecap="round">
                    <path d="M140 235 46 141C-8 87 45 15 107 46c14 7 24 18 33 32 9-14 19-25 33-32 62-31 115 41 61 95l-94 94Z"/>
                    <path d="M48 147h48l24-37 25 77 25-55 15 15h45" stroke-width="6"/>
                </svg>
                <svg class="sp-pulse-line" viewBox="0 0 520 94" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M0 49h72l27-1 24-14 20 33 25-17h26l21-44 21 83 26-61 25 22h63l24-17 22 33 22-17h112"/>
                </svg>
            </div>

            @include('auth.public-login-panel')

            <section class="sp-features" aria-label="STEMI features">
                <article class="sp-feature">
                    <span class="sp-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h5l3-6 4 12 3-6h5"/></svg>
                    </span>
                    <div><h2>ECG Image Management</h2><p>Secure patient-linked JPG and PNG records with high-resolution viewing.</p></div>
                </article>
                <article class="sp-feature">
                    <span class="sp-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20v-2a6 6 0 0 1 12 0v2M18 12a3 3 0 1 0-2.3-5M17 14a5 5 0 0 1 4 5v1"/><circle cx="9" cy="8" r="3"/></svg>
                    </span>
                    <div><h2>Clinical Interpretation</h2><p>Structured, clinician-entered ECG category, rhythm and assessment fields.</p></div>
                </article>
                <article class="sp-feature">
                    <span class="sp-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 8 4v6c0 5-4 8-8 10-4-2-8-5-8-10V6l8-4Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
                    </span>
                    <div><h2>Unified Reporting</h2><p>Role-based clinical dashboards and examination findings history.</p></div>
                </article>
            </section>
        </div>
    </main>

    <footer class="sp-footer">
        <div class="sp-footer-inner">
            <div class="sp-footer-brand">
                <svg viewBox="0 0 80 74" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M40 64 9 33a17 17 0 0 1 25-24l6 7 6-7a17 17 0 0 1 25 24L40 64Z"/>
                    <path d="M13 36h15l7-14 9 29 6-15h17"/>
                </svg>
                <div><strong>STE<span>MI</span></strong><small>ECG Image Examination Management System</small></div>
            </div>
            <nav class="sp-footer-links" aria-label="Footer navigation">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('home') }}#about">About</a>
                <a href="{{ route('home') }}#features">Features</a>
                <a href="{{ route('home') }}#contact">Contact</a>
            </nav>
            <div class="sp-footer-right">
                <img src="{{ asset('assets/images/mmu-logo.png') }}" alt="Multimedia University">
                <span>© {{ date('Y') }} STEMI. All rights reserved.</span>
            </div>
        </div>
    </footer>
</body>
</html>
