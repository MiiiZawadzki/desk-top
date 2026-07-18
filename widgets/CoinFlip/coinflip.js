(function () {
    window.__widgets = window.__widgets || {};

    window.__widgets['coinflip'] = {
        mount(root) {
            const coin = root.querySelector('[data-role="coin"]');
            const flipBtn = root.querySelector('[data-role="flip"]');
            const resultEl = root.querySelector('[data-role="result"]');
            const tallyEl = root.querySelector('[data-role="tally"]');
            const resetBtn = root.querySelector('[data-role="reset"]');

            const headsLabel = (root.querySelector('.cf__face--heads .cf__label') || {}).textContent || 'Heads';
            const tailsLabel = (root.querySelector('.cf__face--tails .cf__label') || {}).textContent || 'Tails';

            let rotation = 0;
            let flipping = false;
            const counts = {heads: 0, tails: 0};

            function renderTally() {
                tallyEl.textContent =
                    headsLabel + ' ' + counts.heads + ' · ' + tailsLabel + ' ' + counts.tails;
                if (resetBtn) resetBtn.disabled = (counts.heads + counts.tails) === 0;
            }
            renderTally();

            function flip() {
                if (flipping) return;
                flipping = true;
                flipBtn.disabled = true;
                resultEl.classList.remove('is-in');
                resultEl.textContent = '…';

                const result = Math.random() < 0.5 ? 'heads' : 'tails';

                const spins = 4 + Math.floor(Math.random() * 3);
                let target = rotation + spins * 360;
                const want = result === 'heads' ? 0 : 180;
                const mod = ((target % 360) + 360) % 360;
                target += (want - mod + 360) % 360;
                rotation = target;
                coin.style.transform = 'rotateX(' + rotation + 'deg)';

                let settled = false;
                const settle = () => {
                    if (settled) return;
                    settled = true;
                    coin.removeEventListener('transitionend', onEnd);
                    counts[result] += 1;
                    resultEl.textContent = result === 'heads' ? headsLabel : tailsLabel;
                    resultEl.classList.add('is-in');
                    renderTally();
                    flipping = false;
                    flipBtn.disabled = false;
                };
                const onEnd = (e) => { if (e.propertyName === 'transform') settle(); };
                coin.addEventListener('transitionend', onEnd);

                root.__settleTimer = setTimeout(settle, 1400);
            }

            function reset() {
                if (flipping) return;
                counts.heads = 0;
                counts.tails = 0;
                renderTally();
                resultEl.classList.remove('is-in');
                resultEl.innerHTML = '&nbsp;';
            }

            const onKey = (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); flip(); }
            };
            flipBtn.addEventListener('click', flip);
            coin.addEventListener('click', flip);
            coin.addEventListener('keydown', onKey);
            if (resetBtn) resetBtn.addEventListener('click', reset);

            root.__cleanup = () => {
                flipBtn.removeEventListener('click', flip);
                coin.removeEventListener('click', flip);
                coin.removeEventListener('keydown', onKey);
                if (resetBtn) resetBtn.removeEventListener('click', reset);
                if (root.__settleTimer) { clearTimeout(root.__settleTimer); root.__settleTimer = null; }
            };
        },

        unmount(root) {
            if (root.__cleanup) { root.__cleanup(); root.__cleanup = null; }
        },
    };
})();
