// =====================================
// ACCIONES DINÁMICAS
// =====================================
document.addEventListener("turbo:frame-load", (event) => {
    const frame = event.target;
    if (frame.id !== "content") return;

    console.log("PTA Acciones JS cargado ✔");

    const addButton = frame.querySelector("#add-accion");
    const collectionHolder = frame.querySelector("[data-collection-holder='acciones']");

    console.log("Botón Accion:", addButton);
    console.log("Tabla Accion:", collectionHolder);

    if (!addButton || !collectionHolder) return;

    // inicializar índice
    collectionHolder.dataset.index = collectionHolder.querySelectorAll("tr").length;

    addButton.addEventListener("click", () => {
        const index = collectionHolder.dataset.index;
        const prototype = collectionHolder.dataset.prototype;

        // Crear fila temporal
        const temp = document.createElement("div");
        temp.innerHTML = prototype.replace(/__name__/g, index);

        const row = document.createElement("tr");
        row.classList.add("accion-row");

        // Inputs del prototype
        const accionInput = temp.querySelector('[name$="[accion]"]');
        const mesesInput = temp.querySelectorAll('[type="checkbox"]');

        // Crear estructura de la fila
        row.innerHTML = `
            <td class="p-2">${accionInput.outerHTML}</td>
            <td class="p-2 meses-col"></td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-accion">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        // Insertar los meses con nombres visibles
        const mesesTd = row.querySelector(".meses-col");
        const nombresMeses = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];

        mesesInput.forEach((mes, index) => {
            const wrapper = document.createElement("label");
            wrapper.classList.add("mes-label");
            wrapper.style.display = "inline-flex";
            wrapper.style.alignItems = "center";
            wrapper.style.marginRight = "12px";
            wrapper.style.cursor = "pointer";

            // Texto del mes
            const span = document.createElement("span");
            span.textContent = nombresMeses[index];
            span.style.marginLeft = "4px";

            wrapper.appendChild(mes);
            wrapper.appendChild(span);
            mesesTd.appendChild(wrapper);
        });

        // Agregar fila final
        collectionHolder.appendChild(row);
        collectionHolder.dataset.index++;

        activateRemoveAccionButtons(frame);
    });

    function activateRemoveAccionButtons(root) {
        root.querySelectorAll(".remove-accion").forEach(btn => {
            btn.onclick = () => btn.closest("tr").remove();
        });
    }

    activateRemoveAccionButtons(frame);
});
