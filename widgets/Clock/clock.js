(function () {
  window.__widgets = window.__widgets || {};

  window.__widgets['clock'] = {
    mount(root, data) {
      const zone = data.timezone && data.timezone !== 'Local' ? data.timezone : undefined;
      const hour12 = !!data.hour12;

      const timeEl = root.querySelector('[data-role="time"]');
      const dateEl = root.querySelector('[data-role="date"]');
      const utcEl = root.querySelector('[data-role="utc"]');
      const hourHand = root.querySelector('[data-role="hour"]');
      const minHand = root.querySelector('[data-role="min"]');
      const secHand = root.querySelector('[data-role="sec"]');

      const timeFmt = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: hour12, timeZone: zone,
      });
      const dateFmt = new Intl.DateTimeFormat('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: zone,
      });
      const utcFmt = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'UTC',
      });
      const partsFmt = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: zone,
      });

      // Server-vs-client clock offset, measured once, so the UTC line shows the
      // SERVER's time and any skew against the local clock is visible.
      const serverOffset = typeof data.serverNow === 'number' ? data.serverNow - Date.now() : 0;

      function setHands(now) {
        if (!hourHand) return;
        const p = {};
        for (const part of partsFmt.formatToParts(now)) p[part.type] = part.value;
        const h = (parseInt(p.hour, 10) || 0) % 12;
        const m = parseInt(p.minute, 10) || 0;
        const s = parseInt(p.second, 10) || 0;
        const secDeg = s * 6;
        const minDeg = m * 6 + s * 0.1;
        const hourDeg = h * 30 + m * 0.5;
        secHand.setAttribute('transform', 'rotate(' + secDeg + ' 50 50)');
        minHand.setAttribute('transform', 'rotate(' + minDeg + ' 50 50)');
        hourHand.setAttribute('transform', 'rotate(' + hourDeg + ' 50 50)');
      }

      const tick = () => {
        const now = new Date();
        if (timeEl) timeEl.textContent = timeFmt.format(now);
        if (dateEl) dateEl.textContent = dateFmt.format(now);
        if (utcEl) utcEl.textContent = utcFmt.format(new Date(now.getTime() + serverOffset));
        setHands(now);
      };
      tick();
      root.__timer = setInterval(tick, 1000);
    },

    unmount(root) {
      if (root.__timer) { clearInterval(root.__timer); root.__timer = null; }
    },
  };
})();
