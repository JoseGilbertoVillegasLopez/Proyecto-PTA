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

        // El service ya manda la serie FINAL (valores snapshot por mes).
        // Los meses sin dato vienen como null — se filtran antes de graficar
        // para que la línea no caiga a 0 en meses sin registro.
        const serieObj = JSON.parse(canvas.dataset.serie || "{}");

        const meta       = Number(canvas.dataset.meta || 0);
        const tendencia  = canvas.dataset.tendencia;
        const capturaPct = canvas.dataset.capturaPct === '1';
        const valorBase  = Number(canvas.dataset.valorBase || 0);

        // ── Filtrar solo los meses con valor registrado (no null) ──
        // Así la gráfica termina en el último dato real y no cae a 0
        // en meses futuros o aún sin captura.
        const labelsConDato = [];
        const valoresConDato = [];

        Object.entries(serieObj).forEach(([mes, val]) => {
            if (val !== null && val !== undefined) {
                labelsConDato.push(mes);
                valoresConDato.push(Number(val));
            }
        });

        // Si no hay ningún dato, reemplazar el canvas con un placeholder.
        // Usamos el padre inmediato (.pta-chart-wrap, .pta-card__body o
        // cualquier contenedor) para ser compatibles con graficas Y historial.
        if (labelsConDato.length === 0) {
            const contenedor = canvas.closest('.pta-chart-wrap')
                            ?? canvas.closest('.pta-card__body')
                            ?? canvas.parentElement;
            if (contenedor) {
                contenedor.innerHTML = `
                    <div class="pta-chart-placeholder">
                        <i class="bi bi-graph-up"></i>
                        Sin datos registrados todavía
                    </div>`;
            }
            return;
        }

        // Escala Y:
        //  - capturaEnPorcentaje: eje fijo 0-100 (porcentaje)
        //  - Normal: escala dinámica con 25% de margen
        const maxSerie = Math.max(...valoresConDato, meta);
        const yMax = capturaPct
            ? 100
            : (maxSerie > 0 ? maxSerie * 1.25 : 10);
        const yMin = capturaPct
            ? Math.max(0, Math.min(valorBase, ...valoresConDato) * 0.9)
            : 0;

        const colorAvance =
            tendencia === "POSITIVA"
                ? "rgba(25, 135, 84, 1)"
                : "rgba(220, 53, 69, 1)";

        const colorMeta = "rgba(13, 202, 240, 0.9)";
        // La línea de meta abarca solo los meses con datos registrados
        const metaData = labelsConDato.map(() => meta);

        new Chart(ctx, {
            type: "line",
            data: {
                labels: labelsConDato,
                datasets: [
                    {
                        label: "Avance",
                        data: valoresConDato,
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
                    tooltip: {
                        callbacks: {
                            // Añadir % en el tooltip cuando la captura es porcentual
                            label: (ctx) => {
                                const label = ctx.dataset.label || '';
                                const val   = ctx.parsed.y;
                                return capturaPct
                                    ? ` ${label}: ${val}%`
                                    : ` ${label}: ${val}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: "#adb5bd" },
                        grid: { color: "rgba(255,255,255,0.05)" },
                    },
                    y: {
                        min: yMin,
                        max: yMax,
                        ticks: {
                            color: "#adb5bd",
                            // Añadir símbolo % en el eje cuando la captura es porcentual
                            callback: (value) => capturaPct ? value + "%" : value,
                        },
                        grid: { color: "rgba(255,255,255,0.08)" },
                    },
                },
            },
        });
    });
}
