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

        // Mes de corte = último mes del trimestre
        $mesesDelTrimestre = self::MESES_POR_TRIMESTRE[$trimestre];
        $mesCorteTrimestre = max($mesesDelTrimestre);

        $resultados = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            $indice       = $indicador->getIndice();
            $valorBase    = (float) ($indicador->getValorBase() ?? 0);
            $meta         = (float) $indicador->getValor();
            $tendencia    = strtoupper($indicador->getTendencia());
            $esPorcentaje = $indicador->isEsPorcentaje();
            $valorMensual = $indicador->getValorMensual() ?? [];

            /* =====================================================
             * BUSCAR EL ÚLTIMO VALOR DISPONIBLE
             * hasta el mes de corte del trimestre (inclusive).
             *
             * Se recorren TODOS los meses del año hasta el corte,
             * no solo los del trimestre. Así, si el trimestre 2
             * no tiene datos pero el trimestre 1 sí, se usa ese.
             * ===================================================== */
            $ultimoValor = null;

            for ($mes = 1; $mes <= $mesCorteTrimestre; $mes++) {
                $nombreMes = self::MESES_NOMBRE[$mes] ?? null;
                if (!$nombreMes) {
                    continue;
                }

                if (isset($valorMensual[$nombreMes]) && $valorMensual[$nombreMes] !== null) {
                    $ultimoValor = (float) $valorMensual[$nombreMes];
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
                    $esPorcentaje
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
                'formula_descripcion' => $indicador->getFormula(),
                'sin_dato'            => $sinDato,
            ];
        }

        return $resultados;
    }

    /**
     * Aplica la fórmula de porcentaje de avance según el tipo de indicador.
     * Resultado acotado al rango [0, 100].
     */
    private function calcularPorcentaje(
        float $ultimoValor,
        float $valorBase,
        float $meta,
        string $tendencia,
        bool $esPorcentaje
    ): float {
        if ($esPorcentaje) {
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
