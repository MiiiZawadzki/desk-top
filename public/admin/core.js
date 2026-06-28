(function () {
    'use strict';

    const csrf = (document.querySelector('meta[name="csrf"]') || {}).content || '';
    const grid = document.querySelector('.grid');

    function esc(s) {
        return String(s).replace(/[&<>"']/g, (c) =>
            ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    }

    class Safe {
        constructor(value) {
            this.value = value;
        }
    }

    function render(v) {
        if (v instanceof Safe) return v.value;
        if (Array.isArray(v)) return v.map(render).join('');
        return esc(String(v));
    }

    function html(strings, ...values) {
        let out = '';
        strings.forEach((str, i) => {
            out += str + (i < values.length ? render(values[i]) : '');
        });
        return new Safe(out);
    }

    function fromHTML(safe) {
        const tpl = document.createElement('template');
        tpl.innerHTML = render(safe).trim();
        return tpl.content.firstElementChild;
    }

    function setHTML(el, safe) {
        el.innerHTML = render(safe);
    }

    async function api(method, url, body) {
        const opts = {method, headers: {}};
        if (method !== 'GET') opts.headers['X-CSRF-Token'] = csrf;
        if (body !== undefined) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
        return data;
    }

    function toast(msg, isError) {
        let host = document.querySelector('.toasts');
        if (!host) {
            host = document.createElement('div');
            host.className = 'toasts';
            document.body.appendChild(host);
        }
        const el = document.createElement('div');
        el.className = 'toast' + (isError ? ' toast--error' : '');
        el.textContent = msg;
        host.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }

    function openModal(title, bodyHtml) {
        const overlay = fromHTML(html`
            <div class="modal">
                <div class="modal__box">
                    <div class="modal__head"><span>${title}</span>
                        <button type="button" class="modal__close">✕</button>
                    </div>
                    <div class="modal__body">${bodyHtml}</div>
                </div>
            </div>`);
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.classList.contains('modal__close')) closeModal(overlay);
        });
        return overlay;
    }

    function closeModal(m) {
        if (m && m.parentNode) m.remove();
    }

    window.__admin = {csrf, grid, esc, html, fromHTML, setHTML, api, toast, openModal, closeModal};
})();
