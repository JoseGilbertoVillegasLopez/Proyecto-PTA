/**
 * SOLICITUD DE GASTOS — NEW
 *
 * Aislado con: div[data-sg-view="sg-new"]
 * Cargado desde base.html.twig con defer.
 */

function bootSgNew(context) {
    var root = context.querySelector('div[data-sg-view="sg-new"]');
    if (!root) return;
    if (root.dataset.sgInit === '1') return;
    root.dataset.sgInit = '1';

    // ── DATEPICKER (Flatpickr con tema oscuro + español) ──────
    var dateEl = root.querySelector('#fecha_necesita');
    if (dateEl && window.flatpickr) {
        flatpickr(dateEl, {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            disableMobile: true,
            allowInput: false,
            locale: {
                firstDayOfWeek: 0,
                weekdays: {
                    shorthand: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
                    longhand:  ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                },
                months: {
                    shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    longhand:  ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                },
            },
        });
    }

    // ── DATOS JSON ────────────────────────────────────────────
    var partidas = [], procesosClave = [];
    try {
        partidas      = JSON.parse(root.dataset.partidas      || '[]');
        procesosClave = JSON.parse(root.dataset.procesosClave || '[]');
    } catch (e) {
        console.error('[sg-new] Error al parsear JSON:', e);
    }

    var partidasMap = {};
    partidas.forEach(function (p) { partidasMap[String(p.id)] = p; });

    var pcMap = {};
    procesosClave.forEach(function (pc) { pcMap[String(pc.id)] = pc; });

    // ── ELEMENTOS DEL DOM ────────────────────────────────────
    var tbody   = root.querySelector('#sg-detalle-tbody');
    var btnAdd  = root.querySelector('#btn-add-partida');
    var totalEl = root.querySelector('#sg-total-valor');

    if (!tbody || !btnAdd || !totalEl) {
        console.error('[sg-new] Faltan elementos del DOM.');
        return;
    }

    // ── UTILIDADES ────────────────────────────────────────────
    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Quita tildes, diéresis y convierte a minúsculas para comparación
    function normalizar(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '');
    }

    // ── SPAN CELLS (rowspan) ─────────────────────────────────
    var SPAN_IDS = ['sg-td-pe', 'sg-td-pc', 'sg-td-pei', 'sg-td-paig', 'sg-td-meta'];

    function actualizarRowspan() {
        var count = tbody.querySelectorAll('tr').length;
        SPAN_IDS.forEach(function (id) {
            var td = root.querySelector('#' + id);
            if (td) td.rowSpan = count;
        });
    }

    // ── AUTOCOMPLETAR capítulo y código desde la partida ─────
    function onPartidaSelect(tr, p) {
        var capEl  = tr.querySelector('.sg-partida-capitulo');
        var codeEl = tr.querySelector('.sg-partida-code');
        if (capEl)  capEl.textContent  = p ? p.capitulo : '—';
        if (codeEl) codeEl.textContent = p ? p.partida  : '—';
    }

    // ── COMBOBOX GENÉRICO ────────────────────────────────────
    //
    //  opts.datos        — array de objetos a mostrar
    //  opts.buscar(item, texto) — fn que devuelve true si item coincide
    //  opts.renderItem(item)    — fn que devuelve string HTML del <li>
    //  opts.onSelect(item)      — callback al seleccionar
    //
    function initCombobox(comboboxEl, opts) {
        var inputEl  = comboboxEl.querySelector('.sg-combobox__input');
        var hiddenEl = comboboxEl.querySelector('input[type="hidden"]');
        var listEl   = comboboxEl.querySelector('.sg-combobox__list');
        if (!inputEl || !hiddenEl || !listEl) return;

        var MAX_H = 240;

        // Posiciona el dropdown arriba o abajo según el espacio disponible
        function posicionar() {
            var rect       = inputEl.getBoundingClientRect();
            var spaceBelow = window.innerHeight - rect.bottom - 8;
            var spaceAbove = rect.top - 8;

            listEl.style.left  = rect.left + 'px';
            listEl.style.width = rect.width + 'px';

            if (spaceBelow >= MAX_H || spaceBelow >= spaceAbove) {
                listEl.style.top       = (rect.bottom + 4) + 'px';
                listEl.style.bottom    = 'auto';
                listEl.style.maxHeight = Math.min(MAX_H, Math.max(spaceBelow, 80)) + 'px';
            } else {
                listEl.style.top       = 'auto';
                listEl.style.bottom    = (window.innerHeight - rect.top + 4) + 'px';
                listEl.style.maxHeight = Math.min(MAX_H, Math.max(spaceAbove, 80)) + 'px';
            }
        }

        function renderLista(filtro) {
            var texto = normalizar(filtro || '').trim();
            var coincidencias;

            if (texto === '') {
                coincidencias = opts.datos.slice(0, 30);
            } else {
                coincidencias = opts.datos.filter(function (item) {
                    return opts.buscar(item, texto);
                }).slice(0, 30);
            }

            listEl.innerHTML = '';

            if (coincidencias.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'sg-combobox__empty';
                empty.textContent = 'Sin resultados';
                listEl.appendChild(empty);
            } else {
                coincidencias.forEach(function (item) {
                    var li = document.createElement('li');
                    li.className = 'sg-combobox__item';
                    li.innerHTML = opts.renderItem(item);

                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        seleccionar(item);
                    });
                    listEl.appendChild(li);
                });
            }

            posicionar();
            listEl.hidden = false;
        }

        function seleccionar(item) {
            hiddenEl.value = item.id;
            listEl.hidden  = true;
            opts.onSelect(item, inputEl, hiddenEl);
        }

        function cerrar() {
            listEl.hidden = true;
        }

        inputEl.addEventListener('input', function () {
            hiddenEl.value = '';
            renderLista(inputEl.value);
        });

        inputEl.addEventListener('focus', function () {
            renderLista(inputEl.value);
        });

        inputEl.addEventListener('blur', function () {
            setTimeout(cerrar, 200);
        });

        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { cerrar(); inputEl.blur(); }
        });

        function onScroll() {
            if (!listEl.hidden) posicionar();
        }
        document.addEventListener('scroll', onScroll, true);
    }

    // ── COMBOBOX: PROCESO CLAVE ──────────────────────────────
    var peiEl  = root.querySelector('#sg-pei');
    var paigEl = root.querySelector('#sg-paig');
    var metaEl = root.querySelector('#sg-meta');

    var pcComboboxEl = root.querySelector('.sg-pc-combobox');
    if (pcComboboxEl) {
        initCombobox(pcComboboxEl, {
            datos: procesosClave,
            buscar: function (pc, texto) {
                return normalizar(pc.nombre).includes(texto);
            },
            renderItem: function (pc) {
                return '<div class="sg-combobox__item-desc">' + escHtml(pc.nombre) + '</div>';
            },
            onSelect: function (pc, inputEl) {
                inputEl.value = pc.nombre;
                if (peiEl)  peiEl.textContent  = pc.pei  || '—';
                if (paigEl) paigEl.textContent = pc.paig || '—';
                if (metaEl) metaEl.textContent = pc.meta || '—';
            },
        });
    }

    // ── COMBOBOX: PARTIDAS PRESUPUESTALES ────────────────────
    function initPartidaCombobox(comboboxEl) {
        initCombobox(comboboxEl, {
            datos: partidas,
            buscar: function (p, texto) {
                return normalizar(p.descripcion).includes(texto) ||
                       normalizar(p.capitulo).includes(texto)    ||
                       normalizar(p.partida).includes(texto);
            },
            renderItem: function (p) {
                return '<div class="sg-combobox__item-desc">' + escHtml(p.descripcion) + '</div>' +
                       '<div class="sg-combobox__item-sub">'  + escHtml(p.capitulo) + ' · ' + escHtml(p.partida) + '</div>';
            },
            onSelect: function (p, inputEl) {
                inputEl.value = p.descripcion;
                var tr = comboboxEl.closest('tr');
                if (tr) onPartidaSelect(tr, p);
            },
        });
    }

    // Cablear primer combobox de partida (ya renderizado en Twig)
    var firstPartidaCombobox = tbody.querySelector('.sg-combobox:not(.sg-pc-combobox)');
    if (firstPartidaCombobox) initPartidaCombobox(firstPartidaCombobox);

    // ── TOTAL ─────────────────────────────────────────────────
    function recalcularTotal() {
        var total = 0;
        tbody.querySelectorAll('.sg-inp-monto').forEach(function (inp) {
            var v = parseFloat(inp.value || '0');
            if (!isNaN(v) && v > 0) total += v;
        });
        totalEl.textContent = '$ ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('sg-inp-monto')) recalcularTotal();
    });

    // ── RENOMBRAR índices ─────────────────────────────────────
    function renombrar() {
        var idx = 1;
        tbody.querySelectorAll('tr').forEach(function (tr) {
            var hiddenInp = tr.querySelector('.sg-sel-partida');
            var montoInp  = tr.querySelector('.sg-inp-monto');
            if (hiddenInp) hiddenInp.name = 'solicitud[partidas][' + idx + '][partida_id]';
            if (montoInp)  montoInp.name  = 'solicitud[partidas][' + idx + '][monto]';
            idx++;
        });
    }

    // ── AGREGAR FILA ──────────────────────────────────────────
    function agregarFila() {
        var rowCount = tbody.querySelectorAll('tr').length + 1;

        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="sg-td-capitulo">' +
                '<span class="sg-partida-capitulo sg-autofield">—</span>' +
            '</td>' +
            '<td class="sg-td-partida-code">' +
                '<span class="sg-partida-code sg-autofield">—</span>' +
            '</td>' +
            '<td class="sg-td-desc">' +
                '<div class="sg-combobox">' +
                    '<input type="text" class="sg-new__input sg-combobox__input"' +
                           ' placeholder="Buscar descripción..." autocomplete="off">' +
                    '<input type="hidden" class="sg-sel-partida"' +
                           ' name="solicitud[partidas][' + rowCount + '][partida_id]">' +
                    '<ul class="sg-combobox__list" hidden></ul>' +
                '</div>' +
            '</td>' +
            '<td class="sg-td-cantidad">' +
                '<input type="number" step="0.01" min="0.01"' +
                       ' class="sg-new__input sg-inp-monto"' +
                       ' name="solicitud[partidas][' + rowCount + '][monto]"' +
                       ' placeholder="0.00">' +
            '</td>' +
            '<td class="sg-td-action">' +
                '<button type="button" class="sg-partida-remove" title="Eliminar">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</td>';

        var newCombobox = tr.querySelector('.sg-combobox');
        if (newCombobox) initPartidaCombobox(newCombobox);

        tr.querySelector('.sg-partida-remove').addEventListener('click', function () {
            tr.remove();
            renombrar();
            actualizarRowspan();
            recalcularTotal();
        });

        tbody.appendChild(tr);
        actualizarRowspan();

        // Enfocar el nuevo combobox automáticamente
        var newInput = tr.querySelector('.sg-combobox__input');
        if (newInput) newInput.focus();
    }

    btnAdd.addEventListener('click', agregarFila);

    // ── EVIDENCIAS DE GASTO (adjuntar imagen/PDF, min 1 - max 7) ─
    var MIN_EVIDENCIAS = 1;
    var MAX_EVIDENCIAS = 7;

    var preview = root.querySelector('[data-sg-evidencias-preview]');
    var addEvidenciaBtn = root.querySelector('[data-sg-evidencias-add]');
    var form = root.querySelector('[data-sg-evidencias-form]');

    function getEvidenceCount() {
        return preview ? preview.querySelectorAll('.sg-evidencias__preview-item').length : 0;
    }

    function clearEvidenceError() {
        preview?.parentElement?.querySelector('.sg-evidencias__error')?.remove();
    }

    function showEvidenceError(message) {
        clearEvidenceError();
        if (!preview) return;
        var error = document.createElement('div');
        error.className = 'sg-evidencias__error';
        error.textContent = message;
        preview.insertAdjacentElement('afterend', error);
    }

    function buildEvidenceTile(file, input) {
        var item = document.createElement('div');
        item.className = 'sg-evidencias__preview-item';

        var url = URL.createObjectURL(file);

        if (file.type.startsWith('image/')) {
            var img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            item.appendChild(img);
        } else {
            var iframe = document.createElement('iframe');
            iframe.src = url + '#toolbar=0&navpanes=0';
            iframe.title = file.name;
            item.appendChild(iframe);
        }

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'sg-evidencias__preview-remove';
        removeButton.title = 'Quitar evidencia';
        removeButton.innerHTML = '<i class="bi bi-x-lg"></i>';
        removeButton.addEventListener('click', function () {
            input.remove();
            item.remove();
            clearEvidenceError();
            if (addEvidenciaBtn) addEvidenciaBtn.hidden = getEvidenceCount() >= MAX_EVIDENCIAS;
        });

        var name = document.createElement('span');
        name.textContent = file.name;

        item.appendChild(removeButton);
        item.appendChild(name);

        return item;
    }

    function openEvidencePicker() {
        var inputName = preview?.dataset.sgEvidenciasInputName;
        if (!preview || !inputName) return;

        if (getEvidenceCount() >= MAX_EVIDENCIAS) {
            showEvidenceError('Máximo ' + MAX_EVIDENCIAS + ' archivos de evidencia.');
            return;
        }

        var picker = document.createElement('input');
        picker.type = 'file';
        picker.name = inputName;
        picker.accept = 'image/*,application/pdf';
        picker.className = 'sg-evidencias__file-hidden';

        picker.addEventListener('change', function () {
            var file = picker.files && picker.files[0];

            if (!file) {
                picker.remove();
                return;
            }

            if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
                picker.remove();
                showEvidenceError('Solo se permiten imágenes o PDF.');
                return;
            }

            var tile = buildEvidenceTile(file, picker);
            preview.insertBefore(tile, addEvidenciaBtn);
            preview.appendChild(picker);
            clearEvidenceError();

            if (addEvidenciaBtn) addEvidenciaBtn.hidden = getEvidenceCount() >= MAX_EVIDENCIAS;
        });

        preview.appendChild(picker);
        picker.click();
    }

    if (addEvidenciaBtn) {
        addEvidenciaBtn.addEventListener('click', openEvidencePicker);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            var evidenceCount = getEvidenceCount();
            var documentosMarcados = root.querySelectorAll('input[name="solicitud[documentos_verificacion][]"]:checked').length;

            if (evidenceCount < MIN_EVIDENCIAS || evidenceCount > MAX_EVIDENCIAS) {
                event.preventDefault();
                showEvidenceError('Debes adjuntar entre ' + MIN_EVIDENCIAS + ' y ' + MAX_EVIDENCIAS + ' archivos de evidencia.');
                preview?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (documentosMarcados === 0) {
                event.preventDefault();
                var group = root.querySelector('.sg-checkbox-group');
                group?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Selecciona al menos un documento de verificación.');
            }
        });
    }
}

// ── INICIALIZACIÓN (mismo patrón que pta/new.js) ──────────────

document.addEventListener('turbo:frame-load', function (event) {
    var frame = event.target;
    if (!frame || frame.id !== 'content') return;
    bootSgNew(frame);
});

document.addEventListener('turbo:load', function () {
    bootSgNew(document);
});

document.addEventListener('DOMContentLoaded', function () {
    bootSgNew(document);
});
