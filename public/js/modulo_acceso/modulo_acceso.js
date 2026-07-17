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
        const usaCargoEncargado = root.dataset.usaCargoEncargado === '1';
        const cargosCatalogo = usaCargoEncargado ? JSON.parse(root.dataset.maCargosCatalogo || '{}') : {};

        // ── Estado independiente por columna ─────────────────────────────────
        const encSet = new Set();
        const accSet = new Set();

        // ── Cargo → combobox (botón + lista, sin buscador) ─────────────────────
        // Mismo patrón que pta/select_combobox.js: reemplaza el <select> nativo
        // para poder estilizar la lista desplegada. El <select> original queda
        // oculto pero sigue siendo el que se lee al hacer submit.
        function crearMaSelectCombobox(select) {
            if (!select || select.dataset.comboboxListo === '1') return;
            select.dataset.comboboxListo = '1';

            const wrapper = document.createElement('div');
            wrapper.className = 'ma-select-combobox';
            select.insertAdjacentElement('beforebegin', wrapper);
            wrapper.appendChild(select);
            select.hidden = true;

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'ma-select-combobox__trigger';

            const textoSpan = document.createElement('span');
            textoSpan.className = 'ma-select-combobox__trigger-text';
            trigger.appendChild(textoSpan);

            const flecha = document.createElement('i');
            flecha.className = 'bi bi-chevron-down ma-select-combobox__trigger-arrow';
            trigger.appendChild(flecha);

            wrapper.appendChild(trigger);

            // La lista se agrega a document.body (no a wrapper): .ma-puesto-card
            // tiene transform en :hover, y cualquier transform en un ancestro
            // rompe position:fixed en sus descendientes (queda relativo a ese
            // ancestro en vez del viewport). onClickFuera revisa wrapper Y lista
            // por separado ya que dejan de estar anidados en el DOM.
            const lista = document.createElement('ul');
            lista.className = 'ma-select-combobox__list';
            lista.hidden = true;
            document.body.appendChild(lista);

            function sincronizarTexto() {
                const seleccionada = select.options[select.selectedIndex];
                textoSpan.textContent = seleccionada ? seleccionada.text : '';
            }

            function posicionarLista() {
                const rect = trigger.getBoundingClientRect();
                const espacioAbajo = window.innerHeight - rect.bottom;
                const abrirArriba = espacioAbajo < 240 && rect.top > espacioAbajo;

                lista.style.left = rect.left + 'px';
                lista.style.width = Math.max(rect.width, 120) + 'px';

                if (abrirArriba) {
                    lista.style.top = 'auto';
                    lista.style.bottom = (window.innerHeight - rect.top) + 'px';
                } else {
                    lista.style.bottom = 'auto';
                    lista.style.top = rect.bottom + 'px';
                }
            }

            function cerrarLista() {
                lista.hidden = true;
                trigger.classList.remove('ma-select-combobox__trigger--open');
                document.removeEventListener('mousedown', onClickFuera);
            }

            function onClickFuera(event) {
                if (!wrapper.contains(event.target) && !lista.contains(event.target)) cerrarLista();
            }

            function renderizarLista() {
                lista.innerHTML = '';

                Array.from(select.options).forEach((opcion) => {
                    const item = document.createElement('li');
                    item.className = 'ma-select-combobox__item';
                    if (opcion.selected) item.classList.add('ma-select-combobox__item--selected');
                    item.textContent = opcion.text;

                    item.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        select.value = opcion.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        cerrarLista();
                    });

                    lista.appendChild(item);
                });

                posicionarLista();
            }

            function abrirLista() {
                renderizarLista();
                lista.hidden = false;
                trigger.classList.add('ma-select-combobox__trigger--open');
                document.addEventListener('mousedown', onClickFuera);
            }

            trigger.addEventListener('click', () => {
                if (lista.hidden) {
                    abrirLista();
                } else {
                    cerrarLista();
                }
            });

            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') cerrarLista();
            });

            select.addEventListener('change', sincronizarTexto);

            function onScrollOResize() {
                if (!lista.hidden) posicionarLista();
            }

            document.addEventListener('scroll', onScrollOResize, true);
            window.addEventListener('resize', onScrollOResize);

            sincronizarTexto();
        }

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

            div.innerHTML = `<span class="ma-puesto-card-nombre">${nombre}</span>`;

            if (colName === 'encargados' && usaCargoEncargado) {
                const select = document.createElement('select');
                select.className = 'ma-puesto-card-cargo';
                select.title = 'Cargo en la revisión';
                select.innerHTML = '<option value="">Sin cargo</option>' +
                    Object.entries(cargosCatalogo).map(([valor, label]) => `<option value="${valor}">${label}</option>`).join('');
                div.appendChild(select);
                crearMaSelectCombobox(select);
            }

            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ma-puesto-card-btn ma-puesto-card-btn--remove';
            btn.title     = colName === 'encargados' ? 'Quitar de Encargados' : 'Quitar de Con acceso';
            btn.innerHTML = '<i class="bi bi-x-lg"></i>';
            btn.addEventListener('click', () => removeFromCol(id, nombre, colName));

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
            const btn = card.querySelector('.ma-puesto-card-btn--remove');
            if (btn) btn.addEventListener('click', () => {
                removeFromCol(card.dataset.id, card.dataset.nombre, 'encargados');
            });
            const cargoSelect = card.querySelector('.ma-puesto-card-cargo');
            if (cargoSelect) crearMaSelectCombobox(cargoSelect);
        });

        if (usaAcceso) {
            listEl('con_acceso').querySelectorAll('.ma-puesto-card').forEach(card => {
                accSet.add(card.dataset.id);
                const btn = card.querySelector('.ma-puesto-card-btn--remove');
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

                    if (usaCargoEncargado) {
                        const card = listEl('encargados').querySelector(`.ma-puesto-card[data-id="${id}"]`);
                        const select = card ? card.querySelector('.ma-puesto-card-cargo') : null;
                        if (select && select.value) {
                            const cargoInp = document.createElement('input');
                            cargoInp.type  = 'hidden';
                            cargoInp.name  = `cargos[${id}]`;
                            cargoInp.value = select.value;
                            encInputs.appendChild(cargoInp);
                        }
                    }
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
