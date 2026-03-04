/**
 * =====================================================
 * REPORTE PTA — VISTA NEW
 * ACCIONES + SINCRONIZACIÓN COMPLETA
 * =====================================================
 */

function bootReporteNew(context) {
    const reporteRoot = context.querySelector("[data-reporte-form]");
    if (!reporteRoot) return;

    initReporteNew(reporteRoot);
}

/* ===================================================== */
/* EVENTOS UNIVERSALES */
/* ===================================================== */

document.addEventListener("turbo:frame-load", (event) => {
    const frame = event.target;
    if (!frame || frame.id !== "content") return;
    bootReporteNew(frame);
});

document.addEventListener("turbo:load", () => {
    bootReporteNew(document);
});

document.addEventListener("DOMContentLoaded", () => {
    bootReporteNew(document);
});

/* ===================================================== */
/* INICIALIZACIÓN */
/* ===================================================== */

function initReporteNew(root) {
    console.log("Reporte PTA NEW JS cargado ✔");
    console.log("Root encontrado:", root);
    const modo = root.dataset.modo;

    if (modo === "edit") {
        console.log("Modo EDIT detectado ✔");

        const datosIniciales = JSON.parse(root.dataset.datosIniciales || "{}");
        const uploadsBase = root.dataset.uploadsBase || "";

        hidratarFormulario(root, datosIniciales, uploadsBase);

        // 🔥 Forzar sync de headers de gastos + orden consistente
        syncGastosInicial(root);
    }

    activarBotonesAgregar(root);
    /* =====================================================
   SYNC TEXTO ACCIÓN → HEADER GASTO (DELEGACIÓN)
   ===================================================== */

    root.addEventListener("input", function (e) {
        if (!e.target.matches(".pta-rnew-accion-card input[type='text']"))
            return;

        const input = e.target;
        const accionCard = input.closest(".pta-rnew-accion-card");
        const container = input.closest(".acciones-container");

        if (!accionCard || !container) return;

        const indicadorIndex = container.dataset.indicadorIndex;
        const accionIndex = accionCard.dataset.accionIndex;

        const gastosContainer = root.querySelector(
            `.gastos-container[data-indicador-index="${indicadorIndex}"]`,
        );

        if (!gastosContainer) return;

        const card = gastosContainer.querySelector(
            `.pta-rnew-gasto-card[data-accion-index="${accionIndex}"]`,
        );

        if (!card) return;

        const header = card.querySelector(".gasto-header");
        if (!header) return;

        const texto = input.value.trim();

        header.textContent = texto.length > 0 ? texto : `Acción ${accionIndex}`;
    });
    initEvidencias(root);
    activarValidacionEnvio(root);
}

/* =====================================================
   BOTÓN AGREGAR ACCIÓN
   ===================================================== */

function activarBotonesAgregar(root) {
    root.querySelectorAll(".add-accion").forEach((btn) => {
        btn.addEventListener("click", function () {
            // ✅ ANTES: closest(".card-body") -> ya no existe
            // ✅ AHORA: nos colgamos del accordion-item actual
            const accordionItem = this.closest(".accordion-item");
            if (!accordionItem) {
                console.warn("[add-accion] No se encontró .accordion-item");
                return;
            }

            const container = accordionItem.querySelector(
                ".acciones-container",
            );
            if (!container) {
                console.warn("[add-accion] No se encontró .acciones-container");
                return;
            }

            const indicadorIndex = container.dataset.indicadorIndex;

            let accionIndex = parseInt(
                container.dataset.accionIndex || "0",
                10,
            );
            accionIndex++;

            container.dataset.accionIndex = accionIndex;

            const div = document.createElement("div");
            div.classList.add("pta-rnew-accion-card");
            div.dataset.accionIndex = accionIndex;

            div.innerHTML = `
                <label class="form-label">Acción ${accionIndex}*</label>

                <input type="hidden"
                       name="acciones[${indicadorIndex}][${accionIndex}][indice]"
                       value="${accionIndex}">

                <div class="pta-rnew-accion-row">
                    <input type="text"
                           class="form-control"
                           name="acciones[${indicadorIndex}][${accionIndex}][descripcion]">

                    <button type="button"
                            class="btn btn-danger btn-sm remove-accion">
                        X
                    </button>
                </div>
            `;

            container.appendChild(div);

            activateRemove(root, div);
            createGastoCard(root, indicadorIndex, accionIndex);
        });
    });
}

/* =====================================================
   SYNC INICIAL
   ===================================================== */

