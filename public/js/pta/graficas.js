/**
 * =====================================================
 * PTA — VISTA GRÁFICAS
 * -----------------------------------------------------
 * - Funciona en:
 *   ✅ Admin (Turbo Frame dentro del dashboard)
 *   ✅ No-admin con Turbo Drive (turbo:load)
 *   ✅ No-admin sin Turbo (DOMContentLoaded)
 *
 * CLAVE:
 * - NO inicializa Chart.js hasta que:
 *   1) exista Chart (window.Chart)
 *   2) exista el root [data-pta-view="graficas"]
 *   3) el canvas tenga tamaño real (no colapsado)
 * =====================================================
 */

/**
 * =====================================================
 * BOOT UNIVERSAL — PTA GRÁFICAS
 * =====================================================
 */
function bootPtaGraficas(context) {
    const root = context.querySelector('[data-pta-view="graficas"]');
    if (!root) return;

    // 🛑 evitar doble init por eventos múltiples
    if (root.dataset.ptaInitialized === "true") return;

    // Helpers
    const hasChartLib = () => typeof window.Chart !== "undefined";

    const isVisibleWithSize = (el) => {
        if (!el) return false;
        // offsetParent null suele significar display:none o no insertado/visible
        if (el.offsetParent === null) return false;

        const rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    };

    const checkReady = () => {
        // 1) Chart.js cargado
        if (!hasChartLib()) return false;

        // 2) Al menos un canvas
        const canvas = root.querySelector(".pta-chart");
        if (!canvas) return false;

        // 3) El canvas y su contenedor ya tienen tamaño real
        const parent = canvas.parentElement;
        if (!isVisibleWithSize(parent)) return false;
        if (!isVisibleWithSize(canvas)) return false;

        // 4) Chart.js responsive necesita layout estable
        //    (a veces parent tiene height pero canvas aún 0)
        if (canvas.clientHeight <= 0 || canvas.clientWidth <= 0) return false;

        return true;
    };

    const tryInit = () => {
        if (!checkReady()) {
            requestAnimationFrame(tryInit);
            return;
        }

        root.dataset.ptaInitialized = "true";
        console.log("📊 Gráficas PTA inicializadas correctamente");
        initPtaGraficas(root);
    };

    tryInit();
}

/**
 * =====================================================
 * EVENTOS UNIVERSALES (igual que NEW)
 * =====================================================
 */

// ✅ Admin (dashboard): cuando se carga el frame content
document.addEventListener("turbo:frame-load", (event) => {
    const frame = event.target;
    if (!frame || frame.id !== "content") return;
    bootPtaGraficas(frame);
});

// ✅ No-admin con Turbo Drive (navegación sin recargar página)
document.addEventListener("turbo:load", () => {
    bootPtaGraficas(document);
});

// ✅ Fallback (si algún día Turbo no está activo)
document.addEventListener("DOMContentLoaded", () => {
    bootPtaGraficas(document);
});

/**
 * =====================================================
 * LIMPIEZA PARA TURBO CACHE
 * -----------------------------------------------------
 * Si Turbo cachea la página, al volver puede romper tamaños
 * o duplicar instancias. Aquí destruimos antes de cachear.
 * =====================================================
 */
document.addEventListener("turbo:before-cache", () => {
    document.querySelectorAll('[data-pta-view="graficas"] .pta-chart').forEach((canvas) => {
        // Chart.js v3/v4: Chart.getChart(canvas) existe
        try {
            const chart = (window.Chart && typeof window.Chart.getChart === "function")
                ? window.Chart.getChart(canvas)
                : canvas.chartInstance;

            if (chart && typeof chart.destroy === "function") chart.destroy();
        } catch (_) {}

        // limpiar marcas
        canvas.chartInstance = null;
    });

    document.querySelectorAll('[data-pta-view="graficas"]').forEach((root) => {
        root.dataset.ptaInitialized = "false";
    });
});

/**
 * =====================================================
 * FUNCIÓN PRINCIPAL DE INICIALIZACIÓN
 * =====================================================
 */
function initPtaGraficas(root) {
    const charts = root.querySelectorAll(".pta-chart");

    if (!charts.length) {
        console.warn("⚠️ No se encontraron gráficas");
        return;
    }

    charts.forEach((canvas) => {
        // Destruir instancia previa (por si acaso)
        try {
            const prev = (window.Chart && typeof window.Chart.getChart === "function")
                ? window.Chart.getChart(canvas)
                : canvas.chartInstance;

            if (prev && typeof prev.destroy === "function") prev.destroy();
        } catch (_) {}

        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        const mesesObj = JSON.parse(canvas.dataset.meses || "{}");

        const labels = Object.keys(mesesObj);
        const valores = Object.values(mesesObj).map((v) => Number(v));

        const meta = Number(canvas.dataset.meta);
        const tendencia = canvas.dataset.tendencia;

        const colorLinea =
            tendencia === "POSITIVA"
                ? "rgba(25, 135, 84, 1)"
                : "rgba(220, 53, 69, 1)";

        const colorMeta = "rgba(13, 202, 240, 0.85)";
        const metaData = labels.map(() => meta);

        // Asegura que el canvas tenga height real antes de crear chart
        // (por si el CSS tarda en aplicar)
        if (canvas.clientHeight <= 0) {
            canvas.style.height = "300px";
        }

        // Crear chart
        const instance = new Chart(ctx, {
            type: "line",
            data: {
                labels,
                datasets: [
                    {
                        label: "Avance",
                        data: valores,
                        borderColor: colorLinea,
                        backgroundColor: colorLinea,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: "Meta",
                        data: metaData,
                        borderColor: colorMeta,
                        borderDash: [6, 6],
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
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: "#adb5bd" },
                        grid: { color: "rgba(255,255,255,0.05)" },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: "#adb5bd" },
                        grid: { color: "rgba(255,255,255,0.08)" },
                    },
                },
            },
        });

        // Guardar referencia (fallback)
        canvas.chartInstance = instance;
    });
}
