function bootPtaResponsables(context) {

    const form = context.querySelector("#responsables-form");
    if (!form) return;

    // 🛑 Evitar doble inicialización
    if (form.dataset.initialized === "true") return;
    form.dataset.initialized = "true";

    /* =====================================================
       BUSCADOR GENÉRICO DE PERSONAL (API /api/personal/buscar)
       ===================================================== */
    function initPersonalSearch({ inputSelector, hiddenSelector, resultsSelector }) {

        const input   = context.querySelector(inputSelector);
        const hidden  = context.querySelector(hiddenSelector);
        const results = context.querySelector(resultsSelector);

        if (!input || !hidden || !results) return;

        let controller = null;

        input.addEventListener("input", () => {

            const q = input.value.trim();

            // En cuanto escribe, invalidamos el hidden (evita IDs falsos)
            hidden.value = "";
            results.innerHTML = "";

            if (q.length < 2) return;

            if (controller) controller.abort();
            controller = new AbortController();

            fetch(`/api/personal/buscar?q=${encodeURIComponent(q)}`, {
                signal: controller.signal
            })
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = "";

                    data.forEach(p => {
                        const div = document.createElement("div");
                        div.classList.add("search-item");
                        div.textContent = p.nombre;

                        div.addEventListener("click", () => {
                            input.value = p.nombre;
                            hidden.value = p.id;
                            results.innerHTML = "";
                        });

                        results.appendChild(div);
                    });
                })
                .catch(() => {});
        });

        // Cerrar dropdown al dar click fuera (solo una vez por init)
        // Usamos document, pero comparando con los nodos ACTUALES del frame.
        document.addEventListener("click", (e) => {
            if (!results.contains(e.target) && e.target !== input) {
                results.innerHTML = "";
            }
        });
    }

    // 🔍 Inicializar buscadores
    initPersonalSearch({
        inputSelector: ".responsable-search",
        hiddenSelector: 'input[name="responsable_id"]',
        resultsSelector: ".responsable-results"
    });

    initPersonalSearch({
        inputSelector: ".supervisor-search",
        hiddenSelector: 'input[name="supervisor_id"]',
        resultsSelector: ".supervisor-results"
    });

    initPersonalSearch({
        inputSelector: ".aval-search",
        hiddenSelector: 'input[name="aval_id"]',
        resultsSelector: ".aval-results"
    });

    /* =====================================================
       RESPONSABLES ADICIONALES — agregar filas dinámicas
       -----------------------------------------------------
       No se guardan al agregar la fila: el hidden de cada
       fila usa form="responsables-form" para viajar junto
       con el resto del formulario solo hasta que se presione
       "Guardar cambios" (igual que en la vista new).
       ===================================================== */
    const addResponsableBtn = context.querySelector("#add-responsable-adicional");
    const nuevosHolder = context.querySelector("[data-collection-holder='nuevosResponsablesAdicionales']");
    const contador = context.querySelector("#resp-adicionales-count");

    if (addResponsableBtn && nuevosHolder) {

        const max = parseInt(nuevosHolder.dataset.max || "5", 10);
        const currentCount = parseInt(nuevosHolder.dataset.currentCount || "0", 10);

        function totalFilas() {
            return currentCount + nuevosHolder.querySelectorAll(".pta-resp-nuevo-row").length;
        }

        function actualizarBotonAgregar() {
            addResponsableBtn.style.display = totalFilas() >= max ? "none" : "";
            if (contador) contador.textContent = `${totalFilas()}/${max}`;
        }

        // Buscador de personal acotado a una fila específica (misma lógica que initPersonalSearch)
        function initSearchEnFila(row) {

            const input   = row.querySelector(".nuevo-responsable-adicional-search");
            const hidden  = row.querySelector(".nuevo-responsable-adicional-id");
            const results = row.querySelector(".search-results");

            if (!input || !hidden || !results) return;

            let controller = null;

            input.addEventListener("input", () => {

                const q = input.value.trim();
                hidden.value = "";
                results.innerHTML = "";

                if (q.length < 2) return;

                if (controller) controller.abort();
                controller = new AbortController();

                fetch(`/api/personal/buscar?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal
                })
                    .then(r => r.json())
                    .then(data => {
                        results.innerHTML = "";
                        data.forEach(p => {
                            const div = document.createElement("div");
                            div.classList.add("search-item");
                            div.textContent = p.nombre;
                            div.addEventListener("click", () => {
                                input.value = p.nombre;
                                hidden.value = p.id;
                                results.innerHTML = "";
                            });
                            results.appendChild(div);
                        });
                    })
                    .catch(() => {});
            });

            document.addEventListener("click", (e) => {
                if (!results.contains(e.target) && e.target !== input) {
                    results.innerHTML = "";
                }
            });
        }

        addResponsableBtn.addEventListener("click", () => {

            if (totalFilas() >= max) return;

            const row = document.createElement("div");
            row.classList.add("pta-resp-nuevo-row", "pta-resp-adicional-item");
            row.innerHTML = `
                <span class="pta-resp-adicional-nombre pta-resp-nuevo-search-wrap">
                    <i class="bi bi-person"></i>
                    <input type="hidden" name="nuevos_responsables_adicionales[]" form="responsables-form" class="nuevo-responsable-adicional-id">
                    <input type="text" class="nuevo-responsable-adicional-search pta-resp-nuevo-input" autocomplete="off" placeholder="Buscar por nombre...">
                    <div class="search-results"></div>
                </span>
                <button type="button" class="pta-resp-adicional-remove-btn" title="Quitar">
                    <i class="bi bi-trash3"></i>
                </button>
            `;

            nuevosHolder.appendChild(row);

            initSearchEnFila(row);

            row.querySelector(".pta-resp-adicional-remove-btn").addEventListener("click", () => {
                row.remove();
                actualizarBotonAgregar();
            });

            actualizarBotonAgregar();
        });
    }

    /* =====================================================
       MODAL ROBUSTO (ANTES / AHORA)
       ===================================================== */
    const modalEl = context.querySelector("#confirmModal");
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);

    const lista = context.querySelector("#lista-cambios");
    const botonesConfirmar = context.querySelectorAll(".js-confirmar-responsables, #btn-confirmar");
    const btnGuardarFinal = context.querySelector("#btn-guardar-final");

    if (!lista || botonesConfirmar.length === 0 || !btnGuardarFinal) return;

    const fields = [
        { label: "Responsable del proyecto", hidden: "responsable_id", input: ".responsable-search" },
        { label: "Supervisor del proyecto",  hidden: "supervisor_id",   input: ".supervisor-search"  },
        { label: "Aval del proyecto",        hidden: "aval_id",         input: ".aval-search"        },
    ];

    // Snapshot original (id + nombre visible)
    const original = {};
    fields.forEach(f => {
        original[f.hidden] = {
            id: context.querySelector(`[name="${f.hidden}"]`)?.value || "",
            nombre: context.querySelector(f.input)?.value?.trim() || "—"
        };
    });

    const abrirConfirmacion = () => {

        lista.innerHTML = "";
        let hayCambios = false;

        fields.forEach(f => {

            const hidden = context.querySelector(`[name="${f.hidden}"]`);
            const input  = context.querySelector(f.input);

            if (!hidden || !input) return;

            const nuevoId = hidden.value || "";
            const nuevoNombre = input.value?.trim() || "—";

            const previo = original[f.hidden];

            if (nuevoId !== previo.id) {
                hayCambios = true;

                const li = document.createElement("li");
                li.classList.add("list-group-item", "bg-dark", "text-light", "border-secondary");

                li.innerHTML = `
    <div class="mb-3">
        <div class="fw-semibold text-info fs-6">
            ${f.label}
        </div>
    </div>

    <div class="ps-3 mb-2">
        <div class="fw-semibold text-secondary fs-6">
            Antes
        </div>
        <div class="text-light fs-6">
            ${previo.nombre}
        </div>
    </div>

    <div class="ps-3">
        <div class="fw-semibold text-secondary fs-6">
            Después
        </div>
        <div class="text-light fw-semibold fs-6">
            ${nuevoNombre}
        </div>
    </div>
`;




                lista.appendChild(li);
            }
        });

        if (!hayCambios) {
            lista.innerHTML = `
                <li class="list-group-item bg-dark text-light border-secondary">
                    No se detectaron cambios en los responsables.
                </li>
            `;
        }

        modal.show();
    };

    botonesConfirmar.forEach((btn) => {
        btn.addEventListener("click", abrirConfirmacion);
    });

    btnGuardarFinal.addEventListener("click", () => {

        // Cerrar modal correctamente (Bootstrap + Turbo)
        modal.hide();

        // Limpieza manual por Turbo (backdrop + scroll)
        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("overflow");
        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());

        // Submit normal (Turbo se encarga)
        form.requestSubmit();
    });
}

/* ===============================
   BOOTSTRAP TURBO
   =============================== */
document.addEventListener("turbo:frame-load", (e) => {
    if (e.target.id === "content") {
        bootPtaResponsables(e.target);
    }
});

document.addEventListener("turbo:load", () => {
    bootPtaResponsables(document);
});
