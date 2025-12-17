/**
 * =====================================================
 * PTA — VISTA NEW
 * Script principal para la creación de un PTA
 *
 * FUNCIONALIDAD:
 *  - Manejo dinámico de Indicadores (CollectionType)
 *  - Manejo dinámico de Acciones (CollectionType)
 *  - Sincronización Indicadores ↔ Acciones
 *  - Validaciones de negocio antes del submit
 *
 * NOTAS IMPORTANTES:
 *  - Usa CollectionType + prototype (Symfony)
 *  - No renderiza filas iniciales
 *  - El submit final lo maneja Symfony (no AJAX)
 * =====================================================
 */

/**
 * Este evento se dispara cada vez que Turbo carga
 * contenido dentro de un <turbo-frame>.
 */
document.addEventListener("turbo:frame-load", (event) => {


    


    // Frame que acaba de cargarse
    const frame = event.target;

    // Ejecutar SOLO si el frame es el principal del dashboard
    if (frame.id !== "content") return;

    console.log("PTA NEW JS cargado ✔");


    function initPersonalSearch({
        inputSelector,
        hiddenSelector,
        resultsSelector
    }) {
        const input = frame.querySelector(inputSelector);
        const hidden = frame.querySelector(hiddenSelector);
        const results = frame.querySelector(resultsSelector);

        if (!input || !hidden || !results) return;

        let controller = null;

        input.addEventListener("input", () => {
            const q = input.value.trim();

            hidden.value = "";
            results.innerHTML = "";

            if (q.length < 2) return;

            if (controller) controller.abort();
            controller = new AbortController();

            fetch(`/admin/api/personal/buscar?q=${encodeURIComponent(q)}`, {
                signal: controller.signal
            })
                .then(res => res.json())
                .then(data => {
                    results.innerHTML = "";

                    data.forEach(p => {
                        const item = document.createElement("div");
                        item.classList.add("search-item");
                        item.textContent = p.nombre;

                        item.addEventListener("click", () => {
                            input.value = p.nombre;
                            hidden.value = p.id;
                            results.innerHTML = "";
                        });

                        results.appendChild(item);
                    });
                })
                .catch(() => {});
        });

        frame.addEventListener("click", (e) => {
    if (!results.contains(e.target) && e.target !== input) {
        results.innerHTML = "";
    }
});

    }
    initPersonalSearch({
    inputSelector: ".supervisor-search",
    hiddenSelector: 'input[name$="[supervisor]"]',
    resultsSelector: ".supervisor-results"
});
initPersonalSearch({
    inputSelector: ".aval-search",
    hiddenSelector: 'input[name$="[aval]"]',
    resultsSelector: ".aval-results"
});



    /**
     * Índice lógico incremental para indicadores.
     * Este índice NO es el id de BD,
     * sirve para relacionar indicadores con acciones.
     */
    let indicadorIndiceGlobal = 1;

    /**
     * =====================================================
     * SINCRONIZAR INDICADORES CON ACCIONES
     * -----------------------------------------------------
     * - Recolecta indicadores existentes
     * - Actualiza los <select> visibles de acciones
     * - Limpia selecciones inválidas
     * =====================================================
     */
    function syncIndicadoresConAcciones(frame) {

        // Lista temporal de indicadores válidos
        const indicadores = [];

        /**
         * Recorremos todas las filas de indicadores
         * para obtener:
         *  - nombre
         *  - índice lógico
         */
        frame.querySelectorAll(".indicator-row").forEach(row => {

            const nombreInput = row.querySelector('[name$="[indicador]"]');
            const indiceInput = row.querySelector('[name$="[indice]"]');

            // Si falta algún campo, ignorar
            if (!nombreInput || !indiceInput) return;

            // Si el nombre está vacío, no es válido
            if (nombreInput.value.trim() === "") return;

            indicadores.push({
                indice: indiceInput.value,
                nombre: nombreInput.value
            });
        });

        /**
         * Recorremos todas las acciones para:
         *  - reconstruir su select de indicadores
         *  - validar que el indicador seleccionado siga existiendo
         */
        frame.querySelectorAll(".accion-row").forEach(row => {

            const select = row.querySelector("select");
            const hidden = row.querySelector(
                'input[type="hidden"][name$="[indicador]"]'
            );

            if (!select || !hidden) return;

            const valorActual = hidden.value;

            // Limpiar opciones actuales
            select.innerHTML = `<option value="">Seleccione un indicador</option>`;

            let sigueExistiendo = false;

            indicadores.forEach(ind => {
                const option = document.createElement("option");
                option.value = ind.indice;
                option.textContent = ind.nombre;

                // Mantener selección si aún existe
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
     * UTILIDAD: BOTONES ELIMINAR
     * -----------------------------------------------------
     * Activa botones de eliminar para indicadores y acciones
     * =====================================================
     */
    function activateRemoveButtons(root, selector) {
        root.querySelectorAll(selector).forEach(btn => {
            btn.onclick = () => {
                // Elimina la fila completa
                btn.closest("tr").remove();

                // Re-sincroniza indicadores con acciones
                syncIndicadoresConAcciones(root);
            };
        });
    }

    /**
     * =====================================================
     * INDICADORES
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
         * Cuando el usuario escribe en el nombre del indicador,
         * se actualizan los selects de acciones en tiempo real.
         */
        indicadoresHolder.addEventListener("input", (e) => {
            if (e.target && e.target.matches('[name$="[indicador]"]')) {
                syncIndicadoresConAcciones(frame);
            }
        });

        /**
         * Agregar nuevo indicador
         */
        addIndicadorBtn.addEventListener("click", () => {

            const index = indicadoresHolder.dataset.index;
            const prototype = indicadoresHolder.dataset.prototype;

            // Crear HTML desde prototype
            const temp = document.createElement("div");
            temp.innerHTML = prototype.replace(/__name__/g, index);

            // Campo hidden del índice lógico
            const indiceInput = temp.querySelector('[name$="[indice]"]');
            if (indiceInput) {
                indiceInput.value = indicadorIndiceGlobal;
                indicadorIndiceGlobal++;
            }

            // Crear fila de tabla
            const row = document.createElement("tr");
            row.classList.add("indicator-row");
            const tendenciaSelect = temp.querySelector('[name$="[tendencia]"]');

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
     * ACCIONES
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
             * Select visible para elegir indicador
             */
            const indicadorSelect = document.createElement("select");
            indicadorSelect.classList.add("form-select", "form-select-sm");
            indicadorSelect.innerHTML = `<option value="">Seleccione un indicador</option>`;

            // Poblar select con indicadores existentes
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
             * Renderizar meses con etiquetas visuales
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

    function limpiarErroresVisuales(frame) {
        frame.querySelectorAll(".field-error").forEach(el => {
            el.classList.remove("field-error");
        });
    }


    /**
     * =====================================================
     * VALIDACIÓN FINAL — SUBMIT
     * =====================================================
     */
    const form = frame.querySelector("form");

    if (form) {
        form.addEventListener("submit", (e) => {


            // ===============================
// VALIDACIÓN RESPONSABLES
// ===============================
const supervisorInput = frame.querySelector(".supervisor-search");
const supervisorHidden = frame.querySelector('input[name$="[supervisor]"]');

const avalInput = frame.querySelector(".aval-search");
const avalHidden = frame.querySelector('input[name$="[aval]"]');

let erroresResponsables = [];

// Supervisor obligatorio y válido
if (!supervisorHidden || supervisorHidden.value === "") {
    erroresResponsables.push("Supervisor del Proyecto");
}

// Aval obligatorio y válido
if (!avalHidden || avalHidden.value === "") {
    erroresResponsables.push("Aval del Proyecto");
}

if (erroresResponsables.length > 0) {
    e.preventDefault();
    e.stopImmediatePropagation(); // 🔥 importante con Turbo

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

            // Regla 1: no puede estar todo vacío
            if (indicadoresRows.length === 0 && accionesRows.length === 0) {
                errores.push({
                    accion: "General",
                    errores: ["no hay indicadores ni acciones"]
                });
            }

            // Regla 2: no puede haber indicadores sin acciones
            if (indicadoresRows.length > 0 && accionesRows.length === 0) {
                errores.push({
                    accion: "General",
                    errores: ["hay indicadores pero no hay acciones"]
                });
            }



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


                if (!accionInput || accionInput.value.trim() === "") erroresAccion.push("sin acción");
                if (!indicadorHidden || indicadorHidden.value === "") erroresAccion.push("sin indicador");
                if (mesesChecks.length === 0) erroresAccion.push("sin meses");

                if (erroresAccion.length > 0) {
                    errores.push({ accion: index + 1, errores: erroresAccion });
                }
            });

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

document.querySelectorAll('.fixed-textarea').forEach(textarea => {
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
});

function autoGrowLimited(textarea, maxRows = 5) {
    const lineHeight = parseFloat(getComputedStyle(textarea).lineHeight);

    // Resetear altura para recalcular correctamente
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

frame.querySelectorAll('.fixed-textarea').forEach(textarea => {
    // Ajuste inicial (por si viene con texto)
    autoGrowLimited(textarea, 5);

    textarea.addEventListener('input', () => {
        autoGrowLimited(textarea, 5);
    });
});
