(function () {
    'use strict';

    function toggleEdit() {
        const on = document.body.classList.toggle('edit-mode');
        const lbl = document.querySelector('[data-role="edit-label"]');
        if (lbl) lbl.textContent = on ? 'Done' : 'Edit';
        ensureAddButton(on);
    }

    function ensureAddButton(on) {
        const btn = document.querySelector('.topbar__add');
        if (on && !btn) {
            const add = document.createElement('button');
            add.type = 'button';
            add.className = 'topbar__add';
            add.dataset.action = 'add';
            add.innerHTML = '<span class="topbar__ico" aria-hidden="true">+</span><span class="topbar__txt">Widget</span>';
            const editBtn = document.querySelector('[data-action="toggle-edit"]');
            editBtn.parentNode.insertBefore(add, editBtn);
        } else if (!on && btn) {
            btn.remove();
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
        const ico = btn.querySelector('.topbar__ico');
        if (ico) ico.textContent = isLight() ? '☾' : '☀';
        btn.title = isLight() ? 'Dark mode' : 'Light mode';
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

    function setMenu(open) {
        const bar = document.querySelector('.topbar');
        if (bar) bar.classList.toggle('is-menu-open', open);
        const btn = document.querySelector('[data-action="menu"]');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function toggleMenu() {
        const bar = document.querySelector('.topbar');
        setMenu(bar ? !bar.classList.contains('is-menu-open') : true);
    }
    const closeMenu = () => setMenu(false);

    document.addEventListener('click', (e) => {
        const act = e.target.closest('[data-action]');
        if (!act) {
            if (!e.target.closest('.topbar')) closeMenu();
            return;
        }
        switch (act.dataset.action) {
            case 'menu': return toggleMenu();
            case 'toggle-edit': return toggleEdit();
            case 'toggle-theme': return toggleTheme();
            case 'refresh': return refreshView(act);
            case 'add': return closeMenu();
            case 'logout': return logout();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });

    updateThemeIcon();
})();
