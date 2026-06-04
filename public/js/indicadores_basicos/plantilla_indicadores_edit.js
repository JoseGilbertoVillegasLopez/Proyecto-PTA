(function () {
    function parseNumber(value) {
        if (value === null || value === undefined || value === "") {
            return null;
        }

        const normalized = String(value).replace(",", ".");
        const number = Number(normalized);

        return Number.isFinite(number) ? number : null;
    }

    function normalizeComparable(value) {
        const number = parseNumber(value);

        if (number === null) {
            return "";
        }

        return number.toFixed(2);
    }

    function formatPercentage(value) {
        return `${value.toFixed(2)}%`;
    }

    function setCellRecent(cell, isRecent) {
        if (!cell) {
            return;
        }

        cell.classList.toggle("plantilla-indicadores-cell--recent", isRecent);
    }

    function syncInputRecent(input) {
        const original = normalizeComparable(input.dataset.originalValue || "");
        const current = normalizeComparable(input.value);

        setCellRecent(input.closest("td"), original !== current);
    }

    function escapeSelectorValue(value) {
        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\]/g, "\\$&");
    }

    function updateResult(form, indicadorId, ciclo) {
        const selectorBase = `[data-indicador-id="${escapeSelectorValue(indicadorId)}"][data-ciclo="${escapeSelectorValue(ciclo)}"]`;
        const cantidad1Input = form.querySelector(`${selectorBase}[data-cantidad="1"]`);
        const cantidad2Input = form.querySelector(`${selectorBase}[data-cantidad="2"]`);
        const resultCell = form.querySelector(`${selectorBase}[data-live-ciclo-result]`);

        if (!cantidad1Input || !cantidad2Input || !resultCell) {
            return;
        }

        const cantidad1 = parseNumber(cantidad1Input.value);
        const cantidad2 = parseNumber(cantidad2Input.value);

        if (cantidad1 === null || cantidad2 === null) {
            resultCell.textContent = "";
            resultCell.classList.remove("plantilla-indicadores-result-cell--live");
            setCellRecent(resultCell, (resultCell.dataset.originalResult || "") !== "");
            return;
        }

        if (cantidad1 === 0 && cantidad2 === 0) {
            resultCell.textContent = "0.00%";
            resultCell.classList.add("plantilla-indicadores-result-cell--live");
            setCellRecent(resultCell, (resultCell.dataset.originalResult || "") !== "0.00%");
            return;
        }

        if (cantidad2 === 0) {
            resultCell.textContent = "";
            resultCell.classList.remove("plantilla-indicadores-result-cell--live");
            setCellRecent(resultCell, (resultCell.dataset.originalResult || "") !== "");
            return;
        }

        const formattedResult = formatPercentage((cantidad1 / cantidad2) * 100);

        resultCell.textContent = formattedResult;
        resultCell.classList.add("plantilla-indicadores-result-cell--live");
        setCellRecent(resultCell, (resultCell.dataset.originalResult || "") !== formattedResult);
    }

    function initPlantillaIndicadoresEdit() {
        const form = document.getElementById("plantilla-indicadores-edit-form");

        if (!form || form.dataset.liveCalculationReady === "true") {
            return;
        }

        form.dataset.liveCalculationReady = "true";

        form.querySelectorAll("[data-live-ciclo-input]").forEach((input) => {
            input.addEventListener("wheel", (event) => {
                event.preventDefault();
            }, { passive: false });

            input.addEventListener("input", () => {
                syncInputRecent(input);
                updateResult(form, input.dataset.indicadorId, input.dataset.ciclo);
            });
        });

        form.querySelectorAll("[data-track-modified]:not([data-live-ciclo-input])").forEach((input) => {
            input.addEventListener("wheel", (event) => {
                event.preventDefault();
            }, { passive: false });

            input.addEventListener("input", () => {
                syncInputRecent(input);
            });
        });
    }

    document.addEventListener("DOMContentLoaded", initPlantillaIndicadoresEdit);
    document.addEventListener("turbo:frame-load", initPlantillaIndicadoresEdit);
    document.addEventListener("turbo:render", initPlantillaIndicadoresEdit);
})();
