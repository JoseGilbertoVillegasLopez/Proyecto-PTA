<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

/**
 * =========================================================
 * PtaGraficaService
 * ---------------------------------------------------------
 * Construye los datos de gráfica para cada indicador del PTA.
 *
 * NUEVO MODELO:
 * - Los valores ya no se suman desde las acciones.
 * - Se leen directamente del campo Indicadores::$valorMensual,
 *   que contiene el snapshot acumulado registrado por el
 *   responsable en cada mes reportable.
 *
 * FÓRMULAS DE PORCENTAJE DE AVANCE:
 *   esPorcentaje=false, POSITIVA: ((actual-base)/(meta-base)) * 100
 *   esPorcentaje=false, NEGATIVA: ((base-actual)/(base-meta)) * 100
 *   esPorcentaje=true,  POSITIVA: ((actual-base)/(base*meta/100)) * 100
 *   esPorcentaje=true,  NEGATIVA: ((base-actual)/(base*meta/100)) * 100
 *
 * El porcentaje se calcula con el ÚLTIMO valor registrado (cualquier mes).
 * =========================================================
 */
class PtaGraficaService
{
    /**
     * Lista ordenada de meses del año.
     */
    private const MESES = [
        'Enero','Febrero','Marzo','Abril','Mayo','Junio',
        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];

