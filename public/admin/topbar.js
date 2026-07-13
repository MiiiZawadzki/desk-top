(function () {
    'use strict';

    function toggleEdit() {
        const on = document.body.classList.toggle('edit-mode');
        const btn = document.querySelector('[data-action="toggle-edit"]');
        if (btn) btn.textContent = on ? 'Done' : 'Edit';
        ensureAddButton(on);
    }

    function ensureAddButton(on) {
        let btn = document.querySelector('.topbar__add');
        if (on && !btn) {
            btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'topbar__add';
            btn.dataset.action = 'add';
            btn.textContent = '+ Widget';
            const editBtn = document.querySelector('[data-action="toggle-edit"]');
            editBtn.parentNode.insertBefore(btn, editBtn);
        }
    }

    const isLight = () => document.documentElement.getAttribute('data-theme') === 'light';

    function toggleTheme() {
        const next = isLight() ? 'dark' : 'light';
        if (next === 'light') document.documentElement.setAttribute('data-theme', 'light');
        else document.documentElement.removeAttribute('data-theme'); // dark = default
        try {
            localStorage.setItem('theme', next);
        } catch (e) {
        }
        updateThemeIcon();
    }

    function updateThemeIcon() {
        const btn = document.querySelector('[data-action="toggle-theme"]');
        if (!btn) return;

        btn.textContent = isLight() ? '☾' : '☀';
        btn.title = isLight() ? 'Dark mode' : 'Light mode';
    }

    function startClock() {
        const el = document.querySelector('[data-role="clock"]');
        if (!el) return;
        const dateFmt = new Intl.DateTimeFormat('en-GB', {weekday: 'long', day: 'numeric', month: 'long'});
        const timeFmt = new Intl.DateTimeFormat('en-GB', {hour: '2-digit', minute: '2-digit'});
        const tick = () => {
            const d = new Date();
            el.textContent = dateFmt.format(d) + ' · ' + timeFmt.format(d);
        };
        tick();
        setInterval(tick, 15000);
    }

    function refreshView(btn) {
        if (window.Dashboard && typeof window.Dashboard.refreshAll === 'function') {
            window.Dashboard.refreshAll();
        }
        if (btn) {
            btn.classList.remove('is-spinning');
            // reflow so the animation restarts on repeated clicks
            void btn.offsetWidth;
            btn.classList.add('is-spinning');
            setTimeout(() => btn.classList.remove('is-spinning'), 600);
        }
    }

    async function logout() {
        try {
            await window.__admin.api('POST', '/logout');
        } catch (e) {
        }
        window.location.assign('/login');
    }

    document.addEventListener('click', (e) => {
        const act = e.target.closest('[data-action]');
        if (!act) return;
        if (act.dataset.action === 'toggle-edit') return toggleEdit();
        if (act.dataset.action === 'toggle-theme') return toggleTheme();
        if (act.dataset.action === 'refresh') return refreshView(act);
        if (act.dataset.action === 'logout') return logout();
    });

    updateThemeIcon();
    startClock();
})();
