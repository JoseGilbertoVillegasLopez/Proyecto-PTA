/**
 * =====================================================
 * PTA — VISTA NEW
 * Script principal para la creación de un PTA
 *
 * FUNCIONALIDAD GENERAL:
 *  - Manejo dinámico de Indicadores (CollectionType Symfony)
 *  - Manejo dinámico de Acciones (CollectionType Symfony)
 *  - Sincronización lógica entre Indicadores ↔ Acciones
 *  - Validaciones de negocio antes del submit del formulario
 *
 * NOTAS IMPORTANTES:
 *  - Se basa en CollectionType + prototype (Symfony)
 *  - No se renderizan filas iniciales (todo es dinámico)
 *  - El submit final lo maneja Symfony (NO AJAX)
 * =====================================================
 */

/**
 * Evento disparado por Turbo cada vez que
 * un <turbo-frame> termina de cargarse.
 *
 * IMPORTANTE:
 *  - Este JS vive dentro de un dashboard
 *  - Por eso se ejecuta solo cuando el frame correcto se carga
 */

/**
 * =====================================================
 * BOOTSTRAP UNIVERSAL PTA NEW
 * -----------------------------------------------------
 * Permite que el mismo JS funcione:
 *  - Dentro de Turbo (admin)
 *  - En carga normal (no-admin)
 * =====================================================
 */
function bootPtaNew(context) {
    const ptaForm = context.querySelector('form[data-pta-form="pta-new"]');
    if (!ptaForm) return;

    // 🛑 PROTECCIÓN: evitar doble inicialización
    if (ptaForm.dataset.ptaInitialized === "true") return;

    ptaForm.dataset.ptaInitialized = "true";

    initPtaNew(context, ptaForm);
}


/**
 * =====================================================
 * EVENTOS UNIVERSALES
 * -----------------------------------------------------
 * - Admin: turbo:frame-load (frame #content)
 * - No-admin con Turbo Drive: turbo:load
 * - No-admin sin Turbo: DOMContentLoaded
 * =====================================================
 */

// ✅ Admin (dashboard): cuando se carga el frame content
document.addEventListener("turbo:frame-load", (event) => {
    const frame = event.target;
    if (!frame || frame.id !== "content") return;
    bootPtaNew(frame);
});

// ✅ No-admin con Turbo Drive (navegación sin recargar página)
document.addEventListener("turbo:load", () => {
    bootPtaNew(document);
});

// ✅ Fallback (si algún día Turbo no está activo)
document.addEventListener("DOMContentLoaded", () => {
    bootPtaNew(document);
});




