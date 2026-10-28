<section class="sp-login-panel" aria-labelledby="sp-login-title">
    <div class="sp-login-intro">
        <span class="sp-login-eyebrow">STEMI</span>
        <h2 id="sp-login-title">Welcome!</h2>
        <p>Sign in to your account</p>
    </div>

    @if (session('error'))
        <div class="sp-form-alert" role="alert">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="sp-form-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="sp-form-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" class="sp-login-form">
        @csrf

        <label for="email">Email Address</label>
        <div class="sp-input-group">
            <span class="sp-input-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="m4 7 8 6 8-6"/>
                </svg>
            </span>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="username"
                maxlength="255"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                required
            >
        </div>

        <label for="password">Password</label>
        <div class="sp-input-group sp-password-group">
            <span class="sp-input-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
            </span>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
            >
            <button
                type="button"
                class="sp-password-toggle"
                data-password-toggle
                aria-controls="password"
                aria-label="Show password"
                aria-pressed="false"
                title="Show password"
            >
                <svg data-password-eye-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg data-password-eye-closed viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                    <path d="m3 3 18 18"/>
                    <path d="M10.5 5.9A12.4 12.4 0 0 1 12 5.8c6.4 0 10 6.2 10 6.2a16.6 16.6 0 0 1-3.2 3.6"/>
                    <path d="M6.4 6.5C3.6 8.2 2 12 2 12s3.6 6.2 10 6.2a10.8 10.8 0 0 0 4.2-.8"/>
                    <path d="M10 10a3 3 0 0 0 4 4"/>
                </svg>
            </button>
        </div>

        <label class="sp-remember" for="remember">
            <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Remember me</span>
        </label>

        <button class="sp-sign-in" type="submit">Sign In</button>
    </form>
</section>
