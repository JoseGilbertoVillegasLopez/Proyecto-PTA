/**
 * =====================================================
 * PTA — VISTA GRÁFICAS (FINAL)
 * -----------------------------------------------------
 * ✔ Turbo compatible
 * ✔ Evita doble inicialización
 * ✔ Escala Y dinámica (NO gráficas aplastadas)
 * ✔ El service YA MANDA LA SERIE FINAL
 * =====================================================
 */

/* =====================================================
 * BOOT UNIVERSAL
 * ===================================================== */
function bootPtaGraficas(context) {
    const roots = context.querySelectorAll('[data-pta-view="graficas"]');
    if (!roots.length) return;

    roots.forEach(root => {

        if (root.dataset.ptaInitialized === "true") return;

        const hasChart = () => typeof window.Chart !== "undefined";

        const isVisible = (el) => {
            if (!el || el.offsetParent === null) return false;
            const r = el.getBoundingClientRect();
            return r.width > 0 && r.height > 0;
        };

        const ready = () => {
            if (!hasChart()) return false;
            const canvas = root.querySelector(".pta-chart");
            if (!canvas) return false;
            if (!isVisible(canvas) || !isVisible(canvas.parentElement)) return false;
            return canvas.clientWidth > 0 && canvas.clientHeight > 0;
        };

        const tryInit = () => {
            if (!ready()) {
                requestAnimationFrame(tryInit);
                return;
            }
            root.dataset.ptaInitialized = "true";
            initPtaGraficas(root);
        };

        tryInit();
    });
}

/* =====================================================
 * EVENTOS UNIVERSALES
 * ===================================================== */
document.addEventListener("turbo:frame-load", (e) => {
    if (e.target?.id === "content") bootPtaGraficas(e.target);
});
document.addEventListener("turbo:load", () => bootPtaGraficas(document));
document.addEventListener("DOMContentLoaded", () => bootPtaGraficas(document));

/* =====================================================
 * LIMPIEZA PARA TURBO CACHE
 * ===================================================== */
document.addEventListener("turbo:before-cache", () => {
    document.querySelectorAll('.pta-chart').forEach((canvas) => {
        const chart = Chart.getChart(canvas);
        if (chart) chart.destroy();
    });
    document.querySelectorAll('[data-pta-view="graficas"]').forEach(r => {
        r.dataset.ptaInitialized = "false";
    });
});

/* =====================================================
 * INIT PRINCIPAL
 * ===================================================== */
function initPtaGraficas(root) {
    const charts = root.querySelectorAll(".pta-chart");

    charts.forEach((canvas) => {
        const prev = Chart.getChart(canvas);
        if (prev) prev.destroy();

        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        // 🔥 El service ya manda la serie FINAL
        const serieObj = JSON.parse(canvas.dataset.serie || "{}");

        const labels  = Object.keys(serieObj);
        const valores = Object.values(serieObj).map(v => Number(v));

        const meta      = Number(canvas.dataset.meta || 0);
        const tendencia = canvas.dataset.tendencia;

        const maxSerie = Math.max(...valores, meta);
        const yMax = maxSerie > 0 ? maxSerie * 1.25 : 10;

        const colorAvance =
            tendencia === "POSITIVA"
                ? "rgba(25, 135, 84, 1)"
                : "rgba(220, 53, 69, 1)";

        const colorMeta = "rgba(13, 202, 240, 0.9)";
        const metaData = labels.map(() => meta);

        new Chart(ctx, {
            type: "line",
            data: {
                labels,
                datasets: [
                    {
                        label: "Avance",
                        data: valores,
                        borderColor: colorAvance,
                        backgroundColor: colorAvance,
                        tension: 0,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: false,
                    },
                    {
                        label: "Meta",
                        data: metaData,
                        borderColor: colorMeta,
                        borderDash: [6, 6],
                        borderWidth: 2,
                        pointRadius: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: "#e9ecef",
                            font: { weight: "500" },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: "#adb5bd" },
                        grid: { color: "rgba(255,255,255,0.05)" },
                    },
                    y: {
                        min: 0,
                        max: yMax,
                        ticks: { color: "#adb5bd" },
                        grid: { color: "rgba(255,255,255,0.08)" },
                    },
                },
            },
        });
    });
}
