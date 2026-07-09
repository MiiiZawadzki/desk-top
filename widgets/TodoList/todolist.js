(function () {
    window.__widgets = window.__widgets || {};

    const csrf = () => (document.querySelector('meta[name="csrf"]') || {}).content || '';

    window.__widgets['todolist'] = {
        mount(root, data, meta) {
            const activeEl = root.querySelector('[data-role="active"]');
            const completedEl = root.querySelector('[data-role="completed"]');
            const doneWrap = root.querySelector('[data-role="done-wrap"]');
            const doneLabel = root.querySelector('[data-role="done-label"]');
            const emptyEl = root.querySelector('[data-role="empty"]');
            const clearBtn = root.querySelector('[data-role="clear"]');
            const form = root.querySelector('[data-role="add-form"]');
            const input = root.querySelector('[data-role="input"]');
            const instance = meta && meta.instance;

            const endpoint = (action) =>
                '/api/widget/action?instance=' + encodeURIComponent(instance) +
                '&action=' + encodeURIComponent(action);

            async function call(action, method, body) {
                if (!instance) return;
                const opts = {method, headers: {}};
                if (method !== 'GET') {
                    opts.headers['Content-Type'] = 'application/json';
                    opts.headers['X-CSRF-Token'] = csrf();
                    if (body) opts.body = JSON.stringify(body);
                }
                try {
                    const res = await fetch(endpoint(action), opts);
                    if (res.ok) render(await res.json());
                } catch (e) {
                }
            }

            function render(snap) {
                if (!snap) return;
                const items = snap.items || [];
                const active = items.filter((i) => !i.done);
                const completed = items.filter((i) => i.done);

                activeEl.textContent = '';
                for (const it of active) activeEl.appendChild(itemRow(it));

                completedEl.textContent = '';
                for (const it of completed) completedEl.appendChild(itemRow(it));

                if (emptyEl) emptyEl.hidden = items.length !== 0;
                if (doneWrap) doneWrap.hidden = completed.length === 0;
                if (doneLabel) doneLabel.textContent = 'Completed · ' + completed.length;
            }

            function itemRow(it) {
                const li = document.createElement('li');
                li.className = 'todo__item' + (it.done ? ' is-done' : '');
                li.dataset.id = it.id;

                const check = document.createElement('button');
                check.type = 'button';
                check.className = 'todo__check';
                check.setAttribute('aria-label', it.done ? 'Mark as not done' : 'Mark as done');
                check.dataset.act = 'toggle';

                const label = document.createElement('span');
                label.className = 'todo__text';
                label.textContent = it.text;
                label.dataset.act = 'edit';
                label.title = 'Double-click to edit';

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'todo__del';
                del.setAttribute('aria-label', 'Delete task');
                del.dataset.act = 'delete';
                del.textContent = '×';

                li.append(check, label, del);
                return li;
            }

            const onListClick = (e) => {
                const el = e.target.closest('[data-act]');
                if (!el) return;
                const li = el.closest('.todo__item');
                if (!li) return;
                const id = Number(li.dataset.id);
                if (el.dataset.act === 'toggle') call('toggle', 'POST', {id});
                else if (el.dataset.act === 'delete') call('delete', 'POST', {id});
            };

            const onListDblClick = (e) => {
                const el = e.target.closest('[data-act="edit"]');
                if (!el) return;
                const li = el.closest('.todo__item');
                const current = el.textContent;
                const next = window.prompt('Edit task', current);
                if (next != null && next.trim() && next.trim() !== current) {
                    call('edit', 'POST', {id: Number(li.dataset.id), text: next.trim()});
                }
            };

            const onSubmit = (e) => {
                e.preventDefault();
                const text = (input.value || '').trim();
                if (!text) return;
                input.value = '';
                call('add', 'POST', {text});
            };

            const onClear = () => call('clear', 'POST');

            activeEl.addEventListener('click', onListClick);
            activeEl.addEventListener('dblclick', onListDblClick);
            completedEl.addEventListener('click', onListClick);
            completedEl.addEventListener('dblclick', onListDblClick);
            form.addEventListener('submit', onSubmit);
            if (clearBtn) clearBtn.addEventListener('click', onClear);

            root.__cleanup = () => {
                activeEl.removeEventListener('click', onListClick);
                activeEl.removeEventListener('dblclick', onListDblClick);
                completedEl.removeEventListener('click', onListClick);
                completedEl.removeEventListener('dblclick', onListDblClick);
                form.removeEventListener('submit', onSubmit);
                if (clearBtn) clearBtn.removeEventListener('click', onClear);
            };

            call('list', 'GET');
        },

        unmount(root) {
            if (root.__cleanup) {
                root.__cleanup();
                root.__cleanup = null;
            }
        },
    };
})();
