(function () {
    'use strict';

    const form = document.querySelector('.login__card');
    const errorEl = form.querySelector('[data-role="error"]');
    const submit = form.querySelector('.login__submit');
    const csrf = document.querySelector('meta[name="csrf"]').content;

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorEl.hidden = true;
        submit.disabled = true;

        const email = form.email.value.trim();
        const password = form.password.value;

        try {
            const res = await fetch('/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
                body: JSON.stringify({email, password}),
            });

            if (res.ok) {
                window.location.assign('/');
                return;
            }

            showError(res.status === 401 ? 'Invalid email or password.' : 'Something went wrong. Try again.');
        } catch (_) {
            showError('Network error. Try again.');
        } finally {
            submit.disabled = false;
        }
    });
})();