function syncGastosInicial(root) {
    root.querySelectorAll(".acciones-container").forEach((container) => {
        const indicadorIndex = container.dataset.indicadorIndex;

        container.querySelectorAll(".pta-rnew-accion-card").forEach((item) => {
            const accionIndex = item.dataset.accionIndex;

            if (!existsGastoCard(root, indicadorIndex, accionIndex)) {
                createGastoCard(root, indicadorIndex, accionIndex);
            }
        });

        reorderGastos(root, indicadorIndex);
    });
}

function existsGastoCard(root, indicadorIndex, accionIndex) {
    const gastosContainer = root.querySelector(
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`,
    );

    if (!gastosContainer) return false;

    return !!gastosContainer.querySelector(
        `.pta-rnew-gasto-card[data-accion-index="${accionIndex}"]`,
    );
}

/* =====================================================
   ELIMINAR Y REORDENAR
   ===================================================== */

function activateRemove(root, element) {
    if (!element) return;

    const btn = element.querySelector(".remove-accion");
    if (!btn) return;

    btn.addEventListener("click", function () {
        const container = element.closest(".acciones-container");
        const indicadorIndex = container.dataset.indicadorIndex;
        const accionIndex = element.dataset.accionIndex;

        removeGastoCard(root, indicadorIndex, accionIndex);
        element.remove();

        let contador = 1;

        container.querySelectorAll(".pta-rnew-accion-card").forEach((item) => {
            item.dataset.accionIndex = contador;
            item.querySelector("label").textContent = `Acción ${contador}*`;

            const hidden = item.querySelector('input[type="hidden"]');
            hidden.name = `acciones[${indicadorIndex}][${contador}][indice]`;
            hidden.value = contador;

            const input = item.querySelector('input[type="text"]');
            input.name = `acciones[${indicadorIndex}][${contador}][descripcion]`;

            contador++;
        });

        container.dataset.accionIndex = contador - 1;

        reorderGastos(root, indicadorIndex);
    });
}

/* =====================================================
   CREAR GASTO
   ===================================================== */

function createGastoCard(root, indicadorIndex, accionIndex) {
    const gastosContainer = root.querySelector(
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`,
    );

    if (!gastosContainer) return;

    if (existsGastoCard(root, indicadorIndex, accionIndex)) return;

    const procesosEstrategicos = JSON.parse(
        root.dataset.procesosEstrategicos || "[]",
    );
    const procesosClave = JSON.parse(root.dataset.procesosClave || "[]");

    const card = document.createElement("div");
    card.classList.add("pta-rnew-gasto-card");
    card.dataset.accionIndex = accionIndex;

    card.innerHTML = `
    <div class="pta-rnew-gasto-header gasto-header">
        Acción ${accionIndex}
    </div>

    <div class="pta-rnew-gasto-body">

        <div class="mb-3">
            <label class="form-label">Proceso Estratégico</label>
            <select class="form-select"
                    name="gastos[${indicadorIndex}][${accionIndex}][proceso_estrategico]">
                <option value="">Seleccione...</option>
                ${procesosEstrategicos
                    .map((p) => `<option value="${p.id}">${p.nombre}</option>`)
                    .join("")}
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Proceso Clave</label>
            <select class="form-select"
                    name="gastos[${indicadorIndex}][${accionIndex}][proceso_clave]">
                <option value="">Seleccione...</option>
                ${procesosClave
                    .map((p) => `<option value="${p.id}">${p.nombre}</option>`)
                    .join("")}
            </select>
        </div>

        <hr>

        <div class="partidas-container"
             data-indicador-index="${indicadorIndex}"
             data-accion-index="${accionIndex}"
             data-partida-index="0">
        </div>

        <button type="button"
                class="btn btn-outline-info add-partida mt-2">
            + Agregar Partida
        </button>

    </div>
`;

    gastosContainer.appendChild(card);
    // Activar botón agregar partida
    const addBtn = card.querySelector(".add-partida");

    addBtn.addEventListener("click", function () {
        createPartida(root, indicadorIndex, accionIndex);
    });
}

/* =====================================================
   ELIMINAR GASTO
   ===================================================== */

