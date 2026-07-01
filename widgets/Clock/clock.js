(function () {
  window.__widgets = window.__widgets || {};

  window.__widgets['clock'] = {
    mount(root, data) {
      const timeEl = root.querySelector('[data-role="time"]');
      const dateEl = root.querySelector('[data-role="date"]');
      const utcEl = root.querySelector('[data-role="utc"]');

      const zone = data.timezone && data.timezone !== 'Local' ? data.timezone : undefined;
      const timeFmt = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: !!data.hour12, timeZone: zone,
      });
      const dateFmt = new Intl.DateTimeFormat('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: zone,
      });
      const utcFmt = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'UTC',
      });

      // Server-vs-client clock offset, measured once at mount, so the UTC line
      // shows the SERVER's time and any skew against the local clock is visible.
      const serverOffset = typeof data.serverNow === 'number' ? data.serverNow - Date.now() : 0;

      const tick = () => {
        const now = new Date();
        if (timeEl) timeEl.textContent = timeFmt.format(now);
        if (dateEl) dateEl.textContent = dateFmt.format(now);
        if (utcEl) utcEl.textContent = utcFmt.format(new Date(now.getTime() + serverOffset));
      };
      tick();
      root.__timer = setInterval(tick, 1000);
    },

    unmount(root) {
      if (root.__timer) { clearInterval(root.__timer); root.__timer = null; }
    },
  };
})();