function initPtaNew(frame, ptaForm) {
    console.log("PTA NEW JS cargado ✔");

    /**
 * =====================================================
 * SOLO DÍGITOS — INPUTS NUMÉRICOS (PTA)
 * =====================================================
 */
function enforceOnlyDigits(input) {
    if (!input) return;

    // Teclado numérico en móviles
    input.setAttribute("inputmode", "numeric");
    input.setAttribute("pattern", "[0-9]*");

    input.addEventListener("input", () => {
        input.value = input.value.replace(/\D+/g, "");
    });
}


/**
     * =====================================================
     * BUSCADOR GENÉRICO DE PERSONAL
     * -----------------------------------------------------
     * Se reutiliza para:
     *  - Supervisor del Proyecto
     *  - Aval del Proyecto
     *
     * FUNCIONAMIENTO:
     *  - Input visible para búsqueda
     *  - Input hidden para guardar el ID real
     *  - Resultados dinámicos desde API
     * =====================================================
     */
    function initPersonalSearch({
        inputSelector,
        hiddenSelector,
        resultsSelector
    }) {

        // Input visible donde el usuario escribe
        const input = frame.querySelector(inputSelector);

        // Input hidden donde se guarda el ID real del Personal
        const hidden = frame.querySelector(hiddenSelector);

        // Contenedor visual de resultados
        const results = frame.querySelector(resultsSelector);

        // Si falta algún elemento, se aborta la inicialización
        if (!input || !hidden || !results) return;

        // AbortController para cancelar búsquedas anteriores
        let controller = null;

        /**
         * Evento input:
         *  - Se dispara en cada escritura del usuario
         */
        input.addEventListener("input", () => {

            // Texto ingresado por el usuario
            const q = input.value.trim();

            // Limpiar el hidden para evitar IDs inválidos
            hidden.value = "";

            // Limpiar resultados anteriores
            results.innerHTML = "";

            // No buscar si hay menos de 2 caracteres
            if (q.length < 2) return;

            // Cancelar request anterior si existe
            if (controller) controller.abort();

            // Crear nuevo controlador para esta búsqueda
            controller = new AbortController();

            /**
             * Petición a la API de personal
             * Devuelve [{ id, nombre }]
             */
            fetch(`/api/personal/buscar?q=${encodeURIComponent(q)}`, {
                signal: controller.signal
            })
                .then(res => res.json())
                .then(data => {

                    // Limpiar resultados antes de renderizar
                    results.innerHTML = "";

                    // Renderizar cada resultado
                    data.forEach(p => {
                        const item = document.createElement("div");
                        item.classList.add("search-item");
                        item.textContent = p.nombre;

                        /**
                         * Al hacer click:
                         *  - Se muestra el nombre en el input visible
                         *  - Se guarda el ID real en el hidden
                         */
                        item.addEventListener("click", () => {
                            input.value = p.nombre;
                            hidden.value = p.id;
                            results.innerHTML = "";
                        });

                        results.appendChild(item);
                    });
                })
                // Ignorar errores silenciosamente (cancelaciones)
                .catch(() => {});
        });

        /**
         * Cierre automático del dropdown de resultados
         * cuando el usuario hace click fuera
         */
        frame.addEventListener("click", (e) => {
            if (!results.contains(e.target) && e.target !== input) {
                results.innerHTML = "";
            }
        });
    }

    
    // =====================================================
    // MODAL COMPARTIDO — MODO DE CAPTURA MENSUAL
    // -----------------------------------------------------
    // El modal es único para toda la vista. Cuando se abre,
    // recibe la referencia al hidden field del indicador
    // activo mediante modalEl._hiddenCapturaPct.
    // =====================================================
    initModalCapturaPct(frame);

    // Inicialización del buscador de Supervisor
    initPersonalSearch({
        inputSelector: ".supervisor-search",
        hiddenSelector: 'input[name$="[supervisor]"]',
        resultsSelector: ".supervisor-results"
    });

    // Inicialización del buscador de Aval
    initPersonalSearch({
        inputSelector: ".aval-search",
        hiddenSelector: 'input[name$="[aval]"]',
        resultsSelector: ".aval-results"
    });
    // Inicialización del buscador de Responsable del PTA
initPersonalSearch({
    inputSelector: ".responsable-search",
    hiddenSelector: 'input[name="responsable_id"]',
    resultsSelector: ".responsable-results"
});


    /**
     * =====================================================
     * ÍNDICE LÓGICO GLOBAL DE INDICADORES
     * -----------------------------------------------------
     * NO es ID de base de datos.
     * Se usa únicamente para:
     *  - Relacionar indicadores ↔ acciones en frontend
     * =====================================================
     */
    let indicadorIndiceGlobal = 1;

    /**
     * =====================================================
     * SINCRONIZACIÓN INDICADORES ↔ ACCIONES
     * -----------------------------------------------------
     * 1. Recolecta indicadores válidos
     * 2. Actualiza selects visibles de acciones
     * 3. Limpia selecciones inválidas
     * =====================================================
     */
    function syncIndicadoresConAcciones(frame) {

        // Array temporal de indicadores existentes
        const indicadores = [];

        /**
         * Recorremos todas las filas de indicadores
         * para extraer:
         *  - nombre del indicador
         *  - índice lógico
         */
        frame.querySelectorAll(".indicator-row").forEach(row => {

            const nombreInput = row.querySelector('[name$="[indicador]"]');
            const indiceInput = row.querySelector('[name$="[indice]"]');

            // Si falta algo, ignorar fila
            if (!nombreInput || !indiceInput) return;

            // Si el nombre está vacío, no es válido
            if (nombreInput.value.trim() === "") return;

            indicadores.push({
                indice: indiceInput.value,
                nombre: nombreInput.value
            });
        });

        /**
         * Recorremos todas las acciones
         * para reconstruir su select de indicadores
         */
        frame.querySelectorAll(".accion-row").forEach(row => {

            const select = row.querySelector(".ac-select");

            // Hidden real que se enviará al backend
            const hidden = row.querySelector(
                'input[type="hidden"][name$="[indicador]"]'
            );

            if (!select || !hidden) return;

            // Valor previamente seleccionado
            const valorActual = hidden.value;

            // Reset del select
            select.innerHTML = `<option value="">— Seleccione un indicador —</option>`;

            let sigueExistiendo = false;

            // Poblar select con indicadores válidos
            indicadores.forEach(ind => {
                const option = document.createElement("option");
                option.value = ind.indice;
                option.textContent = ind.nombre;

                // Mantener selección si sigue existiendo
                if (ind.indice === valorActual) {
                    option.selected = true;
                    sigueExistiendo = true;
                }

                select.appendChild(option);
            });

            // Si el indicador ya no existe, limpiar selección
            if (!sigueExistiendo) {
                hidden.value = "";
                select.value = "";
            }
        });

        console.log("🔄 Indicadores sincronizados con acciones");
    }


    /**
     * =====================================================
     * UTILIDAD: BOTONES DE ELIMINAR
     * -----------------------------------------------------
     * Activa botones para eliminar filas dinámicas
     * y re-sincronizar indicadores
     * =====================================================
     */
    function activateRemoveButtons(root, selector) {
        root.querySelectorAll(selector).forEach(btn => {
            btn.onclick = () => {
                btn.closest("tr, .indicator-card, .accion-card").remove();
                syncIndicadoresConAcciones(root);
            };
        });
    }

    /**
     * =====================================================
     * MANEJO DE INDICADORES
     * =====================================================
     */
    const addIndicadorBtn = frame.querySelector("#add-indicador");
    const indicadoresHolder = frame.querySelector(
        "[data-collection-holder='indicadores']"
    );

    if (addIndicadorBtn && indicadoresHolder) {

        // Inicializar índice del CollectionType
        indicadoresHolder.dataset.index =
            indicadoresHolder.querySelectorAll(".indicator-card").length;

        /**
         * Cuando se escribe en el nombre del indicador,
         * se actualizan los selects de acciones en tiempo real
         */
        indicadoresHolder.addEventListener("input", (e) => {
            if (e.target && e.target.matches('[name$="[indicador]"]')) {
                syncIndicadoresConAcciones(frame);
            }
        });

        /**
         * Agregar nuevo indicador dinámicamente
         */
        addIndicadorBtn.addEventListener("click", () => {

            const index = indicadoresHolder.dataset.index;
            const prototype = indicadoresHolder.dataset.prototype;

            // Crear HTML desde prototype Symfony
            const temp = document.createElement("div");
            temp.innerHTML = prototype.replace(/__name__/g, index);

            // ===============================
            // SOLO DÍGITOS — BASE Y META
            // ===============================
            const valorBaseInput = temp.querySelector('[name$="[valorBase]"]');
            const metaInput = temp.querySelector('[name$="[valor]"]');

            if (valorBaseInput) enforceOnlyDigits(valorBaseInput);
            if (metaInput) enforceOnlyDigits(metaInput);

            // Índice lógico del indicador
            const indiceInput = temp.querySelector('[name$="[indice]"]');
            if (indiceInput) {
                indiceInput.value = indicadorIndiceGlobal;
                indicadorIndiceGlobal++;
            }

            // esPorcentaje: convertir checkbox → hidden
            const esPorcentajeCheck = temp.querySelector('[name$="[esPorcentaje]"]');
            if (esPorcentajeCheck) {
                esPorcentajeCheck.type = 'hidden';
                esPorcentajeCheck.value = '0';
            }

            // capturaEnPorcentaje: convertir checkbox → hidden.
            // Se inicializa VACÍO ('') para detectar si el usuario
            // nunca pasó por el modal de selección. Solo se asigna
            // '0' o '1' al confirmar en el modal.
            const capturaPctCheck = temp.querySelector('[name$="[capturaEnPorcentaje]"]');
            if (capturaPctCheck) {
                capturaPctCheck.type = 'hidden';
                capturaPctCheck.value = ''; // vacío = no elegido todavía
            }

            // Número secuencial visible del card
            const cardNum = indicadoresHolder.querySelectorAll(".indicator-card").length + 1;

            // Card contenedor
            const card = document.createElement("div");
            card.classList.add("indicator-card", "indicator-row");

            card.innerHTML = `
                <div class="ic-header">
                    <span class="ic-num">
                        <i class="bi bi-graph-up"></i>
                        <span class="ic-num-text">Indicador <strong>#${cardNum}</strong></span>
                    </span>
                    <button type="button" class="ic-remove-btn remove-indicador" title="Eliminar indicador">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>

                <div class="ic-body">

                    <div class="ic-row-main">
                        <div class="ic-field ic-field--desc">
                            <label class="ic-label">
                                <i class="bi bi-card-text"></i> Indicador
                            </label>
                            ${temp.querySelector('[name$="[indicador]"]').outerHTML}
                            ${indiceInput ? indiceInput.outerHTML : ''}
                        </div>
                        <div class="ic-field ic-field--formula">
                            <label class="ic-label">
                                <i class="bi bi-calculator"></i> Fórmula de Cálculo
                            </label>
                            ${temp.querySelector('[name$="[formula]"]').outerHTML}
                        </div>
                    </div>

                    <div class="ic-row-meta">
                        <div class="ic-field ic-field--base">
                            <label class="ic-label">
                                <i class="bi bi-bar-chart-line"></i> Valor Base
                            </label>
                            ${temp.querySelector('[name$="[valorBase]"]').outerHTML}
                        </div>
                        <div class="ic-field ic-field--meta">
                            <label class="ic-label">
                                <i class="bi bi-bullseye"></i> Meta
                            </label>
                            <div class="meta-input-wrap">
                                ${temp.querySelector('[name$="[valor]"]').outerHTML}
                                ${esPorcentajeCheck ? esPorcentajeCheck.outerHTML : ''}
                                ${capturaPctCheck   ? capturaPctCheck.outerHTML   : ''}
                                <div class="tipo-toggle btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn tipo-btn tipo-abs active" title="Valor absoluto">#</button>
                                    <button type="button" class="btn tipo-btn tipo-pct" title="Porcentaje">%</button>
                                </div>
                            </div>
                        </div>

                        <!-- Campo "Tipo de captura" — aparece al lado de Meta cuando esPorcentaje=true -->
                        <div class="ic-field ic-field--captura">
                            <label class="ic-label">
                                <i class="bi bi-pencil-square"></i> Captura
                            </label>
                            <div class="captura-modo-badge"></div>
                        </div>
                        <div class="ic-field ic-field--periodo">
                            <label class="ic-label">
                                <i class="bi bi-calendar3"></i> Periodo
                            </label>
                            ${temp.querySelector('[name$="[periodo]"]').outerHTML}
                            <div class="ic-periodo-static">
                                <i class="bi bi-check2-circle"></i> Anual
                            </div>
                        </div>
                        <div class="ic-field ic-field--tendencia">
                            <label class="ic-label">
                                <i class="bi bi-arrow-up-right-circle"></i> Tendencia
                            </label>
                            ${temp.querySelector('[name$="[tendencia]"]').outerHTML}
                        </div>
                    </div>

                </div>
            `;

            // Toggle # / % (esPorcentaje)
            const hiddenPct       = card.querySelector('[name$="[esPorcentaje]"]');
            const hiddenCapturaPct = card.querySelector('[name$="[capturaEnPorcentaje]"]');
            const btnAbs          = card.querySelector('.tipo-abs');
            const btnPct          = card.querySelector('.tipo-pct');
            const modoBadge       = card.querySelector('.captura-modo-badge');

            // Referencia a la columna ic-field--captura (está en el mismo ic-row-meta)
            const capturaField = card.querySelector('.ic-field--captura');

            /**
             * Muestra u oculta la columna "Captura" en ic-row-meta,
             * y actualiza el badge con el modo elegido.
             * La columna solo es visible cuando esPorcentaje=true.
             */
            function actualizarModoBadge() {
                if (!modoBadge || !hiddenCapturaPct) return;

                const esPct = hiddenPct?.value === '1';

                // Mostrar/ocultar la columna completa
                if (capturaField) {
                    capturaField.style.display = esPct ? 'block' : 'none';
                }

                if (!esPct) return;

                // Actualizar el contenido del badge
                if (hiddenCapturaPct.value === '1') {
                    modoBadge.innerHTML = `
                        <span class="captura-badge captura-badge--pct">
                            <i class="bi bi-percent"></i> Porcentaje %
                        </span>`;
                } else if (hiddenCapturaPct.value === '0') {
                    modoBadge.innerHTML = `
                        <span class="captura-badge captura-badge--abs">
                            <i class="bi bi-123"></i> Absoluto
                        </span>`;
                } else {
                    // Aún no elegido — mostrar indicación
                    modoBadge.innerHTML = `
                        <span class="captura-badge" style="opacity:.5;border-style:dashed;">
                            <i class="bi bi-question-circle"></i> Sin elegir
                        </span>`;
                }
            }

            /**
             * Abre el modal compartido de captura y asocia su
             * confirmación al hidden field de este card específico.
             */
            function abrirModalCaptura() {
                const modalEl = document.getElementById('modalCapturaPct');
                if (!modalEl) return;

                // Guardar referencia al campo de este card
                modalEl._hiddenCapturaPct = hiddenCapturaPct;
                modalEl._actualizarBadge  = actualizarModoBadge;

                // Marcar el botón activo según el estado actual
                const btnModalAbs = modalEl.querySelector('#modalCapturaBtnAbs');
                const btnModalPct = modalEl.querySelector('#modalCapturaBtnPct');
                const esActualPct = hiddenCapturaPct?.value === '1';

                btnModalAbs?.classList.toggle('active', !esActualPct);
                btnModalPct?.classList.toggle('active',  esActualPct);

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            if (hiddenPct && btnAbs && btnPct) {

                btnAbs.addEventListener('click', () => {
                    hiddenPct.value = '0';
                    btnAbs.classList.add('active');
                    btnPct.classList.remove('active');

                    // Al volver a absoluto, resetear capturaEnPorcentaje a vacío
                    // (se restablece como si nunca hubiera elegido, sin modal)
                    if (hiddenCapturaPct) hiddenCapturaPct.value = '';
                    actualizarModoBadge();
                });

                btnPct.addEventListener('click', () => {
                    hiddenPct.value = '1';
                    btnPct.classList.add('active');
                    btnAbs.classList.remove('active');

                    // Al activar %, abrir el modal para preguntar el modo de captura
                    abrirModalCaptura();
                });
            }

            // Inicializar badge (para cuando se edita un PTA existente con datos ya guardados)
            actualizarModoBadge();

            // Re-aplicar enforceOnlyDigits sobre los elementos reales del card
            const cardValorBase = card.querySelector('[name$="[valorBase]"]');
            const cardMeta = card.querySelector('[name$="[valor]"]:not([type="hidden"])');
            if (cardValorBase) enforceOnlyDigits(cardValorBase);
            if (cardMeta) enforceOnlyDigits(cardMeta);

            // Actualizar encabezado del card en tiempo real
            const indicadorTextarea = card.querySelector('[name$="[indicador]"]');
            const icNumText = card.querySelector('.ic-num-text');
            if (indicadorTextarea && icNumText) {
                indicadorTextarea.addEventListener('input', () => {
                    const val = indicadorTextarea.value.trim();
                    icNumText.innerHTML = val
                        ? `Indicador: <strong>${val}</strong>`
                        : `Indicador <strong>#${cardNum}</strong>`;
                });
            }

            indicadoresHolder.appendChild(card);
            indicadoresHolder.dataset.index++;

            activateRemoveButtons(frame, ".remove-indicador");
            syncIndicadoresConAcciones(frame);
        });

        activateRemoveButtons(frame, ".remove-indicador");
    }


    /**
     * =====================================================
     * MANEJO DE ACCIONES
     * =====================================================
     */
    const addAccionBtn = frame.querySelector("#add-accion");
    const accionesHolder = frame.querySelector(
        "[data-collection-holder='acciones']"
    );

    if (addAccionBtn && accionesHolder) {

        accionesHolder.dataset.index =
            accionesHolder.querySelectorAll(".accion-card").length;

        addAccionBtn.addEventListener("click", () => {

            const index = accionesHolder.dataset.index;
            const prototype = accionesHolder.dataset.prototype;

            const temp = document.createElement("div");
            temp.innerHTML = prototype.replace(/__name__/g, index);

            const accionInput  = temp.querySelector('[name$="[accion]"]');
            const mesesInput   = temp.querySelectorAll('[type="checkbox"]');
            const indicadorHidden = temp.querySelector('[name$="[indicador]"]');

            const cardNum = accionesHolder.querySelectorAll(".accion-card").length + 1;

            // Select visual de indicador
            const indicadorSelect = document.createElement("select");
            indicadorSelect.classList.add("ac-select");
            indicadorSelect.innerHTML = `<option value="">— Seleccione un indicador —</option>`;

            frame.querySelectorAll(".indicator-row").forEach(ind => {
                const nombreInput = ind.querySelector('[name$="[indicador]"]');
                const indiceInput = ind.querySelector('[name$="[indice]"]');
                if (!nombreInput || !indiceInput || nombreInput.value.trim() === "") return;
                const opt = document.createElement("option");
                opt.value = indiceInput.value;
                opt.textContent = nombreInput.value;
                indicadorSelect.appendChild(opt);
            });

            indicadorSelect.addEventListener("change", () => {
                indicadorHidden.value = indicadorSelect.value;
            });

            // Card
            const card = document.createElement("div");
            card.classList.add("accion-card", "accion-row");

            card.innerHTML = `
                <div class="ac-header">
                    <span class="ac-num">
                        <i class="bi bi-list-check"></i>
                        <span class="ac-num-text">Acción <strong>#${cardNum}</strong></span>
                    </span>
                    <button type="button" class="ic-remove-btn remove-accion" title="Eliminar acción">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
                <div class="ac-body">
                    <div class="ac-field ac-field--indicador">
                        <label class="ic-label">
                            <i class="bi bi-graph-up"></i> Indicador asociado
                        </label>
                    </div>
                    <div class="ac-field ac-field--accion">
                        <label class="ic-label">
                            <i class="bi bi-pencil"></i> Descripción de la Acción
                        </label>
                        ${accionInput.outerHTML}
                    </div>
                    <div class="ac-field ac-field--meses">
                        <label class="ic-label">
                            <i class="bi bi-calendar3-range"></i> Meses de Ejecución
                        </label>
                        <div class="ac-meses-grid"></div>
                    </div>
                </div>
            `;

            // Insertar select + hidden en el campo indicador
            const indicadorField = card.querySelector(".ac-field--indicador");
            indicadorField.appendChild(indicadorSelect);
            indicadorField.appendChild(indicadorHidden);

            // Pills de meses
            const mesesGrid = card.querySelector(".ac-meses-grid");
            const nombresMeses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

            mesesInput.forEach((mes, i) => {
                const pill = document.createElement("label");
                pill.classList.add("ac-mes-pill");

                mes.style.position  = "absolute";
                mes.style.opacity   = "0";
                mes.style.width     = "0";
                mes.style.height    = "0";
                mes.style.pointerEvents = "none";

                const span = document.createElement("span");
                span.textContent = nombresMeses[i];

                pill.appendChild(mes);
                pill.appendChild(span);

                mes.addEventListener("change", () => {
                    pill.classList.toggle("ac-mes-pill--active", mes.checked);
                });

                mesesGrid.appendChild(pill);
            });

            // Actualizar encabezado en tiempo real
            const accionTextarea = card.querySelector('[name$="[accion]"]');
            const acNumText = card.querySelector('.ac-num-text');
            if (accionTextarea && acNumText) {
                accionTextarea.addEventListener('input', () => {
                    const val = accionTextarea.value.trim();
                    acNumText.innerHTML = val
                        ? `Acción: <strong>${val}</strong>`
                        : `Acción <strong>#${cardNum}</strong>`;
                });
            }

            accionesHolder.appendChild(card);
            accionesHolder.dataset.index++;

            activateRemoveButtons(frame, ".remove-accion");
            syncIndicadoresConAcciones(frame);
        });

        activateRemoveButtons(frame, ".remove-accion");
    }



    /**
     * =====================================================
     * LIMPIEZA VISUAL DE ERRORES
     * =====================================================
     */
    function limpiarErroresVisuales(frame) {
        frame.querySelectorAll(".field-error").forEach(el => {
            el.classList.remove("field-error");
        });
    }


    /**
     * =====================================================
     * VALIDACIÓN FINAL ANTES DEL SUBMIT
     * =====================================================
     */

    if (ptaForm) {
        ptaForm.addEventListener("submit", (e) => {

            // ===============================
            // VALIDACIÓN: capturaEnPorcentaje
            // --------------------------------
            // Si algún indicador tiene esPorcentaje='1' pero
            // capturaEnPorcentaje='' (el usuario cerró el modal
            // sin elegir), bloquear el submit y abrir el modal
            // de ese indicador. Al confirmar, se auto-resubmit.
            // ===============================
            const modalEl = document.getElementById('modalCapturaPct');

            const indicadorSinCaptura = frame.querySelector(
                'input[name$="[esPorcentaje]"][value="1"]'
            ) && (() => {
                // Buscar el primer indicador con esPorcentaje='1' y capturaEnPorcentaje=''
                let encontrado = null;
                frame.querySelectorAll('.indicator-row').forEach(row => {
                    if (encontrado) return;
                    const hPct = row.querySelector('[name$="[esPorcentaje]"]');
                    const hCap = row.querySelector('[name$="[capturaEnPorcentaje]"]');
                    if (hPct?.value === '1' && hCap?.value === '') {
                        encontrado = { hCap, row };
                    }
                });
                return encontrado;
            })();

            if (indicadorSinCaptura && modalEl) {
                e.preventDefault();
                e.stopImmediatePropagation();

                // Obtener el badge updater de ese card
                const card = indicadorSinCaptura.row;
                const badge = card.querySelector('.captura-modo-badge');

                // Pasar la referencia al modal + flag de auto-submit
                modalEl._hiddenCapturaPct = indicadorSinCaptura.hCap;
                modalEl._actualizarBadge  = badge
                    ? () => {
                        // Actualizar el badge del card cuando confirme
                        badge.style.display = 'block';
                        badge.innerHTML = indicadorSinCaptura.hCap.value === '1'
                            ? `<span class="captura-badge captura-badge--pct"><i class="bi bi-percent"></i> Captura mensual: porcentaje (0-100%)</span>`
                            : `<span class="captura-badge captura-badge--abs"><i class="bi bi-123"></i> Captura mensual: valor absoluto</span>`;
                      }
                    : null;
                modalEl._pendienteSubmit  = true;
                modalEl._form             = ptaForm;

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                return;
            }

            // ===============================
            // VALIDACIÓN DE RESPONSABLES
            // ===============================
            const supervisorHidden = frame.querySelector('input[name$="[supervisor]"]');
            const avalHidden = frame.querySelector('input[name$="[aval]"]');

            let erroresResponsables = [];

            if (!supervisorHidden || supervisorHidden.value === "") {
                erroresResponsables.push("Supervisor del Proyecto");
            }

            if (!avalHidden || avalHidden.value === "") {
                erroresResponsables.push("Aval del Proyecto");
            }

            if (erroresResponsables.length > 0) {
                e.preventDefault();
                e.stopImmediatePropagation(); // 🔥 clave con Turbo

                const lista = document.getElementById("errores-lista");
                lista.innerHTML = "";

                erroresResponsables.forEach(r => {
                    const li = document.createElement("li");
                    li.classList.add("list-group-item", "bg-dark", "text-light");
                    li.innerHTML = `<strong>Responsables:</strong> ${r}`;
                    lista.appendChild(li);
                });

                new bootstrap.Modal(
                    document.getElementById("erroresModal")
                ).show();

                return;
            }

            limpiarErroresVisuales(frame);

            let primerCampoConError = null;

            const indicadoresRows = frame.querySelectorAll(".indicator-row");
            const accionesRows = frame.querySelectorAll(".accion-row");

            let errores = [];

            // Regla de negocio: no todo vacío
            if (indicadoresRows.length === 0 && accionesRows.length === 0) {
                errores.push({
                    accion: "General",
                    errores: ["no hay indicadores ni acciones"]
                });
            }



            // Regla: indicadores requieren acciones
            if (indicadoresRows.length > 0 && accionesRows.length === 0) {
                errores.push({
                    accion: "General",
                    errores: ["hay indicadores pero no hay acciones"]
                });
            }

            /**
             * Validación por acción
             */
            accionesRows.forEach((row, index) => {
                const accionInput = row.querySelector('[name$="[accion]"]');
                const indicadorHidden = row.querySelector('[name$="[indicador]"]');
                const mesesChecks = row.querySelectorAll('input[type="checkbox"]:checked');

                let erroresAccion = [];

                if (!accionInput || accionInput.value.trim() === "") {
                    erroresAccion.push("sin acción");
                    if (accionInput) {
                        accionInput.classList.add("field-error");
                        if (!primerCampoConError) primerCampoConError = accionInput;
                    }
                }

                if (!indicadorHidden || indicadorHidden.value === "") {
                    erroresAccion.push("sin indicador");
                    const selectVisible = row.querySelector(".ac-select");
                    if (selectVisible) {
                        selectVisible.classList.add("field-error");
                        if (!primerCampoConError) primerCampoConError = selectVisible;
                    }
                }

                if (mesesChecks.length === 0) {
                    erroresAccion.push("sin meses");
                    const mesesCol = row.querySelector(".meses-col");
                    if (mesesCol) {
                        mesesCol.classList.add("field-error");
                        if (!primerCampoConError) primerCampoConError = mesesCol;
                    }
                }

                if (erroresAccion.length > 0) {
                    errores.push({ accion: index + 1, errores: erroresAccion });
                }
            });


            /**
             * =====================================================
             * VALIDACIÓN: CADA INDICADOR DEBE TENER AL MENOS UNA ACCIÓN
             * -----------------------------------------------------
             * Regla de negocio estricta:
             * - No se permite guardar indicadores huérfanos
             * - Cada indicador debe estar asociado
             *   al menos a una acción
             * =====================================================
             */

            // 1️⃣ Obtener todos los índices de indicadores existentes
            const indicadoresExistentes = [];
            indicadoresRows.forEach(row => {
                const indiceInput = row.querySelector('[name$="[indice]"]');
                const nombreInput = row.querySelector('[name$="[indicador]"]');

                if (indiceInput && nombreInput && nombreInput.value.trim() !== "") {
                    indicadoresExistentes.push({
                        indice: indiceInput.value,
                        nombre: nombreInput.value
                    });
                }
            });

            // 2️⃣ Obtener todos los índices de indicadores usados por acciones
            const indicadoresUsados = new Set();
            accionesRows.forEach(row => {
                const indicadorHidden = row.querySelector('[name$="[indicador]"]');
                if (indicadorHidden && indicadorHidden.value !== "") {
                    indicadoresUsados.add(indicadorHidden.value);
                }
            });

            // 3️⃣ Detectar indicadores sin acciones
            const indicadoresSinAccion = indicadoresExistentes.filter(ind =>
                !indicadoresUsados.has(ind.indice)
            );

            // 4️⃣ Si hay indicadores huérfanos, bloquear submit
            if (indicadoresSinAccion.length > 0) {
                indicadoresSinAccion.forEach(ind => {
                    errores.push({
                        accion: "Indicador",
                        errores: [
                            `el indicador "${ind.nombre}" no tiene ninguna acción asociada`
                        ]
                    });
                });
            }




            if (errores.length > 0) {
                e.preventDefault();
                e.stopPropagation();

                const lista = document.getElementById("errores-lista");
                lista.innerHTML = "";

                errores.forEach(err => {
                    const li = document.createElement("li");
                    li.classList.add("list-group-item", "bg-dark", "text-light");
                    li.innerHTML = `
                        <strong>Acción ${err.accion}:</strong>
                        ${err.errores.join(", ")}
                    `;
                    lista.appendChild(li);
                });

                const modal = new bootstrap.Modal(
                    document.getElementById("erroresModal")
                );

                if (primerCampoConError) {
                    primerCampoConError.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }

                modal.show();
            }
        });
    }

    /**
 * =====================================================
 * AUTO-GROW DE TEXTAREAS (ENCABEZADO)
 * =====================================================
 */
document.querySelectorAll('.fixed-textarea').forEach(textarea => {
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
});

/**
 * Función utilitaria para limitar crecimiento
 * vertical de textareas
 */
function autoGrowLimited(textarea, maxRows = 5) {
    const lineHeight = parseFloat(getComputedStyle(textarea).lineHeight);

    textarea.style.height = 'auto';

    const rows = Math.floor(textarea.scrollHeight / lineHeight);

    if (rows <= maxRows) {
        textarea.style.overflowY = 'hidden';
        textarea.style.height = textarea.scrollHeight + 'px';
    } else {
        textarea.style.overflowY = 'auto';
        textarea.style.height = (lineHeight * maxRows) + 'px';
    }
}

/**
 * Inicialización del auto-grow limitado
 */
frame.querySelectorAll('.fixed-textarea').forEach(textarea => {
    autoGrowLimited(textarea, 5);

    textarea.addEventListener('input', () => {
        autoGrowLimited(textarea, 5);
    });
});

/**
 * =====================================================
 * APLICAR SOLO DÍGITOS A INPUTS EXISTENTES
 * =====================================================
 */
frame.querySelectorAll(
    '[name$="[valorBase]"], [name$="[valor]"]:not([type="hidden"])'
).forEach(input => {
    enforceOnlyDigits(input);
});


/**
 * =====================================================
 * MODAL COMPARTIDO — MODO DE CAPTURA MENSUAL
 * -----------------------------------------------------
 * Controla el modal que pregunta al usuario si capturará
 * el avance mensual del indicador en valor absoluto o %.
 *
 * Soporta dos modos de apertura:
 *   1. Normal: usuario activa el toggle %
 *   2. Validación: el submit detectó que capturaEnPorcentaje
 *      está vacío en algún indicador → abre el modal y al
 *      confirmar re-lanza el submit automáticamente.
 *
 * Props que recibe en modalEl antes de .show():
 *   _hiddenCapturaPct : HTMLInputElement (el hidden del card)
 *   _actualizarBadge  : function()
 *   _pendienteSubmit  : bool (true si fue lanzado por validación)
 *   _form             : HTMLFormElement (para el auto-submit)
 * =====================================================
 */
function initModalCapturaPct(frame) {

    const modalEl    = document.getElementById('modalCapturaPct');
    if (!modalEl) return;

    const btnAbs     = modalEl.querySelector('#modalCapturaBtnAbs');
    const btnPct     = modalEl.querySelector('#modalCapturaBtnPct');
    const btnConfirm = modalEl.querySelector('#modalCapturaConfirm');

    if (!btnAbs || !btnPct || !btnConfirm) return;

    let seleccion = ''; // '' = no elegido, '0' = absoluto, '1' = porcentaje

    // ── Botón "Valor absoluto" ──
    btnAbs.addEventListener('click', () => {
        seleccion = '0';
        btnAbs.classList.add('active');
        btnPct.classList.remove('active');
        btnConfirm.disabled = false; // habilitar al elegir
    });

    // ── Botón "Porcentaje %" ──
    btnPct.addEventListener('click', () => {
        seleccion = '1';
        btnPct.classList.add('active');
        btnAbs.classList.remove('active');
        btnConfirm.disabled = false; // habilitar al elegir
    });

    // ── Confirmar elección ──
    btnConfirm.addEventListener('click', () => {

        // Debe tener una selección antes de confirmar
        if (seleccion === '') return;

        const hidden          = modalEl._hiddenCapturaPct;
        const actualizarBadge = modalEl._actualizarBadge;
        const pendienteSubmit = modalEl._pendienteSubmit;
        const formRef         = modalEl._form;

        if (hidden) hidden.value = seleccion;
        if (actualizarBadge) actualizarBadge();

        bootstrap.Modal.getInstance(modalEl)?.hide();

        // Si fue lanzado desde validación de submit, re-lanzar el submit
        if (pendienteSubmit && formRef) {
            // Pequeño delay para dejar que el modal cierre limpiamente
            setTimeout(() => formRef.requestSubmit(), 100);
        }
    });

    // ── Al abrir el modal: sincronizar selección con el estado actual ──
    modalEl.addEventListener('show.bs.modal', () => {
        const hidden = modalEl._hiddenCapturaPct;

        // Si ya tiene valor confirmado previamente, pre-seleccionarlo
        if (hidden?.value === '0') {
            seleccion = '0';
            btnAbs.classList.add('active');
            btnPct.classList.remove('active');
            btnConfirm.disabled = false;
        } else if (hidden?.value === '1') {
            seleccion = '1';
            btnPct.classList.add('active');
            btnAbs.classList.remove('active');
            btnConfirm.disabled = false;
        } else {
            // Vacío ('') = sin elegir todavía → ninguna opción activa,
            // botón Confirmar deshabilitado hasta que el usuario elija
            seleccion = '';
            btnAbs.classList.remove('active');
            btnPct.classList.remove('active');
            btnConfirm.disabled = true;
        }
    });

    // ── Limpiar al cerrar ──
    modalEl.addEventListener('hidden.bs.modal', () => {
        modalEl._hiddenCapturaPct = null;
        modalEl._actualizarBadge  = null;
        modalEl._pendienteSubmit  = false;
        modalEl._form             = null;
    });
}

}// fin de la funcion de contencion