function removeGastoCard(root, indicadorIndex, accionIndex) {
    const gastosContainer = root.querySelector(
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`,
    );

    if (!gastosContainer) return;

    const card = gastosContainer.querySelector(
        `.pta-rnew-gasto-card[data-accion-index="${accionIndex}"]`,
    );

    if (card) card.remove();
}

/* =====================================================
   REORDENAR GASTOS
   ===================================================== */

function reorderGastos(root, indicadorIndex) {
    const gastosContainer = root.querySelector(
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`,
    );

    if (!gastosContainer) return;

    let contador = 1;

    gastosContainer.querySelectorAll(".pta-rnew-gasto-card").forEach((card) => {
        card.dataset.accionIndex = contador;

        const header = card.querySelector(".gasto-header");

        const inputAccion = root.querySelector(
            `.acciones-container[data-indicador-index="${indicadorIndex}"] 
             .pta-rnew-accion-card[data-accion-index="${contador}"] input[type="text"]`,
        );

        if (inputAccion && inputAccion.value.trim() !== "") {
            header.textContent = inputAccion.value.trim();
        } else {
            header.textContent = `Acción ${contador}`;
        }

        const selects = card.querySelectorAll("select");

        if (selects[0]) {
            selects[0].name = `gastos[${indicadorIndex}][${contador}][proceso_estrategico]`;
        }

        if (selects[1]) {
            selects[1].name = `gastos[${indicadorIndex}][${contador}][proceso_clave]`;
        }

        /* =====================================================
           🔥 REORDENAR PARTIDAS — DEBE IR DENTRO DEL FOR EACH
           ===================================================== */

        const partidasContainer = card.querySelector(".partidas-container");

        if (partidasContainer) {
            let partidaContador = 1;

            partidasContainer
                .querySelectorAll("[data-partida-index]")
                .forEach((item) => {
                    item.dataset.partidaIndex = partidaContador;

                    const select = item.querySelector("select");
                    const input = item.querySelector("input");

                    if (select) {
                        select.name = `gastos[${indicadorIndex}][${contador}][partidas][${partidaContador}][partida_id]`;
                    }

                    if (input) {
                        input.name = `gastos[${indicadorIndex}][${contador}][partidas][${partidaContador}][monto]`;
                    }

                    partidaContador++;
                });

            partidasContainer.dataset.partidaIndex = partidaContador - 1;
        }

        contador++;
    });
}

/* =====================================================
   SINCRONIZAR TEXTO ACCIÓN → HEADER GASTO
   ===================================================== */

function createPartida(root, indicadorIndex, accionIndex) {
    const partidasContainer = root.querySelector(
        `.partidas-container[data-indicador-index="${indicadorIndex}"][data-accion-index="${accionIndex}"]`,
    );

    if (!partidasContainer) return;

    let partidaIndex = parseInt(partidasContainer.dataset.partidaIndex);
    partidaIndex++;

    partidasContainer.dataset.partidaIndex = partidaIndex;

    const partidas = JSON.parse(root.dataset.partidasPresupuestales || "[]");

    const div = document.createElement("div");
    div.classList.add("pta-rnew-partida-card");
    div.dataset.partidaIndex = partidaIndex;

    div.innerHTML = `
        <div class="row g-2">

            <div class="col-md-6">
                <label class="form-label">Partida Presupuestal</label>
                <select class="form-select"
                        name="gastos[${indicadorIndex}][${accionIndex}][partidas][${partidaIndex}][partida_id]">
                    <option value="">Seleccione...</option>
                    ${partidas
                        .map(
                            (p) =>
                                `<option value="${p.id}">
                            ${p.capitulo} - ${p.partida} - ${p.descripcion}
                        </option>`,
                        )
                        .join("")}
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Monto</label>
                <input type="number"
                       step="0.01"
                       class="form-control"
                       name="gastos[${indicadorIndex}][${accionIndex}][partidas][${partidaIndex}][monto]">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="button"
                        class="btn btn-danger btn-sm remove-partida">
                    X
                </button>
            </div>

        </div>
    `;

    partidasContainer.appendChild(div);

    activateRemovePartida(root, div, indicadorIndex, accionIndex);
}

function activateRemovePartida(root, element, indicadorIndex, accionIndex) {
    const btn = element.querySelector(".remove-partida");
    if (!btn) return;

    btn.addEventListener("click", function () {
        const container = element.closest(".partidas-container");

        element.remove();

        let contador = 1;

        container.querySelectorAll("[data-partida-index]").forEach((item) => {
            item.dataset.partidaIndex = contador;

            const select = item.querySelector("select");
            const input = item.querySelector("input");

            if (select) {
                select.name = `gastos[${indicadorIndex}][${accionIndex}][partidas][${contador}][partida_id]`;
            }

            if (input) {
                input.name = `gastos[${indicadorIndex}][${accionIndex}][partidas][${contador}][monto]`;
            }

            contador++;
        });

        container.dataset.partidaIndex = contador - 1;
    });
}

/* =====================================================
   EVIDENCIAS POR INDICADOR
   ===================================================== */

function initEvidencias(root) {
    activarBotonesAgregarEvidencia(root);
}

