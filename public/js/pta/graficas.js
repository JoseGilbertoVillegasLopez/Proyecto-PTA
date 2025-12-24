/**
 * =====================================================
 * PTA — VISTA GRÁFICAS
 * - Compatible con Turbo
 * - Los valores YA vienen calculados desde PHP
 * =====================================================
 */

document.addEventListener('turbo:frame-load', (event) => {

    const frame = event.target;

    if (frame.id !== 'content') return;

    const viewRoot = frame.querySelector('[data-pta-view="graficas"]');
    if (!viewRoot) return;

    console.log('📊 Vista de gráficas PTA detectada');

    initPtaGraficas(viewRoot);
});

function initPtaGraficas(root) {

    const charts = root.querySelectorAll('.pta-chart');
    if (!charts.length) {
        console.warn('⚠️ No se encontraron gráficas');
        return;
    }

    charts.forEach(canvas => {

        if (canvas.chartInstance) {
            canvas.chartInstance.destroy();
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // ===============================
        // DATOS (YA PROCESADOS DESDE PHP)
        // ===============================
        const mesesObj = JSON.parse(canvas.dataset.meses);
        const labels = Object.keys(mesesObj);
        const valores = Object.values(mesesObj).map(v => Number(v));

        const meta = Number(canvas.dataset.meta);
        const tendencia = canvas.dataset.tendencia;

        // ===============================
        // COLORES
        // ===============================
        const colorLinea = tendencia === 'POSITIVA'
            ? 'rgba(25, 135, 84, 1)'
            : 'rgba(220, 53, 69, 1)';

        const colorMeta = 'rgba(13, 202, 240, 0.85)';
        const metaData = labels.map(() => meta);

        // ===============================
        // CHART
        // ===============================
        canvas.chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Avance',
                        data: valores,
                        borderColor: colorLinea,
                        backgroundColor: colorLinea,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Meta',
                        data: metaData,
                        borderColor: colorMeta,
                        borderDash: [6, 6],
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#e9ecef'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#adb5bd' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#adb5bd' },
                        grid: { color: 'rgba(255,255,255,0.08)' }
                    }
                }
            }
        });
    });
}
