document.addEventListener("turbo:frame-load", (event) => {
    // Solo ejecutarlo si el frame cargado es el de PTA
    const frame = event.target;

    if (frame.id !== "content") {
        return; // no es el frame donde está el formulario
    }

    console.log("PTA frame loaded ✔");

    const addButton = frame.querySelector("#add-indicador");
    const collectionHolder = frame.querySelector("[data-collection-holder='indicadores']");

    console.log("Botón:", addButton);
    console.log("Tabla:", collectionHolder);

    if (!addButton || !collectionHolder) {
        return;
    }

    // Inicializar contador
    collectionHolder.dataset.index = collectionHolder.querySelectorAll("tr").length;

    addButton.addEventListener("click", () => {
        const index = collectionHolder.dataset.index;
        const prototype = collectionHolder.dataset.prototype;

        const newForm = prototype.replace(/__name__/g, index);

        const row = document.createElement("tr");
        row.classList.add("indicator-row");

        // Convertir el prototype a td's reales
        const temp = document.createElement("div");
        temp.innerHTML = newForm;

        // Crear las celdas manualmente
        row.innerHTML = `
            <td class="p-2">${temp.querySelector('[name$="[indicador]"]').outerHTML}</td>
            <td class="p-2">${temp.querySelector('[name$="[formula]"]').outerHTML}</td>
            <td class="p-2">${temp.querySelector('[name$="[valor]"]').outerHTML}</td>
            <td class="p-2">${temp.querySelector('[name$="[periodo]"]').outerHTML}</td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-indicador">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;


        collectionHolder.appendChild(row);
        collectionHolder.dataset.index++;

        activateRemoveButtons(frame);
    });

    function activateRemoveButtons(root) {
        root.querySelectorAll(".remove-indicador").forEach(btn => {
            btn.onclick = () => btn.closest("tr").remove();
        });
    }

    activateRemoveButtons(frame);
});
