(function () {
    window.__widgets = window.__widgets || {};

    const REFRESH_MS = 10 * 60 * 1000; // 10 minutes

    const WMO = {
        0: ['Clear', '☀️', '🌙'],
        1: ['Mainly clear', '🌤️', '🌙'],
        2: ['Partly cloudy', '⛅', '☁️'],
        3: ['Overcast', '☁️', '☁️'],
        45: ['Fog', '🌫️', '🌫️'], 48: ['Rime fog', '🌫️', '🌫️'],
        51: ['Light drizzle', '🌦️', '🌦️'], 53: ['Drizzle', '🌦️', '🌦️'], 55: ['Heavy drizzle', '🌧️', '🌧️'],
        56: ['Freezing drizzle', '🌧️', '🌧️'], 57: ['Freezing drizzle', '🌧️', '🌧️'],
        61: ['Light rain', '🌦️', '🌧️'], 63: ['Rain', '🌧️', '🌧️'], 65: ['Heavy rain', '🌧️', '🌧️'],
        66: ['Freezing rain', '🌧️', '🌧️'], 67: ['Freezing rain', '🌧️', '🌧️'],
        71: ['Light snow', '🌨️', '🌨️'], 73: ['Snow', '🌨️', '🌨️'], 75: ['Heavy snow', '❄️', '❄️'],
        77: ['Snow grains', '🌨️', '🌨️'],
        80: ['Showers', '🌦️', '🌧️'], 81: ['Showers', '🌧️', '🌧️'], 82: ['Violent showers', '⛈️', '⛈️'],
        85: ['Snow showers', '🌨️', '🌨️'], 86: ['Snow showers', '❄️', '❄️'],
        95: ['Thunderstorm', '⛈️', '⛈️'], 96: ['Thunderstorm', '⛈️', '⛈️'], 99: ['Thunderstorm', '⛈️', '⛈️'],
    };
    const look = (code, isDay) => {
        const e = WMO[code] || ['—', '🌡️', '🌡️'];
        return {text: e[0], icon: isDay === false ? e[2] : e[1]};
    };

    function dayLabel(dateStr, i) {
        if (i === 0) return 'Today';
        const d = new Date(dateStr + 'T00:00:00');
        return isNaN(d) ? dateStr : d.toLocaleDateString('en-GB', {weekday: 'short'});
    }

    function hourLabel(t) {
        const m = /T(\d{2})/.exec(String(t));
        return m ? m[1] : '';
    }

    window.__widgets['weather'] = {
        mount(root, data, meta) {
            const els = {
                main: root.querySelector('[data-role="main"]'),
                error: root.querySelector('[data-role="error"]'),
                place: root.querySelector('[data-role="place"]'),
                icon: root.querySelector('[data-role="icon"]'),
                temp: root.querySelector('[data-role="temp"]'),
                unit: root.querySelector('[data-role="unit"]'),
                cond: root.querySelector('[data-role="cond"]'),
                feels: root.querySelector('[data-role="feels"]'),
                humidity: root.querySelector('[data-role="humidity"]'),
                wind: root.querySelector('[data-role="wind"]'),
                rainWrap: root.querySelector('[data-role="rain-wrap"]'),
                rainDay: root.querySelector('[data-role="rain-day"]'),
                rainPrev: root.querySelector('[data-role="rain-prev"]'),
                rainNext: root.querySelector('[data-role="rain-next"]'),
                hours: root.querySelector('[data-role="hours"]'),
                days: root.querySelector('[data-role="days"]'),
            };
            const instance = meta && meta.instance;

            // Rain-chart paging state: hours grouped by day, and the day on screen.
            let rainDays = [];   // [{ date, hours: [...] }]
            let selDate = null;  // yyyy-mm-dd currently shown (preserved across refreshes)
            let rainNow = null;  // "now" timestamp
            let rainUnit = 'mm';

            const stepDay = (dir) => {
                const idx = rainDays.findIndex((x) => x.date === selDate);
                const next = idx + dir;
                if (next >= 0 && next < rainDays.length) {
                    selDate = rainDays[next].date;
                    drawDay();
                }
            };
            if (els.rainPrev) els.rainPrev.addEventListener('click', () => stepDay(-1));
            if (els.rainNext) els.rainNext.addEventListener('click', () => stepDay(1));

            function render(d) {
                if (!d || d.error) {
                    els.error.textContent = (d && d.error) || 'Weather is unavailable right now.';
                    els.error.hidden = false;
                    els.main.hidden = true;
                    return;
                }
                els.error.hidden = true;
                els.main.hidden = false;

                const c = d.current || {};
                const now = look(c.code, c.isDay);

                els.place.textContent = d.place || '';
                els.icon.textContent = now.icon;
                els.temp.textContent = c.temp == null ? '--' : c.temp;
                els.unit.textContent = d.unit || '°';
                els.cond.textContent = now.text + (d.stale ? ' · offline' : '');

                els.feels.textContent = c.feels == null ? '' : 'Feels ' + c.feels + '°';
                els.humidity.textContent = c.humidity == null ? '' : '💧 ' + c.humidity + '%';
                els.wind.textContent = c.wind == null ? '' : '💨 ' + c.wind + ' ' + (d.windUnit || '');

                renderRain(d);

                els.days.textContent = '';
                (d.daily || []).forEach((day, i) => els.days.appendChild(dayCol(day, i, d.unit)));
            }

            function renderRain(d) {
                if (!els.rainWrap) return;
                const hours = d.hourly || [];
                if (!hours.length) { els.rainWrap.hidden = true; return; }
                els.rainWrap.hidden = false;

                rainNow = d.now || hours[0].time;
                rainUnit = d.rainUnit || 'mm';

                const byDate = new Map();
                for (const h of hours) {
                    const date = String(h.time).slice(0, 10);
                    if (!byDate.has(date)) byDate.set(date, []);
                    byDate.get(date).push(h);
                }
                rainDays = [...byDate.entries()].map(([date, hs]) => ({date: date, hours: hs}));

                const today = String(rainNow).slice(0, 10);
                if (selDate == null || !rainDays.some((x) => x.date === selDate)) {
                    selDate = rainDays.some((x) => x.date === today) ? today : (rainDays[0] && rainDays[0].date);
                }
                drawDay();
            }

            function drawDay() {
                const idx = rainDays.findIndex((x) => x.date === selDate);
                const day = rainDays[idx];
                if (!day) return;

                const today = String(rainNow).slice(0, 10);
                els.rainDay.textContent = dayTitle(day.date, today);
                if (els.rainPrev) els.rainPrev.disabled = idx <= 0;
                if (els.rainNext) els.rainNext.disabled = idx >= rainDays.length - 1;

                els.hours.textContent = '';
                els.hours.appendChild(buildChart(day.hours));
            }

            function buildChart(hours) {
                const frag = document.createDocumentFragment();
                const rowPct = mkRow('wx__crow--pct');
                const plot = document.createElement('div');
                plot.className = 'wx__plot';
                const rowTime = mkRow('wx__crow--time');

                hours.forEach((h, i) => {
                    const hh = String(h.time).slice(11, 13);
                    const past = rainNow ? (h.time < rainNow) : false;
                    const isNow = h.time === rainNow;
                    const prob = h.prob == null ? 0 : h.prob;
                    const showLabel = i % 3 === 0;

                    const pc = mkCell(rowPct, isNow);
                    if (showLabel) pc.textContent = prob + '%';

                    const col = mkCell(plot, isNow);
                    if (past) col.classList.add('is-past');
                    const bar = document.createElement('div');
                    bar.className = 'wx__bar';
                    bar.style.height = Math.max(prob, 2) + '%';
                    if (h.mm != null && h.mm > 0) bar.classList.add('is-wet');
                    col.appendChild(bar);
                    col.title = hh + ':00 · ' + prob + '% chance' +
                        (h.mm != null && h.mm > 0 ? ' · ' + h.mm + ' ' + rainUnit : '');

                    const tc = mkCell(rowTime, isNow);
                    if (showLabel) tc.textContent = hh + ':00';
                });

                frag.append(rowPct, plot, rowTime);
                return frag;
            }

            function mkRow(mod) {
                const r = document.createElement('div');
                r.className = 'wx__crow ' + mod;
                return r;
            }
            function mkCell(row, isNow) {
                const c = document.createElement('div');
                c.className = 'wx__cell';
                if (isNow) c.classList.add('is-now');
                row.appendChild(c);
                return c;
            }
            function dayTitle(date, today) {
                if (date === today) return 'Today';
                const dt = new Date(date + 'T00:00:00');
                return isNaN(dt) ? date : dt.toLocaleDateString('en-GB', {weekday: 'short', day: 'numeric'});
            }

            function dayCol(day, i, unit) {
                const col = document.createElement('div');
                col.className = 'wx__day';

                const name = document.createElement('div');
                name.className = 'wx__day-name';
                name.textContent = dayLabel(day.date, i);

                const ic = document.createElement('div');
                ic.className = 'wx__day-icon';
                ic.textContent = look(day.code, true).icon;

                const hi = document.createElement('div');
                hi.className = 'wx__day-hi';
                hi.textContent = day.max == null ? '' : day.max + '°';

                const lo = document.createElement('div');
                lo.className = 'wx__day-lo';
                lo.textContent = day.min == null ? '' : day.min + '°';

                col.append(name, ic, hi, lo);

                if (day.prob != null && day.prob > 0) {
                    const rain = document.createElement('div');
                    rain.className = 'wx__day-rain';
                    rain.textContent = '💧' + day.prob + '%';
                    if (day.rain != null && day.rain > 0) col.title = day.rain + ' rain';
                    col.appendChild(rain);
                }
                return col;
            }

            async function refresh() {
                if (!instance) return;
                try {
                    const res = await fetch('/api/data?instance=' + encodeURIComponent(instance));
                    if (res.ok) {
                        const p = await res.json();
                        if (p && p.data) render(p.data);
                    }
                } catch (e) {
                }
            }

            render(data || {});
            root.__timer = setInterval(refresh, REFRESH_MS);
        },

        unmount(root) {
            if (root.__timer) {
                clearInterval(root.__timer);
                root.__timer = null;
            }
        },
    };
})();
