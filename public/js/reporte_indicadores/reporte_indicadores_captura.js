(function () {
    if (window.__reporteIndicadoresCapturaDelegado === true) {
        return;
    }

    window.__reporteIndicadoresCapturaDelegado = true;

    function getPage() {
        return document.querySelector('[data-page="reporte-indicadores-captura"]');
    }

    function getCards(page) {
        return [...page.querySelectorAll("[data-reporte-indicadores-captura-card]")];
    }

    function getEvidenceCount(card) {
        return card.querySelectorAll(".reporte-indicadores-captura__preview-item").length;
    }

    function isCardComplete(card) {
        const accion = card.querySelector("[name$='[accion]']")?.value.trim();
        const indicador = card.querySelector("[name$='[indicadorBasico]']")?.value;
        const descripcion = card.querySelector("[name$='[descripcion]']")?.value.trim();
        const evidenceCount = getEvidenceCount(card);

        return Boolean(accion && indicador && descripcion && evidenceCount >= 1 && evidenceCount <= 5);
    }

    function refreshPage(page) {
        const addButton = page.querySelector("[data-reporte-indicadores-captura-add]");
        const cards = getCards(page);

        if (addButton) {
            addButton.disabled = cards.length === 0;
        }

        cards.forEach((card) => {
            const addTile = card.querySelector("[data-reporte-indicadores-captura-add-file]");

            if (addTile) {
                addTile.hidden = getEvidenceCount(card) >= 5;
            }
        });
    }

    function clearCardError(card) {
        card.classList.remove("reporte-indicadores-captura__activity-card--invalid");
        card.querySelector(".reporte-indicadores-captura__card-error")?.remove();
    }

    function showCardError(card) {
        clearCardError(card);
        card.classList.add("reporte-indicadores-captura__activity-card--invalid");

        const error = document.createElement("div");
        error.className = "reporte-indicadores-captura__card-error";
        error.textContent = "Completa actividad, indicador, descripcion y minimo una evidencia antes de agregar otra actividad.";

        card.appendChild(error);
    }

    function showPreviewError(preview, message) {
        preview.querySelector(".reporte-indicadores-captura__file-error")?.remove();

        const error = document.createElement("div");
        error.className = "reporte-indicadores-captura__file-error";
        error.textContent = message;

        preview.prepend(error);
    }

    function buildPreviewTile(file, input, page) {
        const item = document.createElement("div");
        item.className = "reporte-indicadores-captura__preview-item";

        const url = URL.createObjectURL(file);

        if (file.type.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = url;
            img.alt = file.name;
            item.appendChild(img);
        } else {
            const iframe = document.createElement("iframe");
            iframe.src = `${url}#toolbar=0&navpanes=0`;
            iframe.title = file.name;
            item.appendChild(iframe);
        }

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "reporte-indicadores-captura__preview-remove";
        removeButton.title = "Quitar evidencia";
        removeButton.innerHTML = '<i class="bi bi-x-lg"></i>';
        removeButton.addEventListener("click", () => {
            input.remove();
            item.remove();
            refreshPage(page);
        });

        const name = document.createElement("span");
        name.textContent = file.name;

        item.appendChild(removeButton);
        item.appendChild(name);

        return item;
    }

    function openEvidencePicker(card, page) {
        const preview = card.querySelector("[data-reporte-indicadores-captura-preview]");
        const inputName = preview?.dataset.reporteIndicadoresCapturaInputName;

        if (!preview || !inputName) {
            return;
        }

        if (getEvidenceCount(card) >= 5) {
            showPreviewError(preview, "Maximo 5 evidencias por actividad.");
            return;
        }

        const picker = document.createElement("input");
        picker.type = "file";
        picker.name = inputName;
        picker.accept = "image/*,application/pdf";
        picker.className = "reporte-indicadores-captura__file-hidden";
        picker.setAttribute("data-reporte-indicadores-captura-hidden-file", "true");

        picker.addEventListener("change", () => {
            const file = picker.files?.[0];

            if (!file) {
                picker.remove();
                return;
            }

            if (!file.type.startsWith("image/") && file.type !== "application/pdf") {
                picker.remove();
                showPreviewError(preview, "Solo se permiten imagenes o PDF.");
                return;
            }

            const addTile = preview.querySelector("[data-reporte-indicadores-captura-add-file]");
            const tile = buildPreviewTile(file, picker, page);

            preview.insertBefore(tile, addTile);
            preview.appendChild(picker);
            clearCardError(card);
            refreshPage(page);
        });

        preview.appendChild(picker);
        picker.click();
    }

    function addActivityCard(page) {
        const list = page.querySelector("[data-reporte-indicadores-captura-list]");
        const template = page.querySelector("[data-reporte-indicadores-captura-template]");

        if (!list || !template) {
            return;
        }

        const cards = getCards(page);
        const incompleteCard = cards.find((card) => !isCardComplete(card));

        if (incompleteCard) {
            showCardError(incompleteCard);
            incompleteCard.scrollIntoView({ behavior: "smooth", block: "center" });
            refreshPage(page);
            return;
        }

        const index = cards.length;
        const html = template.innerHTML
            .replaceAll("__INDEX__", String(index))
            .replaceAll("__NUMBER__", String(index + 1));

        list.insertAdjacentHTML("beforeend", html);
        list
            .querySelector("[data-reporte-indicadores-captura-card]:last-child")
            ?.scrollIntoView({ behavior: "smooth", block: "center" });

        refreshPage(page);
    }

    document.addEventListener("click", (event) => {
        const page = getPage();

        if (!page) {
            return;
        }

        const addFileButton = event.target.closest("[data-reporte-indicadores-captura-add-file]");
        if (addFileButton && page.contains(addFileButton)) {
            event.preventDefault();
            const card = addFileButton.closest("[data-reporte-indicadores-captura-card]");
            if (card) {
                openEvidencePicker(card, page);
            }
            return;
        }

        const removeExistingButton = event.target.closest("[data-reporte-indicadores-captura-remove-existing]");
        if (removeExistingButton && page.contains(removeExistingButton)) {
            event.preventDefault();

            const card = removeExistingButton.closest("[data-reporte-indicadores-captura-card]");
            const item = removeExistingButton.closest(".reporte-indicadores-captura__preview-item");
            const evidenciaId = removeExistingButton.dataset.evidenciaId;
            const inputName = removeExistingButton.dataset.inputName;

            if (card && evidenciaId && inputName) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = inputName;
                input.value = evidenciaId;
                card.appendChild(input);
                item?.remove();
                clearCardError(card);
                refreshPage(page);
            }
            return;
        }

        const addActivityButton = event.target.closest("[data-reporte-indicadores-captura-add]");
        if (addActivityButton && page.contains(addActivityButton)) {
            event.preventDefault();
            addActivityCard(page);
        }
    });

    document.addEventListener("input", (event) => {
        const page = getPage();
        const card = event.target.closest?.("[data-reporte-indicadores-captura-card]");

        if (page && card && page.contains(card)) {
            clearCardError(card);
            refreshPage(page);
        }
    });

    document.addEventListener("change", (event) => {
        const page = getPage();
        const card = event.target.closest?.("[data-reporte-indicadores-captura-card]");

        if (page && card && page.contains(card)) {
            clearCardError(card);
            refreshPage(page);
        }
    });

    document.addEventListener("submit", (event) => {
        const page = getPage();

        if (!page || !event.target.matches("[data-reporte-indicadores-captura-form]")) {
            return;
        }

        const cards = getCards(page);
        const incompleteCard = cards.find((card) => !isCardComplete(card));

        if (cards.length === 0 || incompleteCard) {
            event.preventDefault();
            if (incompleteCard) {
                showCardError(incompleteCard);
                incompleteCard.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            refreshPage(page);
        }
    });

    document.addEventListener("DOMContentLoaded", () => {
        const page = getPage();
        if (page) {
            refreshPage(page);
        }
    });

    document.addEventListener("turbo:load", () => {
        const page = getPage();
        if (page) {
            refreshPage(page);
        }
    });

    document.addEventListener("turbo:render", () => {
        const page = getPage();
        if (page) {
            refreshPage(page);
        }
    });
})();