function activarBotonesAgregarEvidencia(root) {
    root.querySelectorAll(".add-evidencia").forEach((btn) => {
        btn.addEventListener("click", function () {
            // ✅ ANTES: closest(".card-body") -> ya no existe
            // ✅ AHORA: nos colgamos del accordion-item actual
            const accordionItem = this.closest(".accordion-item");
            if (!accordionItem) {
                console.warn("[add-evidencia] No se encontró .accordion-item");
                return;
            }

            const container = accordionItem.querySelector(
                ".evidencias-container",
            );
            if (!container) {
                console.warn(
                    "[add-evidencia] No se encontró .evidencias-container",
                );
                return;
            }

            const indicadorIndex = container.dataset.indicadorIndex;

            let bloqueIndex = parseInt(
                container.dataset.bloqueIndex || "0",
                10,
            );

            if (bloqueIndex >= 3) {
                alert("Máximo 3 bloques de evidencia permitidos.");
                return;
            }

            bloqueIndex++;
            container.dataset.bloqueIndex = bloqueIndex;

            const bloque = document.createElement("div");
            bloque.classList.add("pta-rnew-evidencia-card");
            bloque.dataset.bloqueIndex = bloqueIndex;

            bloque.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Evidencia ${bloqueIndex}</strong>
                    <button type="button"
                            class="btn btn-danger btn-sm remove-evidencia">
                        Eliminar
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción *</label>
                    <textarea class="form-control"
                            rows="3"
                            name="evidencias[${indicadorIndex}][${bloqueIndex}][descripcion]"
                            required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imágenes (máx 4) *</label>
                    <div class="evidencia-preview-container d-flex gap-2 flex-wrap"></div>
                </div>
            `;

            container.appendChild(bloque);
            inicializarSistemaImagenes(bloque, indicadorIndex, bloqueIndex);
            activarEliminarBloque(bloque);
        });
    });
}

function activarEliminarBloque(bloque) {
    const btn = bloque.querySelector(".remove-evidencia");
    if (!btn) return;

    btn.addEventListener("click", function () {
        const container = bloque.closest(".evidencias-container");
        const indicadorIndex = container.dataset.indicadorIndex;

        bloque.remove();

        let contador = 1;

        container
            .querySelectorAll(".pta-rnew-evidencia-card")
            .forEach((item) => {
                item.dataset.bloqueIndex = contador;

                item.querySelector("strong").textContent =
                    `Evidencia ${contador}`;

                const textarea = item.querySelector("textarea");

                textarea.name = `evidencias[${indicadorIndex}][${contador}][descripcion]`;

                contador++;
            });

        container.dataset.bloqueIndex = contador - 1;
    });
}

/* =====================================================
   SISTEMA VISUAL DE IMÁGENES TIPO "+"
   ===================================================== */

function inicializarSistemaImagenes(bloque, indicadorIndex, bloqueIndex) {
    const container = bloque.querySelector(".evidencia-preview-container");

    if (!container) return;

    renderBotonAgregar(container, bloque, indicadorIndex, bloqueIndex);
}

function renderBotonAgregar(container, bloque, indicadorIndex, bloqueIndex) {
    // 🔥 Eliminar cualquier botón "+" previo
    container
        .querySelectorAll(".evidencia-card.add-button")
        .forEach((btn) => btn.remove());

    const totalPreviews = container.querySelectorAll(
        ".evidencia-card:not(.add-button)",
    ).length;

    if (totalPreviews >= 4) return;

    const card = document.createElement("div");
    card.classList.add("evidencia-card", "add-button");

    card.style.width = "120px";
    card.style.height = "120px";
    card.style.border = "2px dashed #ccc";
    card.style.display = "flex";
    card.style.alignItems = "center";
    card.style.justifyContent = "center";
    card.style.cursor = "pointer";
    card.style.fontSize = "40px";
    card.innerHTML = "+";

    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.style.display = "none";

    card.addEventListener("click", () => input.click());

    input.addEventListener("change", function () {
        const file = input.files[0];
        if (!file) return;

        if (!file.type.startsWith("image/")) {
            alert("Solo se permiten imágenes.");
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert("La imagen supera los 5MB.");
            return;
        }

        input.name = `evidencias[${indicadorIndex}][${bloqueIndex}][imagenes][]`;
        input.classList.add("imagen-real");

        mostrarPreview(container, file, input);

        container.appendChild(input);

        renderBotonAgregar(container, bloque, indicadorIndex, bloqueIndex);
    });

    container.appendChild(card);
}

function mostrarPreview(container, file, input) {
    const card = document.createElement("div");
    card.classList.add("evidencia-card");
    card.style.width = "120px";
    card.style.height = "120px";
    card.style.position = "relative";
    card.style.border = "1px solid #ddd";

    const img = document.createElement("img");
    img.src = URL.createObjectURL(file);
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.objectFit = "cover";

    img.style.cursor = "pointer";
    img.addEventListener("click", function () {
        abrirModalPreview(img.src);
    });

    const btn = document.createElement("button");
    btn.type = "button";
    btn.innerHTML = "×";
    btn.style.position = "absolute";
    btn.style.top = "2px";
    btn.style.right = "2px";
    btn.style.background = "red";
    btn.style.color = "white";
    btn.style.border = "none";
    btn.style.width = "22px";
    btn.style.height = "22px";
    btn.style.cursor = "pointer";

    btn.addEventListener("click", function () {
        input.remove();
        card.remove();

        const imagenesActuales =
            container.querySelectorAll(".imagen-real").length;
        const existeBotonMas = container.querySelector(
            ".evidencia-card.add-button",
        );

        if (imagenesActuales < 4 && !existeBotonMas) {
            renderBotonAgregar(container, bloque, indicadorIndex, bloqueIndex);
        }
    });

    card.appendChild(img);
    card.appendChild(btn);

    container.appendChild(card);
}

function crearCardImagenExistente(
    container,
    bloque,
    indicadorIndex,
    bloqueIndex,
    uploadsBase,
    nombreImagen,
) {
    // 1) Hidden input para que el backend sepa qué imagen se conserva
    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = `evidencias[${indicadorIndex}][${bloqueIndex}][imagenes_existentes][]`;
    hidden.value = nombreImagen;
    hidden.classList.add("imagen-existente-hidden");

    // Lo metemos dentro del bloque (para que vaya en el submit)
    bloque.appendChild(hidden);

    // 2) Card visual con la imagen
    const card = document.createElement("div");
    card.classList.add("evidencia-card");
    card.style.width = "120px";
    card.style.height = "120px";
    card.style.position = "relative";
    card.style.border = "1px solid #ddd";

    const img = document.createElement("img");
    img.src = uploadsBase + nombreImagen;
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.objectFit = "cover";
    img.style.cursor = "pointer";

    img.addEventListener("click", function () {
        abrirModalPreview(img.src);
    });

    // 3) Botón X para quitar (quita card + hidden)
    const btn = document.createElement("button");
    btn.type = "button";
    btn.innerHTML = "×";
    btn.style.position = "absolute";
    btn.style.top = "2px";
    btn.style.right = "2px";
    btn.style.background = "red";
    btn.style.color = "white";
    btn.style.border = "none";
    btn.style.width = "22px";
    btn.style.height = "22px";
    btn.style.cursor = "pointer";

    btn.addEventListener("click", function () {
        // quita de UI
        card.remove();

        // quita del submit (ya no se conservará en BD)
        hidden.remove();

        // si hay espacio y no existe el +, lo volvemos a poner
        const totalPreviews = container.querySelectorAll(
            ".evidencia-card:not(.add-button)",
        ).length;
        const existeBotonMas = container.querySelector(
            ".evidencia-card.add-button",
        );

        if (totalPreviews < 4 && !existeBotonMas) {
            renderBotonAgregar(container, bloque, indicadorIndex, bloqueIndex);
        }
    });

    card.appendChild(img);
    card.appendChild(btn);
    container.appendChild(card);
}

/* =====================================================
   MODAL PREVIEW IMAGEN EVIDENCIA
   ===================================================== */

function crearModalPreviewSiNoExiste() {
    if (document.getElementById("modalPreviewEvidencia")) return;

    const modal = document.createElement("div");
    modal.id = "modalPreviewEvidencia";

    modal.style.position = "fixed";
    modal.style.top = "0";
    modal.style.left = "0";
    modal.style.width = "100%";
    modal.style.height = "100%";
    modal.style.background = "rgba(0,0,0,0.85)";
    modal.style.display = "none";
    modal.style.alignItems = "center";
    modal.style.justifyContent = "center";
    modal.style.zIndex = "9999";

    modal.innerHTML = `
    <div style="
        position:relative;
        max-width:90vw;
        max-height:85vh;
        display:flex;
        align-items:center;
        justify-content:center;
    ">
        <button id="cerrarModalPreview"
                style="
                    position:absolute;
                    top:-45px;
                    right:0;
                    background:red;
                    color:white;
                    border:none;
                    width:35px;
                    height:35px;
                    font-size:18px;
                    cursor:pointer;
                ">
            X
        </button>
        <img id="imagenModalPreview"
             style="
                max-width:85vw;
                max-height:85vh;
                object-fit:contain;
                border-radius:8px;
             ">
    </div>
`;

    document.body.appendChild(modal);

    // Cerrar con botón
    modal.querySelector("#cerrarModalPreview").addEventListener("click", () => {
        modal.style.display = "none";
    });

    // Cerrar haciendo click fuera
    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
}

function abrirModalPreview(src) {
    crearModalPreviewSiNoExiste();

    const modal = document.getElementById("modalPreviewEvidencia");
    const img = document.getElementById("imagenModalPreview");

    img.src = src;
    modal.style.display = "flex";
}

/* =====================================================
   HIDRATAR FORMULARIO — MODO EDIT
===================================================== */

function hidratarFormulario(root, datosIniciales, uploadsBase) {
    Object.keys(datosIniciales).forEach((indice) => {
        const indicadorData = datosIniciales[indice];

        /* ==============================
           ACCIONES
        ============================== */

        const accionesContainer = root.querySelector(
            `.acciones-container[data-indicador-index="${indice}"]`,
        );

        if (!accionesContainer) return;

        Object.keys(indicadorData.acciones || {}).forEach((accionIndexRaw) => {
            const accionIndex = parseInt(accionIndexRaw, 10);

            const accion = indicadorData.acciones[accionIndex];

            // Crear acción visualmente igual que botón "+"
            const div = document.createElement("div");
            div.classList.add("pta-rnew-accion-card");
            div.dataset.accionIndex = accionIndex;

            div.innerHTML = `
                <label class="form-label">Acción ${accionIndex}*</label>

                <input type="hidden"
                       name="acciones[${indice}][${accionIndex}][indice]"
                       value="${accionIndex}">

                <div class="pta-rnew-accion-row">
                    <input type="text"
                           class="form-control"
                           name="acciones[${indice}][${accionIndex}][descripcion]"
                           value="${accion.descripcion || ""}">

                    <button type="button"
                            class="btn btn-danger btn-sm remove-accion">
                        X
                    </button>
                </div>
            `;

            const btnWrap = accionesContainer
                .querySelector(".add-accion")
                ?.closest(".text-end");
            if (btnWrap) accionesContainer.insertBefore(div, btnWrap);
            else accionesContainer.appendChild(div);
            activateRemove(root, div);

            // Crear gasto correspondiente
            createGastoCard(root, indice, accionIndex);

            const gastoCard = root.querySelector(
                `.pta-rnew-gasto-card[data-accion-index="${accionIndex}"]`,
            );
            if (gastoCard) {
                const header = gastoCard.querySelector(".gasto-header");
                if (header) {
                    const texto = (accion.descripcion || "").trim();
                    header.textContent =
                        texto !== "" ? texto : `Acción ${accionIndex}`;
                }
            }

            if (gastoCard) {
                const selects = gastoCard.querySelectorAll("select");

                if (selects[0] && accion.proceso_estrategico_id) {
                    selects[0].value = accion.proceso_estrategico_id;
                }

                if (selects[1] && accion.proceso_clave_id) {
                    selects[1].value = accion.proceso_clave_id;
                }

                /* ==============================
                   PARTIDAS
                ============================== */

                const partidasContainer = gastoCard.querySelector(
                    ".partidas-container",
                );

                Object.keys(accion.partidas || {}).forEach((partidaIndex) => {
                    createPartida(root, indice, accionIndex);

                    const ultimaPartida = partidasContainer.querySelector(
                        `[data-partida-index="${partidaIndex}"]`,
                    );

                    if (ultimaPartida) {
                        const select = ultimaPartida.querySelector("select");
                        const input = ultimaPartida.querySelector("input");

                        if (
                            select &&
                            accion.partidas[partidaIndex].partida_id
                        ) {
                            select.value =
                                accion.partidas[partidaIndex].partida_id;
                        }

                        if (input && accion.partidas[partidaIndex].cantidad) {
                            input.value =
                                accion.partidas[partidaIndex].cantidad;
                        }
                    }
                });
            }
        });

        /* ==============================
           EVIDENCIAS
        ============================== */

        const evidenciasContainer = root.querySelector(
            `.evidencias-container[data-indicador-index="${indice}"]`,
        );

        Object.keys(indicadorData.evidencias || {}).forEach(
            (bloqueIndexRaw) => {
                const bloqueIndex = parseInt(bloqueIndexRaw, 10);

                const bloqueData = indicadorData.evidencias[bloqueIndex];

                const bloque = document.createElement("div");
                bloque.classList.add("pta-rnew-evidencia-card");
                bloque.dataset.bloqueIndex = bloqueIndex;

                bloque.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Evidencia ${bloqueIndex}</strong>
                    <button type="button"
                            class="btn btn-danger btn-sm remove-evidencia">
                        Eliminar
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción *</label>
                    <textarea class="form-control"
          rows="3"
          name="evidencias[${indice}][${bloqueIndex}][descripcion]">${(bloqueData.descripcion || "").trim()}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Imágenes</label>
                    <div class="evidencia-preview-container d-flex gap-2 flex-wrap"></div>
                </div>
            `;

                evidenciasContainer.appendChild(bloque);
                activarEliminarBloque(bloque);

                const previewContainer = bloque.querySelector(
                    ".evidencia-preview-container",
                );

                (bloqueData.imagenes || []).forEach((nombreImagen) => {
                    crearCardImagenExistente(
                        previewContainer,
                        bloque,
                        indice,
                        bloqueIndex,
                        uploadsBase,
                        nombreImagen,
                    );
                });

                renderBotonAgregar(
                    previewContainer,
                    bloque,
                    indice,
                    bloqueIndex,
                );
                renderBotonAgregar(
                    previewContainer,
                    bloque,
                    indice,
                    bloqueIndex,
                );
            },
        );
    });
}

