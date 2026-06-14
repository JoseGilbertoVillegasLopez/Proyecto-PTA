(function () {
    'use strict';

    function bootModuloAcceso(context) {
        const root = (context || document).querySelector('[data-ma-view="edit"]');
        if (!root) return;
        if (root.dataset.maInit === '1') return;
        root.dataset.maInit = '1';

        // ── Helpers ──────────────────────────────────────────────────────────

        function getList(name) {
            return root.querySelector('#list-' + name);
        }

        function updateCount(name) {
            const list  = getList(name);
            const count = root.querySelector('#count-' + name.replace(/_/g, '-'));
            if (list && count) {
                count.textContent = list.querySelectorAll('.ma-puesto-card').length;
            }
        }

        function buildActions(colName) {
            if (colName === 'encargados') {
                return `
                    <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--right"
                            data-from="encargados" data-to="con_acceso" title="Mover a Con acceso">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--remove"
                            data-from="encargados" title="Quitar acceso">
                        <i class="bi bi-x-lg"></i>
                    </button>`;
            }
            if (colName === 'con_acceso') {
                return `
                    <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--left"
                            data-from="con_acceso" data-to="encargados" title="Promover a Encargado">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--remove"
                            data-from="con_acceso" title="Quitar acceso">
                        <i class="bi bi-x-lg"></i>
                    </button>`;
            }
            // sin_acceso
            return `
                <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--enc"
                        data-from="sin_acceso" data-to="encargados" title="Agregar como Encargado">
                    <i class="bi bi-person-fill-gear"></i>
                </button>
                <button type="button" class="ma-puesto-card-btn ma-puesto-card-btn--acc"
                        data-from="sin_acceso" data-to="con_acceso" title="Agregar con acceso">
                    <i class="bi bi-person-check-fill"></i>
                </button>`;
        }

        function moveCard(card, fromName, toName) {
            const toList = getList(toName);
            if (!toList) return;

            card.querySelector('.ma-puesto-card-actions').innerHTML = buildActions(toName);
            toList.appendChild(card);

            updateCount(fromName);
            updateCount(toName);
            bindCard(card);
        }

        function removeCard(card, fromName) {
            card.querySelector('.ma-puesto-card-actions').innerHTML = buildActions('sin_acceso');
            getList('sin_acceso').appendChild(card);
            updateCount(fromName);
            updateCount('sin_acceso');
            bindCard(card);
        }

        function bindCard(card) {
            card.querySelectorAll('button[data-from]').forEach(btn => {
                const fresh = btn.cloneNode(true);
                btn.replaceWith(fresh);

                fresh.addEventListener('click', () => {
                    const from = fresh.dataset.from;
                    const to   = fresh.dataset.to;
                    if (fresh.classList.contains('ma-puesto-card-btn--remove')) {
                        removeCard(card, from);
                    } else {
                        moveCard(card, from, to);
                    }
                });
            });
        }

        // ── Botón "Todos" — mueve todos los de sin_acceso a con_acceso ────────

        const todosBtn = root.querySelector('#ma-todos-btn');
        if (todosBtn) {
            todosBtn.addEventListener('click', () => {
                const sinList = getList('sin_acceso');
                const cards   = Array.from(sinList.querySelectorAll('.ma-puesto-card'));
                cards.forEach(card => moveCard(card, 'sin_acceso', 'con_acceso'));
            });
        }

        // ── Inicializar botones en todas las cards existentes ─────────────────

        root.querySelectorAll('.ma-puesto-card').forEach(card => bindCard(card));

        // ── Al submit: serializar ids en inputs hidden ─────────────────────────

        const form = root.querySelector('#ma-edit-form');
        if (form) {
            form.addEventListener('submit', () => {
                root.querySelector('#ma-inputs-encargados').innerHTML = '';
                root.querySelector('#ma-inputs-con-acceso').innerHTML  = '';

                getList('encargados').querySelectorAll('.ma-puesto-card').forEach(card => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = 'encargados[]';
                    input.value = card.dataset.id;
                    root.querySelector('#ma-inputs-encargados').appendChild(input);
                });

                getList('con_acceso').querySelectorAll('.ma-puesto-card').forEach(card => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = 'con_acceso[]';
                    input.value = card.dataset.id;
                    root.querySelector('#ma-inputs-con-acceso').appendChild(input);
                });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => bootModuloAcceso(document));
    document.addEventListener('turbo:load',       () => bootModuloAcceso(document));
    document.addEventListener('turbo:frame-load', (e) => {
        if (e.target.id === 'content') bootModuloAcceso(e.target);
    });

})();
