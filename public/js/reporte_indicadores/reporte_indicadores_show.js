function initReporteIndicadoresShow() {
    const page = document.querySelector('[data-page="reporte-indicadores-show"]');

    if (!page || page.dataset.reporteIndicadoresShowReady === "true") {
        return;
    }

    page.dataset.reporteIndicadoresShowReady = "true";

    page.querySelectorAll("[data-reporte-indicadores-show-toggle]").forEach((header) => {
        header.addEventListener("click", () => {
            header
                .closest(".reporte-indicadores-show__activity-card")
                ?.classList.toggle("reporte-indicadores-show__activity-card--collapsed");
        });
    });
}

document.addEventListener("DOMContentLoaded", initReporteIndicadoresShow);
document.addEventListener("turbo:load", initReporteIndicadoresShow);
document.addEventListener("turbo:render", initReporteIndicadoresShow);
