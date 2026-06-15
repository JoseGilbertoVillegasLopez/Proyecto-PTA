function initReporteIndicadoresIndex() {
    const page = document.querySelector('[data-page="reporte-indicadores-index"]');

    if (!page || page.dataset.reporteIndicadoresReady === "true") {
        return;
    }

    page.dataset.reporteIndicadoresReady = "true";
}

document.addEventListener("DOMContentLoaded", initReporteIndicadoresIndex);
document.addEventListener("turbo:load", initReporteIndicadoresIndex);
document.addEventListener("turbo:render", initReporteIndicadoresIndex);
