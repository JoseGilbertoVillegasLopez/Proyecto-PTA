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
             * ===================================================== */
            $serie = [];
            foreach (self::MESES as $mes) {
                if (!in_array($mes, $mesesReportables, true)) {
                    continue;
                }

                $serie[$mes] = isset($valorMensual[$mes])
                    ? (float) $valorMensual[$mes]
                    : null;
            }

            /* =====================================================
             * 3) CALCULAR PORCENTAJE DE AVANCE
             *    Se usa el ÚLTIMO valor registrado (más reciente).
             *    Si no hay ningún valor, el avance es 0.
             * ===================================================== */
            $ultimoValor = null;
            // Recorrer meses en orden para encontrar el último
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
                $indicador->isCapturaEnPorcentaje()
            );

            /* =====================================================
             * 4) RESUMEN DE ACCIONES ASOCIADAS
             *    Muestra qué meses tiene cada acción y su
             *    estado de cumplimiento general.
             * ===================================================== */
            $accionesResumen = $this->buildResumenAcciones($encabezado, $indicador->getIndice());

            /* =====================================================
             * 5) RESULTADO PARA ESTE INDICADOR
             * ===================================================== */
            $graficas[] = [
                'indicador'          => $indicador->getIndicador(),
                'tendencia'          => $tendencia,
                'valor_base'         => $valorBase,
                'meta'               => $meta,
                'es_porcentaje'      => $esPorcentaje,
                'captura_porcentaje' => $indicador->isCapturaEnPorcentaje(),
                'serie'              => $serie,
                'porcentaje'   => $porcentaje,
                'acciones'     => $accionesResumen,
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
     * Calcula el porcentaje de avance según la fórmula del indicador.
     *
     * Hay tres casos:
     *
     * 1. esPorcentaje=false:
     *    Meta es un valor neto absoluto.
     *    Fórmula: ((actual-base) / (meta-base)) × 100
     *
     * 2. esPorcentaje=true, capturaEnPorcentaje=false (Opción A):
     *    Meta es un % de cambio relativo al base. Captura en absolutos.
     *    Fórmula: ((actual-base) / (base × meta/100)) × 100
     *
     * 3. esPorcentaje=true, capturaEnPorcentaje=true:
     *    El indicador se mide en %. Meta es el % objetivo directo.
     *    Fórmula: ((actual-base) / (meta-base)) × 100
     *    (misma que el caso 1, pero todas las cifras están en % 0-100)
     *
     * El resultado se acota al rango [0, 100].
     */
    private function calcularPorcentaje(
        ?float $ultimoValor,
        float $valorBase,
        float $meta,
        string $tendencia,
        bool $esPorcentaje,
        bool $capturaEnPorcentaje = false
    ): float {
        if ($ultimoValor === null) {
            return 0.0;
        }

        if ($esPorcentaje && !$capturaEnPorcentaje) {
            // Caso 2: meta es % de cambio, captura en absolutos
            $denominador = $valorBase * $meta / 100;
        } else {
            // Caso 1 y 3: denominador = distancia entre base y meta
            $denominador = abs($meta - $valorBase);
        }

        if ($denominador == 0) {
            return 0.0;
        }

        if ($tendencia === 'POSITIVA') {
            $porcentaje = (($ultimoValor - $valorBase) / $denominador) * 100;
        } else {
            $porcentaje = (($valorBase - $ultimoValor) / $denominador) * 100;
        }

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
