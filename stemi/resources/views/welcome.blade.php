<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="STEMI — secure ECG image examinations, patient records and structured clinical interpretation.">
    <title>STEMI | ECG Image Examination Management System</title>
    <link rel="icon" href="{{ asset('assets/images/webicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public-pulsewise.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-public-home.css') }}">
    {{-- Homepage-only styling. The existing /login page does not load this stylesheet. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-home-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-glass-cards.css') }}">
</head>
<body class="stemi-public-page stemi-homepage">
    <header class="sp-nav">
        <div class="sp-nav-inner">
            <a href="{{ route('home') }}" class="sp-mmu-brand" aria-label="Multimedia University — Stemi homepage">
                <img src="{{ asset('assets/images/mmu-logo.png') }}" alt="Multimedia University">
            </a>

            <nav class="sp-header-links" aria-label="Main navigation">
                <a href="#home" aria-current="page">Home</a>
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#contact">Contact</a>
            </nav>

            <a class="sp-header-login" href="{{ route('login') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="7.5" r="3.5"/>
                    <path d="M5 21v-2.5a7 7 0 0 1 14 0V21"/>
                </svg>
                <span>Login</span>
            </a>
        </div>
    </header>

    <main id="home" class="sp-main sp-home-main">
        <div class="sp-home-shell">
            <section class="sp-home-hero" aria-labelledby="sp-home-title">
                <div class="sp-home-intro">
                    <p class="sp-home-overline">ECG IMAGE EXAMINATION MANAGEMENT SYSTEM</p>
                    <div class="sp-home-brand">
                        <svg class="sp-home-brand-mark" viewBox="0 0 80 74" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M40 64 9 33a17 17 0 0 1 25-24l6 7 6-7a17 17 0 0 1 25 24L40 64Z"/>
                            <path d="M13 36h15l7-14 9 29 6-15h17"/>
                        </svg>
                        <h1 id="sp-home-title">STE<span>MI</span></h1>
                    </div>
                    <p class="sp-home-description">
                        Smarter ECG image examinations for connected, efficient and patient-centered clinical care.
                    </p>
                    <div class="sp-home-hero-actions">
                        <a class="sp-home-button sp-home-button-primary" href="{{ route('login') }}">
                            <span>Login to STEMI</span>
                            <span aria-hidden="true">@include('partials.eye-icon')</span>
                        </a>
                        <a class="sp-home-button sp-home-button-outline" href="#about">Learn More</a>
                    </div>
                </div>

                <div class="sp-home-hero-art" aria-hidden="true">
                    <svg class="sp-home-wave-svg" viewBox="0 0 980 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <filter id="sp-home-blue-glow" x="-30%" y="-80%" width="160%" height="260%">
                                <feGaussianBlur stdDeviation="6" result="blur"/>
                                <feMerge>
                                    <feMergeNode in="blur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                            <filter id="sp-home-heart-glow" x="-65%" y="-65%" width="230%" height="230%">
                                <feGaussianBlur stdDeviation="16" result="blur"/>
                                <feMerge>
                                    <feMergeNode in="blur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>

                        <path class="sp-home-wave-path" d="M2 158H148L164 158L178 145L195 193L211 107L228 181L246 150L260 158H399M691 158H752L766 139L784 193L802 99L820 198L838 135L854 158L873 146L894 158H978" stroke="#078cf0" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round" filter="url(#sp-home-blue-glow)"/>
                        <path class="sp-home-heart-shape" d="M551 245 452 145C407 99 441 36 494 58c24 10 44 31 57 52 14-21 33-42 57-52 53-22 87 41 42 87L551 245Z" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" filter="url(#sp-home-heart-glow)"/>
                        <path class="sp-home-heart-pulse" d="M461 160h50l17-31 23 73 22-58 17 16h55" stroke="white" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" filter="url(#sp-home-heart-glow)"/>
                        <circle cx="164" cy="158" r="5" fill="white"/>
                        <circle cx="195" cy="193" r="5" fill="white"/>
                        <circle cx="211" cy="107" r="5" fill="white"/>
                        <circle cx="260" cy="158" r="4" fill="white"/>
                        <circle cx="766" cy="139" r="4" fill="white"/>
                        <circle cx="802" cy="99" r="5" fill="white"/>
                        <circle cx="820" cy="198" r="5" fill="white"/>
                        <circle cx="894" cy="158" r="4" fill="white"/>
                    </svg>
                </div>

                <div class="sp-home-motto">
                    Better Hearts
                    <strong>Healthier Tomorrows</strong>
                    <span aria-hidden="true"></span>
                </div>
            </section>

            <section id="features" class="sp-home-feature-bar" aria-label="STEMI system features">
                <article class="sp-home-feature-item">
                    <span class="sp-home-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="7" r="3"/><path d="M3 20v-3a6 6 0 0 1 12 0v3M18 12a3 3 0 1 0-2.4-5M17 15a5 5 0 0 1 4 5"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Patient Records</h2>
                        <p>Centrally managed</p>
                    </div>
                </article>

                <article class="sp-home-feature-item">
                    <span class="sp-home-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m6 16 4-4 3 3 3-5 3 3"/>
                        </svg>
                    </span>
                    <div>
                        <h2>ECG Image Records</h2>
                        <p>JPG and PNG examinations</p>
                    </div>
                </article>

                <article class="sp-home-feature-item">
                    <span class="sp-home-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 2h9l5 5v15H5z"/><path d="M14 2v6h5M8 12h8M8 16h8"/>
                        </svg>
                    </span>
                    <div>
                        <h2>ECG Interpretation</h2>
                        <p>Structured clinical findings</p>
                    </div>
                </article>

                <article class="sp-home-feature-item">
                    <span class="sp-home-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 2 8 4v6c0 5-4 8-8 10-4-2-8-5-8-10V6l8-4Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Role-Based Access</h2>
                        <p>Secure clinical workspace</p>
                    </div>
                </article>
            </section>

            <section id="about" class="sp-home-about" aria-labelledby="sp-home-about-title">
                <div class="sp-home-about-copy">
                    <p class="sp-home-section-eyebrow">WHY CHOOSE STEMI</p>
                    <h2 id="sp-home-about-title">Supporting Healthcare Professionals</h2>
                    <p>
                        STEMI simplifies ECG image examination and clinical documentation, helping
                        healthcare professionals manage patient records, interpret ECG images and
                        review clinical findings in one organized system.
                    </p>
                    <a href="#features" class="sp-home-button sp-home-button-primary sp-home-explore">
                        <span>Explore Features</span>
                        <span aria-hidden="true">@include('partials.eye-icon')</span>
                    </a>
                </div>

                <ul class="sp-home-benefits" aria-label="STEMI benefits">
                    <li>User-friendly and responsive interface</li>
                    <li>Structured ECG examination workflow</li>
                    <li>Secure and centralized patient records</li>
                    <li>Designed for healthcare professionals</li>
                    <li>Protected ECG image viewing</li>
                </ul>

                <aside class="sp-home-join" aria-labelledby="sp-home-join-title">
                    <p class="sp-home-section-eyebrow">GET STARTED</p>
                    <h2 id="sp-home-join-title">Join <span>STEMI</span></h2>
                    <p>Access a connected clinical workspace for ECG image examination and patient care.</p>
                    <a href="{{ route('login') }}" class="sp-home-join-button">
                        <span>Login to STEMI</span>
                        <span aria-hidden="true">@include('partials.eye-icon')</span>
                    </a>
                </aside>
            </section>
        </div>
    </main>

    <footer id="contact" class="sp-footer">
        <div class="sp-footer-inner">
            <div class="sp-footer-brand">
                <svg viewBox="0 0 80 74" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M40 64 9 33a17 17 0 0 1 25-24l6 7 6-7a17 17 0 0 1 25 24L40 64Z"/>
                    <path d="M13 36h15l7-14 9 29 6-15h17"/>
                </svg>
                <div>
                    <strong>STE<span>MI</span></strong>
                    <small>ECG Image Examination Management System</small>
                </div>
            </div>
            <nav class="sp-footer-links" aria-label="Footer navigation">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="sp-footer-right">
                <img src="{{ asset('assets/images/mmu-logo.png') }}" alt="Multimedia University">
                <span>© {{ date('Y') }} STEMI. All rights reserved.</span>
            </div>
        </div>
    </footer>
</body>
</html>

