(function () {
    'use strict';

    function bootModuloAcceso(context) {
        const root = (context || document).querySelector('[data-ma-view="edit"]');
        if (!root) return;
        if (root.dataset.maInit === '1') return;
        root.dataset.maInit = '1';

        // ── Configuración del módulo ──────────────────────────────────────────
        const usaEncargado = root.dataset.usaEncargado === '1';
        const usaAcceso    = root.dataset.usaAcceso    === '1';

        // ── Estado independiente por columna ─────────────────────────────────
        const encSet = new Set();
        const accSet = new Set();

        // ── Helpers ──────────────────────────────────────────────────────────

        function listEl(name) {
            return root.querySelector('#list-' + name.replace(/_/g, '-'));
        }

        function updateCount(name) {
            const badge = root.querySelector('#count-' + name.replace(/_/g, '-'));
            const list  = listEl(name);
            if (badge && list) {
                badge.textContent = list.querySelectorAll('.ma-puesto-card').length;
            }
        }

        // Reconstruye los botones del card en la columna "Puestos"
        function refreshPuestosCard(id) {
            const card = listEl('puestos').querySelector(`.ma-puesto-card[data-id="${id}"]`);
            if (!card) return;

            const actions = card.querySelector('.ma-puesto-card-actions');
            actions.innerHTML = '';

            if (usaEncargado && !encSet.has(id)) {
                const btn = document.createElement('button');
                btn.type      = 'button';
                btn.className = 'ma-puesto-card-btn ma-puesto-card-btn--enc';
                btn.title     = 'Agregar como Encargado';
                btn.innerHTML = '<i class="bi bi-person-fill-gear"></i>';
                actions.appendChild(btn);
            }
            if (usaAcceso && !accSet.has(id)) {
                const btn = document.createElement('button');
                btn.type      = 'button';
                btn.className = 'ma-puesto-card-btn ma-puesto-card-btn--acc';
                btn.title     = 'Agregar con acceso';
                btn.innerHTML = '<i class="bi bi-person-check-fill"></i>';
                actions.appendChild(btn);
            }

            bindPuestosCard(card);
        }

        // Card para Encargados / Con acceso (solo botón quitar)
        function makeColCard(id, nombre, colName) {
            const div = document.createElement('div');
            div.className      = 'ma-puesto-card';
            div.dataset.id     = id;
            div.dataset.nombre = nombre;

            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ma-puesto-card-btn ma-puesto-card-btn--remove';
            btn.title     = colName === 'encargados' ? 'Quitar de Encargados' : 'Quitar de Con acceso';
            btn.innerHTML = '<i class="bi bi-x-lg"></i>';
            btn.addEventListener('click', () => removeFromCol(id, nombre, colName));

            div.innerHTML = `<span class="ma-puesto-card-nombre">${nombre}</span>`;
            const actions = document.createElement('div');
            actions.className = 'ma-puesto-card-actions';
            actions.appendChild(btn);
            div.appendChild(actions);

            return div;
        }

        // ── Operaciones ───────────────────────────────────────────────────────

        function addToCol(id, nombre, colName) {
            const set = colName === 'encargados' ? encSet : accSet;
            if (set.has(id)) return;
            set.add(id);

            listEl(colName).appendChild(makeColCard(id, nombre, colName));
            updateCount(colName);
            refreshPuestosCard(id);
        }

        function removeFromCol(id, nombre, colName) {
            const set = colName === 'encargados' ? encSet : accSet;
            set.delete(id);

            const card = listEl(colName).querySelector(`.ma-puesto-card[data-id="${id}"]`);
            if (card) card.remove();
            updateCount(colName);
            refreshPuestosCard(id);
        }

        function bindPuestosCard(card) {
            card.querySelectorAll('button').forEach(btn => {
                const fresh = btn.cloneNode(true);
                btn.replaceWith(fresh);
                fresh.addEventListener('click', () => {
                    const id     = card.dataset.id;
                    const nombre = card.dataset.nombre;
                    if (fresh.classList.contains('ma-puesto-card-btn--enc')) {
                        addToCol(id, nombre, 'encargados');
                    } else if (fresh.classList.contains('ma-puesto-card-btn--acc')) {
                        addToCol(id, nombre, 'con_acceso');
                    }
                });
            });
        }

        // ── Inicializar desde el DOM renderizado por Twig ─────────────────────

        listEl('encargados').querySelectorAll('.ma-puesto-card').forEach(card => {
            encSet.add(card.dataset.id);
            const btn = card.querySelector('button');
            if (btn) btn.addEventListener('click', () => {
                removeFromCol(card.dataset.id, card.dataset.nombre, 'encargados');
            });
        });

        if (usaAcceso) {
            listEl('con_acceso').querySelectorAll('.ma-puesto-card').forEach(card => {
                accSet.add(card.dataset.id);
                const btn = card.querySelector('button');
                if (btn) btn.addEventListener('click', () => {
                    removeFromCol(card.dataset.id, card.dataset.nombre, 'con_acceso');
                });
            });
        }

        listEl('puestos').querySelectorAll('.ma-puesto-card').forEach(card => {
            bindPuestosCard(card);
        });

        // ── Botón "Todos" — agrega todos los puestos a Con acceso ────────────

        const todosBtn = root.querySelector('#ma-todos-btn');
        if (todosBtn) {
            todosBtn.addEventListener('click', () => {
                listEl('puestos').querySelectorAll('.ma-puesto-card').forEach(card => {
                    addToCol(card.dataset.id, card.dataset.nombre, 'con_acceso');
                });
            });
        }

        // ── Serializar al submit ──────────────────────────────────────────────

        const form = root.querySelector('#ma-edit-form');
        if (form) {
            form.addEventListener('submit', () => {
                const encInputs = root.querySelector('#ma-inputs-encargados');
                const accInputs = root.querySelector('#ma-inputs-con-acceso');
                encInputs.innerHTML = '';
                accInputs.innerHTML = '';

                encSet.forEach(id => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'encargados[]';
                    inp.value = id;
                    encInputs.appendChild(inp);
                });

                accSet.forEach(id => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'con_acceso[]';
                    inp.value = id;
                    accInputs.appendChild(inp);
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