/* =====================================================
   VALIDACIÓN FRONTEND REPORTE
   ===================================================== */

function activarValidacionEnvio(root) {
    const form = root.querySelector("form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        limpiarErroresVisuales(root);

        const erroresAgrupados = {};
        let primerCampoError = null;

        root.querySelectorAll(".accordion-item").forEach(
            (item, indicadorIndex) => {
                const numeroIndicador = indicadorIndex + 1;
                const collapse = item.querySelector(".accordion-collapse");

                if (!erroresAgrupados[numeroIndicador]) {
                    erroresAgrupados[numeroIndicador] = [];
                }

                /* ===============================
               CAMPOS INDICADOR
            =============================== */

                item.querySelectorAll("[name^='reporte[indicadores]']").forEach(
                    (input) => {
                        if (input.value.trim() === "") {
                            const label =
                                input
                                    .closest(".mb-3")
                                    ?.querySelector("label")
                                    ?.textContent.trim() || "Campo";

                            erroresAgrupados[numeroIndicador].push({
                                mensaje: `⚠ ${label} está vacío.`,
                                elemento: input,
                                collapse: collapse,
                            });

                            marcarError(input);
                            if (!primerCampoError) primerCampoError = input;
                        }
                    },
                );

                /* ===============================
               ACCIONES
            =============================== */

                const acciones = item.querySelectorAll(".pta-rnew-accion-card");

                if (acciones.length === 0) {
                    erroresAgrupados[numeroIndicador].push({
                        mensaje: "⚠ Debe agregar al menos una acción.",
                        elemento: collapse,
                        collapse: collapse,
                    });
                }

                acciones.forEach((accion, accionIndex) => {
                    const numeroAccion = accionIndex + 1;
                    const descripcion = accion.querySelector("input");

                    if (!descripcion || descripcion.value.trim() === "") {
                        erroresAgrupados[numeroIndicador].push({
                            mensaje: `⚠ Acción ${numeroAccion} sin descripción.`,
                            elemento: descripcion,
                            collapse: collapse,
                        });

                        marcarError(descripcion);
                        if (!primerCampoError) primerCampoError = descripcion;
                    }
                });

                /* ===============================
               PARTIDAS
            =============================== */

                item.querySelectorAll(".partidas-container").forEach(
                    (container, accionIndex) => {
                        const numeroAccion = accionIndex + 1;
                        const partidas = container.querySelectorAll(
                            "[data-partida-index]",
                        );

                        if (partidas.length === 0) {
                            erroresAgrupados[numeroIndicador].push({
                                mensaje: `⚠ Acción ${numeroAccion} sin partidas.`,
                                elemento: container,
                                collapse: collapse,
                            });
                        }

                        partidas.forEach((partida, partidaIndex) => {
                            const numeroPartida = partidaIndex + 1;
                            const select = partida.querySelector("select");
                            const monto = partida.querySelector("input");

                            if (!select || select.value === "") {
                                erroresAgrupados[numeroIndicador].push({
                                    mensaje: `⚠ Acción ${numeroAccion} → Partida ${numeroPartida} sin seleccionar.`,
                                    elemento: select,
                                    collapse: collapse,
                                });
                                marcarError(select);
                                if (!primerCampoError)
                                    primerCampoError = select;
                            }

                            if (
                                !monto ||
                                monto.value.trim() === "" ||
                                parseFloat(monto.value) <= 0
                            ) {
                                erroresAgrupados[numeroIndicador].push({
                                    mensaje: `⚠ Acción ${numeroAccion} → Partida ${numeroPartida} con monto inválido.`,
                                    elemento: monto,
                                    collapse: collapse,
                                });
                                marcarError(monto);
                                if (!primerCampoError) primerCampoError = monto;
                            }
                        });
                    },
                );

                /* ===============================
               EVIDENCIAS
            =============================== */

                const bloques = item.querySelectorAll(
                    ".pta-rnew-evidencia-card",
                );

                if (bloques.length === 0) {
                    erroresAgrupados[numeroIndicador].push({
                        mensaje:
                            "⚠ Debe agregar al menos un bloque de evidencia.",
                        elemento: collapse,
                        collapse: collapse,
                    });
                }

                bloques.forEach((bloque, bloqueIndex) => {
                    const numeroBloque = bloqueIndex + 1;
                    const descripcion = bloque.querySelector("textarea");
                    const imagenesNuevas =
                        bloque.querySelectorAll(".imagen-real");
                    const imagenesExistentes = bloque.querySelectorAll(
                        ".imagen-existente-hidden",
                    );

                    const totalImagenes =
                        imagenesNuevas.length + imagenesExistentes.length;

                    if (totalImagenes === 0) {
                        erroresAgrupados[numeroIndicador].push({
                            mensaje: `⚠ Evidencia ${numeroBloque} sin imágenes.`,
                            elemento: bloque,
                            collapse: collapse,
                        });

                        marcarError(descripcion);
                        if (!primerCampoError) primerCampoError = descripcion;
                    }

                    if (imagenes.length === 0) {
                        erroresAgrupados[numeroIndicador].push({
                            mensaje: `⚠ Evidencia ${numeroBloque} sin imágenes.`,
                            elemento: bloque,
                            collapse: collapse,
                        });
                    }
                });
            },
        );

        const indicadoresConError = Object.keys(erroresAgrupados).filter(
            (key) => erroresAgrupados[key].length > 0,
        );

        if (indicadoresConError.length > 0) {
            e.preventDefault();

            mostrarModalErroresAgrupados(erroresAgrupados);

            // Expandir accordion con error
            indicadoresConError.forEach((ind) => {
                const item = root.querySelectorAll(".accordion-item")[ind - 1];
                const collapse = item.querySelector(".accordion-collapse");

                if (!collapse.classList.contains("show")) {
                    new bootstrap.Collapse(collapse, { toggle: true });
                }
            });

            if (primerCampoError) {
                primerCampoError.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
            }

            return false;
        }
    });
}