    /**
     * Construye el array de gráficas para todos los indicadores del PTA.
     * Cada elemento del array corresponde a un indicador.
     *
     * @return array<int, array{
     *   indicador:    string,
     *   tendencia:    string,
     *   valor_base:   float,
     *   meta:         float,
     *   es_porcentaje: bool,
     *   serie:        array<string, float|null>,
     *   porcentaje:   float,
     *   acciones:     array
     * }>
     */
    public function build(Encabezado $encabezado): array
    {
        $graficas = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            $valorBase    = (float) ($indicador->getValorBase() ?? 0);
            $meta         = (float) $indicador->getValor();
            $tendencia    = $indicador->getTendencia();
            $esPorcentaje = $indicador->isEsPorcentaje();
            $valorMensual = $indicador->getValorMensual() ?? [];

            /* =====================================================
             * 1) CALCULAR MESES REPORTABLES DEL INDICADOR
             *    (unión de periodos de sus acciones)
             * ===================================================== */
            $mesesReportables = $this->calcularMesesReportables($encabezado, $indicador->getIndice());

            /* =====================================================
             * 2) CONSTRUIR SERIE MENSUAL
             *    Solo se incluyen los meses reportables.
             *    Meses sin dato = null (no se grafica ese punto).
             *
             *    Cuando esPorcentaje=true Y capturaEnPorcentaje=true:
             *    El responsable registra % del avance (0-100).
             *    Para graficar correctamente en unidades absolutas
             *    convertimos cada porcentaje v al valor absoluto:
             *    Fórmula correcta (validada):
             *      meta_abs = base + base × meta/100   [POSITIVA]
             *      meta_abs = base - base × meta/100   [NEGATIVA]
             *
             *      abs(v) = base + base × v/100        [POSITIVA]
             *      abs(v) = base - base × v/100        [NEGATIVA]
             *
             *    Ejemplo: base=100, meta=90%, v=40%
             *      meta_abs = 100 + 100×0.9 = 190
             *      abs(40%) = 100 + 100×0.4 = 140
             *      progreso = 140/190 × 100 = 73.68%
             * ===================================================== */
            $capturaEnPorcentaje = $indicador->isCapturaEnPorcentaje();

            // meta_abs: objetivo en unidades absolutas (para conversión y meta línea)
            $metaAbsParaConversion = null;
            if ($esPorcentaje && $capturaEnPorcentaje) {
                $metaAbsParaConversion = $tendencia === 'POSITIVA'
                    ? $valorBase + $valorBase * $meta / 100
                    : $valorBase - $valorBase * $meta / 100;
            }

            $serie = [];
            foreach (self::MESES as $mes) {
                if (!in_array($mes, $mesesReportables, true)) {
                    continue;
                }

                if (!isset($valorMensual[$mes])) {
                    $serie[$mes] = null;
                    continue;
                }

                $v = (float) $valorMensual[$mes];

                // Convertir % a absoluto cuando capturaEnPorcentaje=true
                if ($metaAbsParaConversion !== null) {
                    $v = $tendencia === 'POSITIVA'
                        ? $valorBase + $valorBase * $v / 100
                        : $valorBase - $valorBase * $v / 100;
                }

                $serie[$mes] = $v;
            }

            /* =====================================================
             * 3) CALCULAR PORCENTAJE DE AVANCE
             *    Se usa el ÚLTIMO valor registrado (ya en absoluto).
             * ===================================================== */
            $ultimoValor = null;
            foreach (self::MESES as $mes) {
                if (isset($serie[$mes]) && $serie[$mes] !== null) {
                    $ultimoValor = $serie[$mes];
                }
            }

            $porcentaje = $this->calcularPorcentaje(
                $ultimoValor,
                $valorBase,
                $meta,
                $tendencia,
                $esPorcentaje,
                $capturaEnPorcentaje,
                $metaAbsParaConversion   // se pasa para usar la fórmula correcta
            );

            /* =====================================================
             * 4) RESUMEN DE ACCIONES ASOCIADAS
             *    Muestra qué meses tiene cada acción y su
             *    estado de cumplimiento general.
             * ===================================================== */
            $accionesResumen = $this->buildResumenAcciones($encabezado, $indicador->getIndice());

            /* =====================================================
             * 5) META PARA LA GRÁFICA
             * -----------------------------------------------------
             * La línea de meta debe estar en las mismas unidades
             * que los valores de la serie.
             *
             * Casos:
             *   esPorcentaje=false → meta es valor neto absoluto
             *   esPorcentaje=true, capturaAbs → meta es % de cambio;
             *     se convierte al objetivo absoluto
             *   esPorcentaje=true, capturaPct → la serie YA fue
             *     convertida a absoluto arriba; meta_grafica = meta_abs
             * ===================================================== */
            if ($esPorcentaje && !$capturaEnPorcentaje) {
                // Meta expresada como % de cambio; captura en absolutos
                $metaGrafica = $tendencia === 'POSITIVA'
                    ? $valorBase + $valorBase * $meta / 100
                    : $valorBase - $valorBase * $meta / 100;
            } elseif ($esPorcentaje && $capturaEnPorcentaje) {
                // La serie fue convertida a absoluto → meta_grafica = mismo absoluto
                $metaGrafica = $metaAbsParaConversion ?? $meta;
            } else {
                // Meta ya es absoluta
                $metaGrafica = $meta;
            }

            /* =====================================================
             * 6) RESULTADO PARA ESTE INDICADOR
             * ===================================================== */
            $graficas[] = [
                'indicador'          => $indicador->getIndicador(),
                'tendencia'          => $tendencia,
                'valor_base'         => $valorBase,
                'meta'               => $meta,
                'meta_grafica'       => round($metaGrafica, 2),
                'es_porcentaje'      => $esPorcentaje,
                // Si capturaEnPorcentaje=true la serie ya fue convertida a absoluto,
                // por lo que el chart debe tratarla como valores absolutos (no %).
                'captura_porcentaje' => $capturaEnPorcentaje && $metaAbsParaConversion === null,
                'serie'              => $serie,
                'porcentaje'         => $porcentaje,
                'acciones'           => $accionesResumen,
            ];
        }

