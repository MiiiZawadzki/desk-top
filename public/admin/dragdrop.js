(function () {
    'use strict';

    const A = window.__admin;
    const grid = A.grid;

    const COLS = 12;
    const ROW_H = 80;
    const GAP = 18;
    const MOBILE = 640;

    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

    function gridMetrics() {
        const cs = getComputedStyle(grid);
        const padL = parseFloat(cs.paddingLeft) || 0;
        const padR = parseFloat(cs.paddingRight) || 0;
        const colGap = parseFloat(cs.columnGap || cs.gap) || GAP;
        const rowGap = parseFloat(cs.rowGap || cs.gap) || GAP;
        const inner = grid.clientWidth - padL - padR;
        const colW = (inner - (COLS - 1) * colGap) / COLS;
        return {colStride: colW + colGap, rowStride: ROW_H + rowGap};
    }

    function startGesture(widget, kind, startEvent, target) {
        if (window.innerWidth <= MOBILE) return;
        startEvent.preventDefault();

        const m = gridMetrics();
        const sx = startEvent.clientX, sy = startEvent.clientY;
        const ox = parseInt(widget.dataset.x, 10) || 1;
        const oy = parseInt(widget.dataset.y, 10) || 1;
        const ow = parseInt(widget.dataset.w, 10) || 3;
        const oh = parseInt(widget.dataset.h, 10) || 2;

        target.setPointerCapture(startEvent.pointerId);
        widget.classList.add('is-dragging');

        const next = {x: ox, y: oy, w: ow, h: oh};

        function move(e) {
            const dCol = Math.round((e.clientX - sx) / m.colStride);
            const dRow = Math.round((e.clientY - sy) / m.rowStride);
            if (kind === 'drag') {
                next.x = clamp(ox + dCol, 1, COLS - ow + 1);
                next.y = Math.max(1, oy + dRow);
            } else {
                next.w = clamp(ow + dCol, 1, COLS - ox + 1);
                next.h = Math.max(1, oh + dRow);
            }
            widget.style.gridColumn = next.x + ' / span ' + next.w;
            widget.style.gridRow = next.y + ' / span ' + next.h;
            widget.style.setProperty('--h', next.h);
        }

        async function up() {
            target.releasePointerCapture(startEvent.pointerId);
            target.removeEventListener('pointermove', move);
            target.removeEventListener('pointerup', up);
            widget.classList.remove('is-dragging');
            widget.dataset.x = next.x;
            widget.dataset.y = next.y;
            widget.dataset.w = next.w;
            widget.dataset.h = next.h;
            try {
                await A.api('POST', '/api/layout', [{
                    id: widget.dataset.id,
                    x: next.x,
                    y: next.y,
                    w: next.w,
                    h: next.h
                }]);
            } catch (err) {
                A.toast('Failed to save layout: ' + err.message, true);
            }
        }

        target.addEventListener('pointermove', move);
        target.addEventListener('pointerup', up);
    }

    document.addEventListener('pointerdown', (e) => {
        if (!document.body.classList.contains('edit-mode')) return;
        const handle = e.target.closest('.chrome__handle');
        if (handle) return startGesture(handle.closest('.widget'), 'drag', e, handle);
        const resize = e.target.closest('.widget__resize');
        if (resize) return startGesture(resize.closest('.widget'), 'resize', e, resize);
    });
})();
