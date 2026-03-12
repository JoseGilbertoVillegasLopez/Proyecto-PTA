<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

class PtaTrimestreCalculoService
{
    /**
     * ============================================================
     * CALCULA RESULTADOS Y PORCENTAJES POR INDICADOR
     * PARA UN TRIMESTRE ESPECÍFICO
     * ============================================================
     */
    public function build(Encabezado $encabezado, int $trimestre): array
    {
        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        // Determinar mes tope según trimestre
        $mesTopeIndex = match ($trimestre) {
            1 => 2,  // Marzo
            2 => 5,  // Junio
            3 => 8,  // Septiembre
            4 => 11, // Diciembre
            default => throw new \InvalidArgumentException('Trimestre inválido.')
        };

        $mesesAcumulados = array_slice($meses, 0, $mesTopeIndex + 1);

        $acciones = $encabezado->getAcciones();
        $resultados = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            $indice = $indicador->getIndice();
            $valorBase = (float) ($indicador->getValorBase() ?? 0);
            $meta = (float) $indicador->getValor();
            $tendencia = strtoupper($indicador->getTendencia());
            $formulaDescripcion = $indicador->getFormula(); // descripción textual

            // =========================================================
            // SUMA MENSUAL ACUMULADA
            // =========================================================
            $sumaMensual = 0.0;

            foreach ($acciones as $accion) {

                if ($accion->getIndicador() !== $indice) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];

                foreach ($mesesAcumulados as $mes) {

                    if (isset($valoresAccion[$mes]) && $valoresAccion[$mes] !== null) {
                        $sumaMensual += (float) $valoresAccion[$mes];
                    }
                }
            }

            // =========================================================
            // CÁLCULO SEGÚN TENDENCIA (MISMA LÓGICA QUE GRÁFICA)
            // =========================================================
            $resultadoFinal = 0.0;
            $porcentaje = 0.0;

            if ($tendencia === 'POSITIVA') {

                $acumulado = $valorBase + $sumaMensual;
                $resultadoFinal = $acumulado;

                if ($meta > $valorBase) {
                    $porcentaje = (($acumulado - $valorBase) / ($meta - $valorBase)) * 100;
                }

            } else { // NEGATIVA

                $actual = $valorBase - $sumaMensual;
                $actual = max(0, $actual);

                $resultadoFinal = $actual;

                if ($valorBase > $meta) {
                    $porcentaje = (($valorBase - $actual) / ($valorBase - $meta)) * 100;
                }
            }

            // Limitar porcentaje 0 - 100
            $porcentaje = max(0, min(100, $porcentaje));

            // Redondear a 2 decimales
            $porcentaje = round($porcentaje, 2);
            $resultadoFinal = round($resultadoFinal, 2);

            $resultados[$indice] = [
                'id' => $indicador->getId(),
                'indice' => $indice,
                'nombre' => $indicador->getIndicador(),
                'meta' => round($meta, 2),
                'resultado' => $resultadoFinal,
                'porcentaje' => $porcentaje,
                'formula_descripcion' => $formulaDescripcion
            ];
        }

        return $resultados;
    }
}
