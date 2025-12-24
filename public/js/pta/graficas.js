/**
 * =====================================================
 * PTA — VISTA GRÁFICAS
 * -----------------------------------------------------
 * Responsabilidad del archivo:
 * - Detectar cuando la vista de gráficas se carga con Turbo
 * - Inicializar Chart.js sobre cada canvas encontrado
 *
 * REGLAS CLAVE:
 * - Este JS NO calcula lógica de negocio
 * - Todos los valores ya vienen calculados desde PHP
 * - El JS solo interpreta y dibuja
 * =====================================================
 */

/**
 * =====================================================
 * EVENTO TURBO
 * -----------------------------------------------------
 * Se escucha el evento turbo:frame-load para asegurar:
 * - Compatibilidad con navegación Turbo
 * - Ejecución solo cuando el frame "content" se actualiza
 * =====================================================
 */
document.addEventListener('turbo:frame-load', (event) => {

    const frame = event.target;

    // Ejecutar únicamente cuando se carga el frame principal
    if (frame.id !== 'content') return;

    // Buscar el root específico de la vista de gráficas
    const viewRoot = frame.querySelector('[data-pta-view="graficas"]');
    if (!viewRoot) return;

    console.log('📊 Vista de gráficas PTA detectada');

    // Inicializar gráficas dentro del root detectado
    initPtaGraficas(viewRoot);
});

/**
 * =====================================================
 * FUNCIÓN PRINCIPAL DE INICIALIZACIÓN
 * -----------------------------------------------------
 * - Recibe el nodo raíz de la vista de gráficas
 * - Busca todos los canvas de gráficas
 * - Inicializa una instancia de Chart.js por cada uno
 * =====================================================
 */
function initPtaGraficas(root) {

    // Obtener todos los canvas destinados a gráficas
    const charts = root.querySelectorAll('.pta-chart');

    // Si no hay canvas, no hay nada que dibujar
    if (!charts.length) {
        console.warn('⚠️ No se encontraron gráficas');
        return;
    }

    /**
     * =================================================
     * ITERACIÓN POR CADA GRÁFICA
     * =================================================
     */
    charts.forEach(canvas => {

        /**
         * -------------------------------------------------
         * Protección contra doble inicialización
         * -------------------------------------------------
         * Si el canvas ya tiene una instancia previa
         * (por navegación Turbo), se destruye antes
         * de crear una nueva.
         */
        if (canvas.chartInstance) {
            canvas.chartInstance.destroy();
        }

        // Obtener contexto 2D del canvas
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        /**
         * =================================================
         * DATOS (YA PROCESADOS DESDE PHP)
         * =================================================
         * Los datos llegan mediante data-attributes:
         * - data-meses: JSON con serie final por mes
         * - data-meta: valor de la meta
         * - data-tendencia: POSITIVA / NEGATIVA
         */
        const mesesObj = JSON.parse(canvas.dataset.meses);

        // Etiquetas del eje X (meses)
        const labels = Object.keys(mesesObj);

        // Valores del eje Y (avance mensual)
        const valores = Object.values(mesesObj).map(v => Number(v));

        // Meta y tendencia del indicador
        const meta = Number(canvas.dataset.meta);
        const tendencia = canvas.dataset.tendencia;

        /**
         * =================================================
         * DEFINICIÓN DE COLORES
         * =================================================
         * - El color del avance depende de la tendencia
         * - La meta siempre se muestra con línea punteada
         */
        const colorLinea = tendencia === 'POSITIVA'
            ? 'rgba(25, 135, 84, 1)'   // verde
            : 'rgba(220, 53, 69, 1)'; // rojo

        const colorMeta = 'rgba(13, 202, 240, 0.85)';

        // Serie constante para representar la meta
        const metaData = labels.map(() => meta);

        /**
         * =================================================
         * INICIALIZACIÓN DE CHART.JS
         * =================================================
         * Se crea una gráfica de tipo "line" con:
         * - Serie de avance
         * - Serie de meta
         */
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
                        ticks: {
                            color: '#adb5bd'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#adb5bd'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.08)'
                        }
                    }
                }
            }
        });
    });
}
