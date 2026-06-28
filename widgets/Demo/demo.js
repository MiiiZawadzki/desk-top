// DEMO / TEMPLATE WIDGET — the JS half of the contract. Registered under a key
// equal to 'type' from the manifest; two types can't collide (different keys).
//
// The whole contract is one object: { mount, unmount }.
//   mount(root, data, meta)
//     root = THIS instance's Shadow Root. querySelector only sees this widget —
//            it cannot reach the page or other widgets (hard isolation).
//     data = the result of Demo::data() in PHP (server-computed).
//     meta = { instance } — the instance id, used to live-refresh via /api/data.
//   unmount(root)
//     Called by the host on remove/disable. Tear down timers/listeners here, or
//     they keep running on detached nodes (a memory leak).
(function () {
  window.__widgets = window.__widgets || {};

  window.__widgets['demo'] = {
    mount(root, data, meta) {
      const wrap       = root.querySelector('.demo');
      const greetingEl = root.querySelector('[data-role="greeting"]');
      const clockEl    = root.querySelector('[data-role="clock"]');
      const tipEl      = root.querySelector('[data-role="tip"]');
      const refreshBtn = root.querySelector('[data-role="refresh"]');

      // Paint whatever the server sent (greeting, tip, accent colour).
      const show = (d) => {
        if (wrap && d.accent) wrap.style.setProperty('--demo-accent', d.accent);
        if (greetingEl && d.greeting != null) greetingEl.textContent = d.greeting;
        if (tipEl && d.tip != null) tipEl.textContent = d.tip;
      };
      show(data || {});

      // A live clock, ticking once a second. Keep the id on root so unmount clears it.
      const tick = () => {
        if (clockEl) clockEl.textContent = new Date().toLocaleTimeString('en-GB');
      };
      tick();
      root.__timer = setInterval(tick, 1000);

      // "Show another tip" re-fetches data() for THIS instance (note meta.instance)
      // without re-downloading html/css/js — a fresh tip appears each click.
      const onRefresh = async () => {
        if (!meta || !meta.instance) return;
        refreshBtn.disabled = true;
        try {
          const res = await fetch('/api/data?instance=' + encodeURIComponent(meta.instance));
          if (res.ok) {
            const p = await res.json();
            if (p.data) { show(p.data); flash(tipEl); }
          }
        } catch (e) {
          /* a transient network error must not break the widget */
        } finally {
          refreshBtn.disabled = false;
        }
      };
      if (refreshBtn) refreshBtn.addEventListener('click', onRefresh);

      // Remember how to undo the listener (the timer is cleared in unmount).
      root.__cleanup = () => {
        if (refreshBtn) refreshBtn.removeEventListener('click', onRefresh);
      };

      function flash(el) {
        if (!el) return;
        el.classList.remove('is-flash');
        void el.offsetWidth; // restart the CSS animation
        el.classList.add('is-flash');
      }
    },

    unmount(root) {
      if (root.__timer) { clearInterval(root.__timer); root.__timer = null; }
      if (root.__cleanup) { root.__cleanup(); root.__cleanup = null; }
    },
  };
})();
