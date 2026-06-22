<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

/**
 * =========================================================
 * PtaTrimestreCalculoService
 * ---------------------------------------------------------
 * Calcula el resultado y porcentaje de avance por indicador
 * para un trimestre específico del PTA.
 *
 * NUEVO MODELO:
 * - Ya no suma deltas mensuales de acciones.
 * - Lee el ÚLTIMO valor registrado en Indicadores::$valorMensual
 *   que esté dentro de los meses del trimestre o antes de él.
 *
 * LÓGICA DE "ÚLTIMO VALOR":
 *   Si el trimestre es Ene-Mar pero el único dato está en Feb,
 *   se usa Feb. Si no hay ningún dato hasta el corte del
 *   trimestre, el resultado es null ("sin dato").
 *
 * FÓRMULAS (mismas que PtaGraficaService):
 *   esPorcentaje=false, POSITIVA: ((actual-base)/(meta-base)) * 100
 *   esPorcentaje=false, NEGATIVA: ((base-actual)/(base-meta)) * 100
 *   esPorcentaje=true,  POSITIVA: ((actual-base)/(base*meta/100)) * 100
 *   esPorcentaje=true,  NEGATIVA: ((base-actual)/(base*meta/100)) * 100
 * =========================================================
 */
class PtaTrimestreCalculoService
{
    /**
     * Meses por trimestre (índices base-1 en el año).
     * Trimestre 1 = Enero–Marzo (meses 1-3), etc.
     */
    private const MESES_POR_TRIMESTRE = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    private const MESES_NOMBRE = [
        1 => 'Enero',   2 => 'Febrero',  3 => 'Marzo',
        4 => 'Abril',   5 => 'Mayo',     6 => 'Junio',
        7 => 'Julio',   8 => 'Agosto',   9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Calcula resultados por indicador para el trimestre dado.
     *
     * @param int $trimestre Número de trimestre (1-4)
     * @return array<int, array{
     *   id:          int,
     *   indice:      int,
     *   nombre:      string,
     *   meta:        float,
     *   valor_base:  float,
     *   resultado:   float|null,
     *   porcentaje:  float,
     *   es_porcentaje: bool,
     *   formula_descripcion: string,
     *   sin_dato:    bool
     * }>
     */
    public function build(Encabezado $encabezado, int $trimestre): array
    {
        if (!isset(self::MESES_POR_TRIMESTRE[$trimestre])) {
            throw new \InvalidArgumentException('Trimestre inválido. Debe ser 1, 2, 3 o 4.');
        }

        $mesesDelTrimestre = self::MESES_POR_TRIMESTRE[$trimestre];

        $resultados = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            $indice              = $indicador->getIndice();
            $valorBase           = (float) ($indicador->getValorBase() ?? 0);
            $meta                = (float) $indicador->getValor();
            $tendencia           = strtoupper($indicador->getTendencia());
            $esPorcentaje        = $indicador->isEsPorcentaje();
            $capturaEnPorcentaje = $indicador->isCapturaEnPorcentaje();
            $valorMensual        = $indicador->getValorMensual() ?? [];

            // meta_abs para conversión (mismo cálculo que PtaGraficaService)
            $metaAbsParaConversion = null;
            if ($esPorcentaje && $capturaEnPorcentaje) {
                $metaAbsParaConversion = $tendencia === 'POSITIVA'
                    ? $valorBase + $valorBase * $meta / 100
                    : $valorBase - $valorBase * $meta / 100;
            }

            /* =====================================================
             * BUSCAR EL ÚLTIMO VALOR REGISTRADO dentro de los
             * meses del trimestre actual (sin extenderse a meses
             * de trimestres anteriores).
             * Si capturaEnPorcentaje=true, convertir % → absoluto.
             * ===================================================== */
            $ultimoValor = null;

            foreach ($mesesDelTrimestre as $mes) {
                $nombreMes = self::MESES_NOMBRE[$mes] ?? null;
                if (!$nombreMes) {
                    continue;
                }

                if (isset($valorMensual[$nombreMes]) && $valorMensual[$nombreMes] !== null) {
                    $v = (float) $valorMensual[$nombreMes];

                    // Convertir % → absoluto (misma fórmula que PtaGraficaService)
                    if ($metaAbsParaConversion !== null) {
                        $v = $tendencia === 'POSITIVA'
                            ? $valorBase + $valorBase * $v / 100
                            : $valorBase - $valorBase * $v / 100;
                    }

                    $ultimoValor = $v;
                }
            }

            /* =====================================================
             * CALCULAR PORCENTAJE DE AVANCE
             * ===================================================== */
            $porcentaje = 0.0;
            $sinDato    = ($ultimoValor === null);

            if (!$sinDato) {
                $porcentaje = $this->calcularPorcentaje(
                    $ultimoValor,
                    $valorBase,
                    $meta,
                    $tendencia,
                    $esPorcentaje,
                    $capturaEnPorcentaje,
                    $metaAbsParaConversion
                );
            }

            $resultados[$indice] = [
                'id'                  => $indicador->getId(),
                'indice'              => $indice,
                'nombre'              => $indicador->getIndicador(),
                'meta'                => round($meta, 2),
                'valor_base'          => round($valorBase, 2),
                'resultado'           => $sinDato ? null : round($ultimoValor, 2),
                'porcentaje'          => $porcentaje,
                'es_porcentaje'       => $esPorcentaje,
                'captura_porcentaje'  => $indicador->isCapturaEnPorcentaje(),
                'formula_descripcion' => $indicador->getFormula(),
                'sin_dato'            => $sinDato,
            ];
        }

        return $resultados;
    }

    /**
     * Aplica la fórmula de porcentaje de avance (misma lógica que PtaGraficaService).
     *
     * @param float|null $metaAbs  objetivo absoluto; requerido cuando capturaEnPorcentaje=true
     */
    private function calcularPorcentaje(
        float $ultimoValor,
        float $valorBase,
        float $meta,
        string $tendencia,
        bool $esPorcentaje,
        bool $capturaEnPorcentaje = false,
        ?float $metaAbs = null
    ): float {
        // Caso capturaEnPorcentaje=true: valores ya en absoluto
        // Fórmula: absolute / meta_abs × 100  (POSITIVA)
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
            return (float) max(0, min(100, round($porcentaje, 2)));
        }

        if ($esPorcentaje && !$capturaEnPorcentaje) {
            // Meta es % de cambio relativo al base
            $denominador = $valorBase * $meta / 100;
        } else {
            // Meta es valor neto absoluto
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

        return (float) max(0, min(100, round($porcentaje, 2)));
    }
}
