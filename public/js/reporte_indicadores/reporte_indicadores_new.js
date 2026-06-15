function initReporteIndicadoresNew() {
    const page = document.querySelector('[data-page="reporte-indicadores-new"]');

    if (!page || page.dataset.reporteIndicadoresNewReady === "true") {
        return;
    }

    page.dataset.reporteIndicadoresNewReady = "true";

    page
        .querySelectorAll(".reporte-indicadores-new__trimestre-actions form")
        .forEach((form) => {
            form.addEventListener("submit", () => {
                const card = form.closest(".reporte-indicadores-new__trimestre-card");
                const button = form.querySelector(".reporte-indicadores-new__trimestre-action");

                card?.classList.add("reporte-indicadores-new__trimestre-card--submitting");
                button?.setAttribute("disabled", "disabled");
            });
        });
}

document.addEventListener("DOMContentLoaded", initReporteIndicadoresNew);
document.addEventListener("turbo:load", initReporteIndicadoresNew);
document.addEventListener("turbo:render", initReporteIndicadoresNew);
