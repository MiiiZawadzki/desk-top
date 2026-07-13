(function () {
    const SCRIPT_CACHE = new Map();

    const BASE = `
    .w-state { padding:16px; font:13px system-ui, sans-serif; color:#9ca3af; }
    .w-error { color:#b91c1c; }
  `;

    function showState(root, msg, isError) {
        root.innerHTML = `<style>${BASE}</style><div class="w-state${isError ? ' w-error' : ''}">${msg}</div>`;
    }

    function loadScript(type, url) {
        if (!SCRIPT_CACHE.has(type)) {
            SCRIPT_CACHE.set(type, new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = url;
                s.onload = () => resolve();
                s.onerror = () => reject(new Error('asset load failed: ' + type));
                document.head.appendChild(s);
            }));
        }
        return SCRIPT_CACHE.get(type);
    }

    function isDisabled(host) {
        const widget = host.closest('.widget');
        return widget && widget.dataset.enabled === '0';
    }

    async function mount(host) {
        if (host.__mounted) return;
        host.__mounted = true;

        const root = host.shadowRoot || host.attachShadow({mode: 'open'});
        host.__root = root;

        if (isDisabled(host)) {
            showState(root, 'Disabled');
            return;
        }

        showState(root, 'Loading…');

        try {
            const res = await fetch('/api/widget?instance=' + encodeURIComponent(host.dataset.instance));
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const p = await res.json();

            root.innerHTML = `<style>${p.css || ''}</style>${p.html || ''}`;
            host.__type = p.type;

            if (p.jsUrl && p.type) {
                await loadScript(p.type, p.jsUrl);
                const reg = (window.__widgets || {})[p.type];
                if (reg && typeof reg.mount === 'function') {
                    reg.mount(root, p.data || {}, {instance: host.dataset.instance});
                }
            }
        } catch (e) {
            showState(root, 'Widget unavailable', true);
        }
    }

    function unmount(host) {
        const root = host.__root;
        const type = host.__type;
        const reg = (window.__widgets || {})[type];
        if (root && reg && typeof reg.unmount === 'function') {
            try {
                reg.unmount(root);
            } catch (e) {
            }
        }
        if (root) root.innerHTML = '';
        host.__mounted = false;
        host.__type = null;
    }

    function mountAll() {
        document.querySelectorAll('.widget__host[data-widget]').forEach(mount);
    }

    // Force every mounted widget that supports it to re-fetch its data
    function refreshAll() {
        document.querySelectorAll('.widget__host[data-widget]').forEach((host) => {
            const fn = host.__root && host.__root.__refresh;
            if (typeof fn === 'function') {
                try {
                    fn();
                } catch (e) {
                }
            }
        });
    }

    window.Dashboard = {mount, mountAll, unmount, refreshAll};

    mountAll();
})();
