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
document.addEventListener("turbo:frame-load", (event) => {

    // Referencia al frame que acaba de cargarse
    const frame = event.target;

    // Seguridad: solo ejecutar si es el frame principal del contenido
    if (frame.id !== "content") return;

    console.log("PTA NEW JS cargado ✔");

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
            fetch(`/admin/api/personal/buscar?q=${encodeURIComponent(q)}`, {
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

            const select = row.querySelector("select");

            // Hidden real que se enviará al backend
            const hidden = row.querySelector(
                'input[type="hidden"][name$="[indicador]"]'
            );

            if (!select || !hidden) return;

            // Valor previamente seleccionado
            const valorActual = hidden.value;

            // Reset del select
            select.innerHTML = `<option value="">Seleccione un indicador</option>`;

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

                // Elimina la fila completa (tr)
                btn.closest("tr").remove();

                // Re-sincroniza indicadores con acciones
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
            indicadoresHolder.querySelectorAll("tr").length;

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

            // Campo hidden de índice lógico
            const indiceInput = temp.querySelector('[name$="[indice]"]');
            if (indiceInput) {
                indiceInput.value = indicadorIndiceGlobal;
                indicadorIndiceGlobal++;
            }

            // Crear fila de tabla
            const row = document.createElement("tr");
            row.classList.add("indicator-row");

            row.innerHTML = `
                <td class="p-2">
                    ${temp.querySelector('[name$="[indicador]"]').outerHTML}
                    ${indiceInput ? indiceInput.outerHTML : ''}
                </td>
                <td class="p-1">${temp.querySelector('[name$="[formula]"]').outerHTML}</td>
                <td class="p-1">${temp.querySelector('[name$="[valor]"]').outerHTML}</td>
                <td class="p-1">${temp.querySelector('[name$="[periodo]"]').outerHTML}</td>
                <td class="p-1">${temp.querySelector('[name$="[tendencia]"]').outerHTML}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-indicador">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            indicadoresHolder.appendChild(row);
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

        // Inicializar índice del CollectionType
        accionesHolder.dataset.index =
            accionesHolder.querySelectorAll("tr").length;

        /**
         * Agregar nueva acción
         */
        addAccionBtn.addEventListener("click", () => {

            const index = accionesHolder.dataset.index;
            const prototype = accionesHolder.dataset.prototype;

            const temp = document.createElement("div");
            temp.innerHTML = prototype.replace(/__name__/g, index);

            const row = document.createElement("tr");
            row.classList.add("accion-row");

            const accionInput = temp.querySelector('[name$="[accion]"]');
            const mesesInput = temp.querySelectorAll('[type="checkbox"]');

            /**
             * Select visible de indicador
             * (el real es un hidden)
             */
            const indicadorSelect = document.createElement("select");
            indicadorSelect.classList.add("form-select", "form-select-sm");
            indicadorSelect.innerHTML = `<option value="">Seleccione un indicador</option>`;

            // Poblar con indicadores existentes
            frame.querySelectorAll(".indicator-row").forEach(row => {
                const nombreInput = row.querySelector('[name$="[indicador]"]');
                const indiceInput = row.querySelector('[name$="[indice]"]');
                if (!nombreInput || !indiceInput) return;
                if (nombreInput.value.trim() === "") return;

                const option = document.createElement("option");
                option.value = indiceInput.value;
                option.textContent = nombreInput.value;
                indicadorSelect.appendChild(option);
            });

            // Campo hidden real del formulario
            const indicadorHidden = temp.querySelector('[name$="[indicador]"]');
            indicadorSelect.addEventListener("change", () => {
                indicadorHidden.value = indicadorSelect.value;
            });

            row.innerHTML = `
                <td class="p-2"></td>
                <td class="p-2">${accionInput.outerHTML}</td>
                <td class="p-2 meses-col"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-accion">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            // Insertar select + hidden
            const indicadorTd = row.querySelector("td");
            indicadorTd.appendChild(indicadorSelect);
            indicadorTd.appendChild(indicadorHidden);

            /**
             * Renderizado visual de meses
             */
            const mesesTd = row.querySelector(".meses-col");
            const nombresMeses = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];

            mesesInput.forEach((mes, i) => {
                const wrapper = document.createElement("label");
                wrapper.classList.add("mes-label");
                wrapper.style.display = "inline-flex";
                wrapper.style.alignItems = "center";
                wrapper.style.marginRight = "12px";
                wrapper.style.cursor = "pointer";

                const span = document.createElement("span");
                span.textContent = nombresMeses[i];
                span.style.marginLeft = "4px";

                wrapper.appendChild(mes);
                wrapper.appendChild(span);
                mesesTd.appendChild(wrapper);
            });

            accionesHolder.appendChild(row);
            accionesHolder.dataset.index++;

            activateRemoveButtons(frame, ".remove-accion");
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
    const form = frame.querySelector("form");

    if (form) {
        form.addEventListener("submit", (e) => {

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
                    const selectVisible = row.querySelector("select");
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
});

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