        return $graficas;
    }

    /**
     * Calcula los meses reportables de un indicador como la unión
     * de los periodos de todas sus acciones.
     * El resultado está ordenado por posición en el año.
     *
     * @return string[]
     */
    private function calcularMesesReportables(Encabezado $encabezado, int $indiceIndicador): array
    {
        $mesesUnion = [];

        foreach ($encabezado->getAcciones() as $accion) {
            if ($accion->getIndicador() !== $indiceIndicador) {
                continue;
            }
            $mesesUnion = array_merge($mesesUnion, $accion->getPeriodo());
        }

        $mesesUnion = array_unique($mesesUnion);

        // Ordenar por posición en el año (no alfabéticamente)
        $orden = array_flip(self::MESES);
        usort($mesesUnion, fn($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));

        return $mesesUnion;
    }

    /**
     * Calcula el porcentaje de avance según el tipo de indicador.
     *
     * Casos:
     *
     * 1. esPorcentaje=false:
     *    Meta es valor neto absoluto.
     *    ((actual-base) / (meta-base)) × 100
     *
     * 2. esPorcentaje=true, capturaEnPorcentaje=false:
     *    Meta es % de cambio relativo al base. Captura en absolutos.
     *    ((actual-base) / (base × meta/100)) × 100
     *
     * 3. esPorcentaje=true, capturaEnPorcentaje=true:
     *    La serie ya fue convertida a absoluto usando base + base×v/100.
     *    Fórmula del usuario: absolute / meta_abs × 100  (POSITIVA)
     *                         (base - absolute) / (base - meta_abs) × 100  (NEGATIVA)
     *    Esto significa que el base ya cuenta como parte del logro.
     *    Ejemplo: base=100, meta_abs=190, absolute=140 → 140/190 = 73.68%
     *
     * @param float|null $metaAbs  objetivo absoluto, requerido en el Caso 3
     */
    private function calcularPorcentaje(
        ?float $ultimoValor,
        float $valorBase,
        float $meta,
        string $tendencia,
        bool $esPorcentaje,
        bool $capturaEnPorcentaje = false,
        ?float $metaAbs = null
    ): float {
        if ($ultimoValor === null) {
            return 0.0;
        }

        // Caso 3: capturaEnPorcentaje=true — valores ya en absoluto
        // Fórmula: absolute / meta_abs × 100  (POSITIVA)
        //          (base - absolute) / (base - meta_abs) × 100  (NEGATIVA)
        if ($esPorcentaje && $capturaEnPorcentaje && $metaAbs !== null) {
            if ($tendencia === 'POSITIVA') {
                $porcentaje = $metaAbs != 0
                    ? ($ultimoValor / $metaAbs) * 100
                    : 0.0;
            } else {
                $denominador = $valorBase - $metaAbs;
                $porcentaje  = $denominador != 0
                    ? (($valorBase - $ultimoValor) / $denominador) * 100
                    : 0.0;
            }
            return (float) max(0, min(100, round($porcentaje, 1)));
        }

        // Caso 2: esPorcentaje=true, captura en absolutos
        if ($esPorcentaje && !$capturaEnPorcentaje) {
            $denominador = $valorBase * $meta / 100;
        } else {
            // Caso 1: meta neto absoluto
            $denominador = abs($meta - $valorBase);
        }

        if ($denominador == 0) {
            return 0.0;
        }

        $porcentaje = $tendencia === 'POSITIVA'
            ? (($ultimoValor - $valorBase) / $denominador) * 100
            : (($valorBase - $ultimoValor) / $denominador) * 100;

        return (float) max(0, min(100, round($porcentaje, 1)));
    }

    /**
     * Construye un resumen de cumplimiento de las acciones asociadas
     * al indicador, para mostrar en el detalle de la gráfica.
     *
     * @return array<int, array{nombre: string, meses: array, cumplidas: int, total: int}>
     */
    private function buildResumenAcciones(Encabezado $encabezado, int $indiceIndicador): array
    {
        $resumen = [];

        foreach ($encabezado->getAcciones() as $accion) {
            if ($accion->getIndicador() !== $indiceIndicador) {
                continue;
            }

            $mesesCumplidos = $accion->getMesesCumplidos() ?? [];
            $cumplidas = 0;
            $mesesDetalle = [];

            foreach ($accion->getPeriodo() as $mes) {
                $estado = $mesesCumplidos[$mes] ?? null;
                $mesesDetalle[$mes] = $estado;

                if ($estado === true) {
                    $cumplidas++;
                }
            }

            $resumen[] = [
                'nombre'   => $accion->getAccion(),
                'meses'    => $mesesDetalle,
                'cumplidas' => $cumplidas,
                'total'    => count($accion->getPeriodo()),
            ];
        }

        return $resumen;
    }
}
