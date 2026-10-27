<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="STEMI — ECG Image Examination Management System">
    <link rel="icon" href="{{ asset('assets/images/webicon.png') }}" type="image/png">
    <title>@yield('title', 'Dashboard') | STEMI</title>
    <link rel="stylesheet" href="{{ asset('assets/css/heartscope-dashboard.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-dashboard.css') }}">
    {{-- Original Project 4 theme, layered after page-specific styles. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/pulsewise-dashboard-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pulsewise-sidebar-tree.css') }}">
    {{-- Scoped adaptation for Stemi components and typography, loaded last. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-pulsewise-bridge.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pulsewise-typography.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-typography.css') }}">
    @stack('final-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-glass-cards.css') }}">
</head>
@php
    $user = auth()->user();
    $role = $user->isAdmin() ? 'Administrator' : ($user->isDoctor() ? 'Doctor' : 'Consultant');
    $rolePath = $user->isAdmin() ? 'admin' : ($user->isDoctor() ? 'doctor' : 'consultant');
    $dashUrl = route($rolePath.'.dashboard');
    $patientsUrl = route($rolePath.'.patients');
@endphp
<body>
<div class="stemi-app app-shell">
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    <aside id="appSidebar" class="app-sidebar stemi-sidebar" aria-label="{{ $role }} navigation">
        <a class="stemi-logo" href="{{ $dashUrl }}" aria-label="STEMI dashboard">
            <span class="stemi-logo-icon"><svg viewBox="0 0 64 58" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M32 52 8 29C-6 11 17 -1 32 17 47 -1 70 11 56 29L32 52Z"/><path d="M8 30h13l6-12 8 23 6-16 4 5h11"/></svg></span>
            <span><strong>STEMI<span class="stemi-blue">.</span></strong><small>ECG Image Examination<br>Management System</small></span>
        </a>
        <div class="stemi-role"><span class="stemi-role-dot"></span>{{ strtoupper($role) }} PORTAL</div>
        @include('partials.stemi-sidebar-navigation')
        <div class="stemi-sidebar-footer">
            <div class="stemi-footer-line"></div>
            <small>Better Hearts</small><strong>Healthier Tomorrows</strong>
            <div class="stemi-school"><img src="{{ asset('assets/images/mmu-logo.png') }}" alt="Multimedia University"></div>
        </div>
    </aside>
    <div class="app-main stemi-main">
        <header class="app-topbar stemi-topbar">
            <button type="button" class="stemi-menu-button" id="mobileMenuButton" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <form class="stemi-top-search" method="GET" action="{{ $patientsUrl }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                <input name="q" value="{{ request('q') }}" aria-label="Search patient ID" placeholder="Search Patient ID..."><button type="submit">Search</button>
            </form>
            <div class="stemi-top-spacer"></div>
            <span class="stemi-status" title="Secure clinical workspace"><span class="stemi-role-dot"></span> Secure workspace</span>
            <div class="profile-menu stemi-user-menu">
                <button type="button" id="profileButton" class="stemi-profile-button" aria-expanded="false" aria-controls="profileDropdown">
                    <span class="stemi-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <span class="stemi-profile-copy"><strong>{{ $user->name }}</strong><small>{{ $role }}</small></span>
                    <span aria-hidden="true">⌄</span>
                </button>
                <div id="profileDropdown" class="profile-dropdown stemi-dropdown" hidden>
                    <div class="stemi-dropdown-ident"><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div>
                    @if (!$user->isAdmin()) <a href="{{ route($rolePath.'.profile') }}">My Profile</a> @endif
                    <form action="{{ route('logout') }}" method="POST">@csrf <button type="submit">Sign Out</button></form>
                </div>
            </div>
        </header>
        <main class="page-content stemi-content">
            @if(session('success')) <div class="stemi-alert success" role="status">{{ session('success') }}</div> @endif
            @if(session('error')) <div class="stemi-alert error" role="alert">{{ session('error') }}</div> @endif
            @if($errors->any()) <div class="stemi-alert error" role="alert"><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('assets/js/heartscope-dashboard.js') }}"></script>
<script src="{{ asset('assets/js/stemi-workspace.js') }}" defer></script>
@stack('scripts')
</body>
</html>
