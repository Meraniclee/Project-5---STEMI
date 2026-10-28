'use strict';

/**
 * Public sign-in: toggle visibility without changing the form's login flow.
 * Works for both the homepage login panel and the dedicated login page.
 */
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const password = document.getElementById(button.getAttribute('aria-controls'));

    if (!password) {
        return;
    }

    const eyeOpen = button.querySelector('[data-password-eye-open]');
    const eyeClosed = button.querySelector('[data-password-eye-closed]');

    button.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.title = visible ? 'Hide password' : 'Show password';

        if (eyeOpen) {
            eyeOpen.hidden = visible;
        }

        if (eyeClosed) {
            eyeClosed.hidden = !visible;
        }
    });
});
