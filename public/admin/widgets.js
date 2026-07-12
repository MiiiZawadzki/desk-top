(function () {
    'use strict';
    const A = window.__admin;
    const {html, api, toast} = A;

    const hostOf = (widget) => widget.querySelector('.widget__host');

    function remount(widget) {
        const host = hostOf(widget);
        window.Dashboard.unmount(host);
        window.Dashboard.mount(host);
    }

    function buildChrome(widget) {
        const chrome = widget.querySelector('.widget__chrome');
        if (!chrome || chrome.dataset.built) return;
        chrome.dataset.built = '1';

        A.setHTML(chrome, html`
            <div class="chrome__bar">
                <button type="button" class="chrome__btn chrome__handle" title="Drag" aria-label="Drag">⠿</button>
                <button type="button" class="chrome__btn" data-action="config" title="Configure" aria-label="Configure">
                    ⚙
                </button>
                <button type="button" class="chrome__btn" data-action="toggle"></button>
                <button type="button" class="chrome__btn chrome__btn--danger" data-action="remove" title="Remove"
                        aria-label="Remove">✕
                </button>
            </div>`);
        refreshToggleBtn(widget);

        if (!widget.querySelector('.widget__resize')) {
            const rs = document.createElement('div');
            rs.className = 'widget__resize';
            rs.title = 'Resize';
            widget.appendChild(rs);
        }
    }

    function refreshToggleBtn(widget) {
        const btn = widget.querySelector('[data-action="toggle"]');
        if (!btn) return;
        const enabled = widget.dataset.enabled !== '0';
        btn.textContent = enabled ? '●' : '○';
        btn.title = enabled ? 'Disable' : 'Enable';
    }

    async function onConfig(widget) {
        try {
            const meta = (await loadCatalog()).find((t) => t.type === widget.dataset.type);
            const all = (await api('GET', '/api/instances')).instances || [];
            const inst = all.find((i) => String(i.id) === String(widget.dataset.id)) || {};
            openConfigModal(widget, meta, inst);
        } catch (e) {
            toast('Failed to load config: ' + e.message, true);
        }
    }

    async function onToggle(widget) {
        const id = widget.dataset.id;
        const next = widget.dataset.enabled === '0';
        try {
            await api('PATCH', '/api/instances?id=' + encodeURIComponent(id), {enabled: next});
            widget.dataset.enabled = next ? '1' : '0';
            refreshToggleBtn(widget);
            remount(widget);
        } catch (e) {
            toast('Failed to toggle: ' + e.message, true);
        }
    }

    async function onRemove(widget) {
        if (!confirm('Remove this widget?')) return;
        try {
            await api('DELETE', '/api/instances?id=' + encodeURIComponent(widget.dataset.id));
            window.Dashboard.unmount(hostOf(widget));
            widget.remove();
            toast('Widget removed');
        } catch (e) {
            toast('Failed to remove: ' + e.message, true);
        }
    }

    let typeCatalog = null;

    async function loadCatalog() {
        if (!typeCatalog) typeCatalog = (await api('GET', '/api/types')).types || [];
        return typeCatalog;
    }

    async function openAddModal() {
        try {
            const cat = await loadCatalog();
            const cards = cat.map((t) => html`
                <button type="button" class="catalog__card" data-type="${t.type}">
                    <span class="catalog__label">${t.label}</span>
                    <span class="catalog__meta">${t.size.w}×${t.size.h}</span>
                </button>`);
            const modal = A.openModal('Add widget', html`
                <div class="catalog">${cards}</div>`);

            modal.querySelectorAll('.catalog__card').forEach((card) => {
                card.addEventListener('click', async () => {
                    try {
                        const res = await api('POST', '/api/instances', {type: card.dataset.type});
                        injectWidget(res.instance);
                        A.closeModal(modal);
                        toast('Widget added');
                    } catch (e) {
                        toast('Failed to add: ' + e.message, true);
                    }
                });
            });
        } catch (e) {
            toast('Failed to load catalog: ' + e.message, true);
        }
    }

    function injectWidget(inst) {
        const l = inst.layout || {x: 1, y: 1, w: 3, h: 2};
        const widget = A.fromHTML(html`
            <div class="widget" data-enabled="${inst.enabled === false ? '0' : '1'}"
                 data-id="${inst.id}" data-type="${inst.type}" data-title="${inst.title || inst.type}"
                 data-x="${l.x}" data-y="${l.y}" data-w="${l.w}" data-h="${l.h}">
                <div class="widget__host" data-widget data-instance="${inst.id}"></div>
                <div class="widget__chrome"></div>
            </div>`);
        widget.style.gridColumn = l.x + ' / span ' + l.w;
        widget.style.gridRow = l.y + ' / span ' + l.h;
        widget.style.setProperty('--h', l.h);
        A.grid.appendChild(widget);
        buildChrome(widget);
        window.Dashboard.mount(hostOf(widget));
    }

    function openConfigModal(widget, meta, inst) {
        const schema = (meta && meta.configSchema) || {};
        const cfg = inst.config || {};

        const rows = Object.keys(schema).map((key) => {
            const f = schema[key];
            const val = cfg[key] !== undefined ? cfg[key] : (f.default !== undefined ? f.default : '');
            const input = f.type === 'select'
                ? html`<select name="${key}">${(f.options || []).map((o) =>
                            html`
                                <option value="${o}" ${o === val ? ' selected' : ''}>${o}</option>`)}</select>`
                : html`<input type="text" name="${key}" value="${String(val)}">`;
            return html`<label class="form__row"><span>${f.label || key}</span>${input}</label>`;
        });

        const modal = A.openModal('Configure', html`
            <form class="form">
                ${rows.length ? rows : html`<p>No settings.</p>`}
                <div class="form__actions">
                    <button type="submit">Save</button>
                </div>
            </form>`);

        modal.querySelector('.form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const config = {};
            modal.querySelectorAll('[name]').forEach((el) => {
                config[el.name] = el.value;
            });
            try {
                await api('PATCH', '/api/instances?id=' + encodeURIComponent(widget.dataset.id), {config});
                remount(widget);
                A.closeModal(modal);
                toast('Saved');
            } catch (err) {
                toast('Failed to save: ' + err.message, true);
            }
        });
    }

    document.addEventListener('click', (e) => {
        const act = e.target.closest('[data-action]');
        if (!act) return;
        if (act.dataset.action === 'add') return openAddModal();

        const widget = act.closest('.widget');
        if (!widget) return;
        if (act.dataset.action === 'config') return onConfig(widget);
        if (act.dataset.action === 'toggle') return onToggle(widget);
        if (act.dataset.action === 'remove') return onRemove(widget);
    });

    document.querySelectorAll('.widget').forEach(buildChrome);
})();
