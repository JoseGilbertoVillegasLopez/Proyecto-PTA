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
       MODAL ROBUSTO (ANTES / AHORA)
       ===================================================== */
    const modalEl = context.querySelector("#confirmModal");
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);

    const lista = context.querySelector("#lista-cambios");
    const btnConfirmar = context.querySelector("#btn-confirmar");
    const btnGuardarFinal = context.querySelector("#btn-guardar-final");

    if (!lista || !btnConfirmar || !btnGuardarFinal) return;

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

    btnConfirmar.addEventListener("click", () => {

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
