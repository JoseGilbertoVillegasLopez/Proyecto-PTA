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

            // capturaEnPorcentaje: convertir checkbox → hidden
            // El bloque visual se añade dentro del card HTML
            const capturaPctCheck = temp.querySelector('[name$="[capturaEnPorcentaje]"]');
            if (capturaPctCheck) {
                capturaPctCheck.type = 'hidden';
                capturaPctCheck.value = '0';
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

                            <!--
                                Bloque capturaEnPorcentaje — solo visible cuando esPorcentaje=true.
                                Pregunta al usuario cómo capturará el avance mensual:
                                  - Valor absoluto: misma unidad que valorBase
                                  - Porcentaje: el indicador se mide en % (ej. eficiencia terminal)
                            -->
                            <div class="captura-pct-wrap" style="display:none; margin-top:10px;">
                                <div class="captura-pct-label">
                                    <i class="bi bi-pencil-square"></i>
                                    ¿Cómo registrarás el avance mensual?
                                </div>
                                <div class="btn-group btn-group-sm" role="group" style="margin-top:6px;">
                                    <button type="button" class="btn captura-abs-btn active" title="Captura en valor absoluto (misma unidad que el valor base)">
                                        <i class="bi bi-123"></i> Valor absoluto
                                    </button>
                                    <button type="button" class="btn captura-pct-btn" title="Captura en porcentaje (el indicador se mide en %)">
                                        <i class="bi bi-percent"></i> Porcentaje %
                                    </button>
                                </div>
                                <div class="captura-pct-hint"></div>
                            </div>
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
            const hiddenPct = card.querySelector('[name$="[esPorcentaje]"]');
            const btnAbs    = card.querySelector('.tipo-abs');
            const btnPct    = card.querySelector('.tipo-pct');

            // Toggle de modo de captura (capturaEnPorcentaje) —
            // solo visible cuando esPorcentaje=true.
            // Pregunta al usuario si capturará % o valor absoluto mensualmente.
            const hiddenCapturaPct = card.querySelector('[name$="[capturaEnPorcentaje]"]');
            const capturaPctWrap   = card.querySelector('.captura-pct-wrap');

            /**
             * Sincroniza la visibilidad del bloque capturaEnPorcentaje
             * según el estado actual de esPorcentaje.
             */
            function syncCapturaPctVisibility() {
                if (!capturaPctWrap) return;
                if (hiddenPct && hiddenPct.value === '1') {
                    capturaPctWrap.style.display = 'block';
                } else {
                    capturaPctWrap.style.display = 'none';
                    // Al ocultar, resetear capturaEnPorcentaje a false
                    if (hiddenCapturaPct) hiddenCapturaPct.value = '0';
                    // Resetear botones visibles si existen
                    const btnCapAbs = capturaPctWrap?.querySelector('.captura-abs-btn');
                    const btnCapPct = capturaPctWrap?.querySelector('.captura-pct-btn');
                    if (btnCapAbs) btnCapAbs.classList.add('active');
                    if (btnCapPct) btnCapPct.classList.remove('active');
                }
            }

            if (hiddenPct && btnAbs && btnPct) {
                btnAbs.addEventListener('click', () => {
                    hiddenPct.value = '0';
                    btnAbs.classList.add('active');
                    btnPct.classList.remove('active');
                    syncCapturaPctVisibility();
                });
                btnPct.addEventListener('click', () => {
                    hiddenPct.value = '1';
                    btnPct.classList.add('active');
                    btnAbs.classList.remove('active');
                    syncCapturaPctVisibility();
                });
            }

            // Toggle dentro de capturaEnPorcentaje (Absoluto / Porcentaje)
            const btnCapAbs  = card.querySelector('.captura-abs-btn');
            const btnCapPct  = card.querySelector('.captura-pct-btn');
            const capturHint = card.querySelector('.captura-pct-hint');

            const HINT_ABS = 'Registrarás números en la misma unidad que el valor base (alumnos, pesos, proyectos…)';
            const HINT_PCT = 'Registrarás porcentajes (0-100). Úsalo cuando el indicador se mide en % (eficiencia terminal, satisfacción…)';

            if (hiddenCapturaPct && btnCapAbs && btnCapPct) {
                btnCapAbs.addEventListener('click', () => {
                    hiddenCapturaPct.value = '0';
                    btnCapAbs.classList.add('active');
                    btnCapPct.classList.remove('active');
                    if (capturHint) capturHint.textContent = HINT_ABS;
                });
                btnCapPct.addEventListener('click', () => {
                    hiddenCapturaPct.value = '1';
                    btnCapPct.classList.add('active');
                    btnCapAbs.classList.remove('active');
                    if (capturHint) capturHint.textContent = HINT_PCT;
                });
                // Hint inicial
                if (capturHint) capturHint.textContent = HINT_ABS;
            }

            // Estado inicial (oculto porque esPorcentaje empieza en false)
            syncCapturaPctVisibility();

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


}// fin de la funcion de contencion

