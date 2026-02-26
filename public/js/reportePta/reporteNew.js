/**
 * =====================================================
 * REPORTE PTA — VISTA NEW
 * ACCIONES + SINCRONIZACIÓN COMPLETA
 * =====================================================
 */

function bootReporteNew(context) {

    const reporteRoot = context.querySelector('[data-reporte-form="reporte-new"]');
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

    activarBotonesAgregar(root);
    activarSyncTexto(root);
    initEvidencias(root);
    activarValidacionEnvio(root);
}

/* =====================================================
   BOTÓN AGREGAR ACCIÓN
   ===================================================== */

function activarBotonesAgregar(root) {

    root.querySelectorAll(".add-accion").forEach(btn => {

        btn.addEventListener("click", function () {

            const cardBody = this.closest(".card-body");
            const container = cardBody.querySelector(".acciones-container");

            const indicadorIndex = container.dataset.indicadorIndex;

            let accionIndex = parseInt(container.dataset.accionIndex);
            accionIndex++;

            container.dataset.accionIndex = accionIndex;

            const div = document.createElement("div");
            div.classList.add("accion-item", "mb-3");
            div.dataset.accionIndex = accionIndex;

            div.innerHTML = `
                <label class="form-label">Acción ${accionIndex}*</label>

                <input type="hidden"
                       name="acciones[${indicadorIndex}][${accionIndex}][indice]"
                       value="${accionIndex}">

                <div class="d-flex gap-2">
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

            activarSyncTexto(root);
        });
    });
}

/* =====================================================
   SYNC INICIAL
   ===================================================== */

function syncGastosInicial(root) {

    root.querySelectorAll(".acciones-container").forEach(container => {

        const indicadorIndex = container.dataset.indicadorIndex;

        container.querySelectorAll(".accion-item").forEach(item => {

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
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`
    );

    if (!gastosContainer) return false;

    return !!gastosContainer.querySelector(
        `.card[data-accion-index="${accionIndex}"]`
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

        container.querySelectorAll(".accion-item").forEach(item => {

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
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`
    );

    if (!gastosContainer) return;

    if (existsGastoCard(root, indicadorIndex, accionIndex)) return;

    const procesosEstrategicos = JSON.parse(root.dataset.procesosEstrategicos || "[]");
    const procesosClave = JSON.parse(root.dataset.procesosClave || "[]");

    const card = document.createElement("div");
    card.classList.add("card", "mb-3");
    card.dataset.accionIndex = accionIndex;

    card.innerHTML = `
    <div class="card-header gasto-header">
        Acción ${accionIndex}
    </div>

    <div class="card-body">

        <div class="mb-3">
            <label class="form-label">Proceso Estratégico</label>
            <select class="form-select"
                    name="gastos[${indicadorIndex}][${accionIndex}][proceso_estrategico]">
                <option value="">Seleccione...</option>
                ${procesosEstrategicos.map(p =>
                    `<option value="${p.id}">${p.nombre}</option>`
                ).join("")}
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Proceso Clave</label>
            <select class="form-select"
                    name="gastos[${indicadorIndex}][${accionIndex}][proceso_clave]">
                <option value="">Seleccione...</option>
                ${procesosClave.map(p =>
                    `<option value="${p.id}">${p.nombre}</option>`
                ).join("")}
            </select>
        </div>

        <hr>

        <div class="partidas-container"
             data-indicador-index="${indicadorIndex}"
             data-accion-index="${accionIndex}"
             data-partida-index="0">
        </div>

        <button type="button"
                class="btn btn-sm btn-outline-success add-partida mt-2">
            Agregar Partida
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
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`
    );

    if (!gastosContainer) return;

    const card = gastosContainer.querySelector(
        `.card[data-accion-index="${accionIndex}"]`
    );

    if (card) card.remove();
}

/* =====================================================
   REORDENAR GASTOS
   ===================================================== */

function reorderGastos(root, indicadorIndex) {

    const gastosContainer = root.querySelector(
        `.gastos-container[data-indicador-index="${indicadorIndex}"]`
    );

    if (!gastosContainer) return;

    let contador = 1;

    gastosContainer.querySelectorAll(".card").forEach(card => {

        card.dataset.accionIndex = contador;

        const header = card.querySelector(".gasto-header");
        const inputAccion = root.querySelector(
            `.acciones-container[data-indicador-index="${indicadorIndex}"] 
             .accion-item[data-accion-index="${contador}"] input[type="text"]`
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

        contador++;
    });

    // Reordenar partidas internas también
const partidasContainer = card.querySelector(".partidas-container");

if (partidasContainer) {

    let partidaContador = 1;

    partidasContainer.querySelectorAll("[data-partida-index]").forEach(item => {

        item.dataset.partidaIndex = partidaContador;

        const select = item.querySelector("select");
        const input = item.querySelector("input");

        if (select) {
            select.name =
                `gastos[${indicadorIndex}][${contador}][partidas][${partidaContador}][partida_id]`;
        }

        if (input) {
            input.name =
                `gastos[${indicadorIndex}][${contador}][partidas][${partidaContador}][monto]`;
        }

        partidaContador++;
    });

    partidasContainer.dataset.partidaIndex = partidaContador - 1;
}
}

/* =====================================================
   SINCRONIZAR TEXTO ACCIÓN → HEADER GASTO
   ===================================================== */

function activarSyncTexto(root) {

    root.querySelectorAll(".acciones-container").forEach(container => {

        const indicadorIndex = container.dataset.indicadorIndex;

        container.querySelectorAll(".accion-item").forEach(item => {

            const accionIndex = item.dataset.accionIndex;
            const input = item.querySelector('input[type="text"]');

            if (!input) return;

            input.addEventListener("input", function () {

                const gastosContainer = root.querySelector(
                    `.gastos-container[data-indicador-index="${indicadorIndex}"]`
                );

                if (!gastosContainer) return;

                const card = gastosContainer.querySelector(
                    `.card[data-accion-index="${accionIndex}"]`
                );

                if (!card) return;

                const header = card.querySelector(".gasto-header");

                const texto = input.value.trim();

                header.textContent = texto.length > 0
                    ? texto
                    : `Acción ${accionIndex}`;
            });

        });

    });
}


function createPartida(root, indicadorIndex, accionIndex) {

    const partidasContainer = root.querySelector(
        `.partidas-container[data-indicador-index="${indicadorIndex}"][data-accion-index="${accionIndex}"]`
    );

    if (!partidasContainer) return;

    let partidaIndex = parseInt(partidasContainer.dataset.partidaIndex);
    partidaIndex++;

    partidasContainer.dataset.partidaIndex = partidaIndex;

    const partidas = JSON.parse(root.dataset.partidasPresupuestales || "[]");

    const div = document.createElement("div");
    div.classList.add("border", "p-2", "mb-2");
    div.dataset.partidaIndex = partidaIndex;

    div.innerHTML = `
        <div class="row g-2">

            <div class="col-md-6">
                <label class="form-label">Partida Presupuestal</label>
                <select class="form-select"
                        name="gastos[${indicadorIndex}][${accionIndex}][partidas][${partidaIndex}][partida_id]">
                    <option value="">Seleccione...</option>
                    ${partidas.map(p =>
                        `<option value="${p.id}">
                            ${p.capitulo} - ${p.partida} - ${p.descripcion}
                        </option>`
                    ).join("")}
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

        container.querySelectorAll("[data-partida-index]").forEach(item => {

            item.dataset.partidaIndex = contador;

            const select = item.querySelector("select");
            const input = item.querySelector("input");

            if (select) {
                select.name =
                    `gastos[${indicadorIndex}][${accionIndex}][partidas][${contador}][partida_id]`;
            }

            if (input) {
                input.name =
                    `gastos[${indicadorIndex}][${accionIndex}][partidas][${contador}][monto]`;
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

    root.querySelectorAll(".add-evidencia").forEach(btn => {

        btn.addEventListener("click", function () {

            const cardBody = this.closest(".card-body");
            const container = cardBody.querySelector(".evidencias-container");

            if (!container) return;

            const indicadorIndex = container.dataset.indicadorIndex;

            let bloqueIndex = parseInt(container.dataset.bloqueIndex);

            if (bloqueIndex >= 3) {
                alert("Máximo 3 bloques de evidencia permitidos.");
                return;
            }

            bloqueIndex++;
            container.dataset.bloqueIndex = bloqueIndex;

            const bloque = document.createElement("div");
            bloque.classList.add("evidencia-bloque", "border", "p-3", "mb-3");
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

        container.querySelectorAll(".evidencia-bloque").forEach(item => {

            item.dataset.bloqueIndex = contador;

            item.querySelector("strong").textContent = `Evidencia ${contador}`;

            const textarea = item.querySelector("textarea");

            textarea.name =
                `evidencias[${indicadorIndex}][${contador}][descripcion]`;

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

    if (container.querySelectorAll(".evidencia-card").length >= 4) return;

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

        const imagenIndex = container.querySelectorAll(".imagen-real").length + 1;

        input.name = `evidencias[${indicadorIndex}][${bloqueIndex}][imagenes][]`;
        input.classList.add("imagen-real");

        mostrarPreview(container, file, input);

        card.remove();
        card.classList.remove("add-button");
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

        const imagenesActuales = container.querySelectorAll(".imagen-real").length;
        const existeBotonMas = container.querySelector(".evidencia-card.add-button");

        if (imagenesActuales < 4 && !existeBotonMas) {
            renderBotonAgregar(container, input.name.match(/\[(\d+)\]\[(\d+)\]/)[1], input.name.match(/\[(\d+)\]\[(\d+)\]/)[2]);
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
   VALIDACIÓN FRONTEND REPORTE
   ===================================================== */

function activarValidacionEnvio(root) {

    const form = root.closest("form");
    if (!form) return;

    form.addEventListener("submit", function (e) {

        limpiarErroresVisuales(root);

        const errores = [];
        let primerCampoError = null;

        /* ===============================
           VALIDAR CAMPOS DEL INDICADOR
        =============================== */

        root.querySelectorAll("[name^='reporte[indicadores]']").forEach(input => {

            if (input.tagName === "SELECT" || input.tagName === "INPUT" || input.tagName === "TEXTAREA") {

                if (input.value.trim() === "") {

                    marcarError(input);

                    if (!primerCampoError) {
                        primerCampoError = input;
                    }

                    errores.push("Hay campos del indicador sin completar.");
                }
            }
        });

        /* ===============================
           VALIDAR ACCIONES
        =============================== */

        root.querySelectorAll(".acciones-container").forEach(container => {

            const acciones = container.querySelectorAll(".accion-item");

            if (acciones.length === 0) {
                errores.push("Debe agregar al menos una acción.");
            }

            acciones.forEach(accion => {

                const descripcion = accion.querySelector("input");

                if (!descripcion || descripcion.value.trim() === "") {

                    marcarError(descripcion);

                    if (!primerCampoError) {
                        primerCampoError = descripcion;
                    }

                    errores.push("Hay acciones sin descripción.");
                }
            });
        });

        /* ===============================
           VALIDAR PARTIDAS
        =============================== */

        root.querySelectorAll(".partidas-container").forEach(container => {

            const partidas = container.querySelectorAll("[data-partida-index]");

            if (partidas.length === 0) {
                errores.push("Debe agregar al menos una partida.");
            }

            partidas.forEach(partida => {

                const select = partida.querySelector("select");
                const monto = partida.querySelector("input");

                if (!select || select.value === "") {
                    marcarError(select);
                    if (!primerCampoError) primerCampoError = select;
                    errores.push("Seleccione partida presupuestal.");
                }

                if (!monto || monto.value === "" || parseFloat(monto.value) <= 0) {
                    marcarError(monto);
                    if (!primerCampoError) primerCampoError = monto;
                    errores.push("Monto inválido en partida.");
                }
            });
        });

        /* ===============================
           VALIDAR EVIDENCIAS
        =============================== */

        root.querySelectorAll(".evidencia-bloque").forEach(bloque => {

            const descripcion = bloque.querySelector("textarea");
            const imagenes = bloque.querySelectorAll(".imagen-real");

            if (!descripcion || descripcion.value.trim() === "") {
                marcarError(descripcion);
                if (!primerCampoError) primerCampoError = descripcion;
                errores.push("Descripción de evidencia obligatoria.");
            }

            if (imagenes.length === 0) {
                errores.push("Cada evidencia debe tener al menos una imagen.");
            }
        });

        /* ===============================
           FINAL
        =============================== */

        if (errores.length > 0) {

            e.preventDefault();

            mostrarModalErrores(errores);

            if (primerCampoError) {
                primerCampoError.scrollIntoView({ behavior: "smooth", block: "center" });
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
    root.querySelectorAll(".campo-error").forEach(el => {
        el.classList.remove("campo-error");
    });
}

function mostrarModalErrores(errores) {

    const lista = document.getElementById("listaErroresReporte");
    lista.innerHTML = "";

    const erroresUnicos = [...new Set(errores)];

    erroresUnicos.forEach(error => {
        const li = document.createElement("li");
        li.textContent = error;
        lista.appendChild(li);
    });

    const modal = new bootstrap.Modal(document.getElementById("modalErroresReporte"));
    modal.show();
}