/* ===============================
   UTILIDADES
=============================== */

function marcarError(input) {
    if (!input) return;
    input.classList.add("campo-error");
}

function limpiarErroresVisuales(root) {
    root.querySelectorAll(".campo-error").forEach((el) => {
        el.classList.remove("campo-error");
    });
}

function mostrarModalErroresAgrupados(erroresAgrupados) {
    const modalElement = document.getElementById("modalErroresReporte");
    const modalInstance = new bootstrap.Modal(modalElement);

    const lista = document.getElementById("listaErroresReporte");
    lista.innerHTML = "";

    Object.keys(erroresAgrupados).forEach((indicador) => {
        if (erroresAgrupados[indicador].length === 0) return;

        const titulo = document.createElement("li");
        titulo.innerHTML = `<strong>🔴 Indicador ${indicador}</strong>`;
        titulo.classList.add("mt-3");
        lista.appendChild(titulo);

        erroresAgrupados[indicador].forEach((error) => {
            const li = document.createElement("li");
            li.style.cursor = "pointer";
            li.innerHTML = `❌ ${error.mensaje}`;

            li.addEventListener("click", function () {
                // 🔥 CERRAR MODAL PRIMERO
                modalInstance.hide();

                // Esperar a que el modal termine animación
                setTimeout(() => {
                    // Expandir accordion si está cerrado
                    if (
                        error.collapse &&
                        !error.collapse.classList.contains("show")
                    ) {
                        new bootstrap.Collapse(error.collapse, {
                            toggle: true,
                        });
                    }

                    // Scroll al campo
                    if (error.elemento) {
                        error.elemento.scrollIntoView({
                            behavior: "smooth",
                            block: "center",
                        });

                        // Opcional: enfoque visual
                        error.elemento.focus();
                    }
                }, 300); // tiempo aproximado animación Bootstrap
            });

            lista.appendChild(li);
        });
    });

    modalInstance.show();
}
