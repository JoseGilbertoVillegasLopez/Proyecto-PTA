<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

class PtaGraficaService
{
    /**
     * =====================================================
     * CONSTRUCCIÓN COMPLETA DE GRÁFICAS PTA
     * =====================================================
     * - TODA la lógica vive aquí
     * - El controlador solo orquesta
     * - El JS solo dibuja
     */
    public function build(Encabezado $encabezado): array
    {
        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        $acciones = $encabezado->getAcciones();
        $graficas = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            /* =====================================================
               1) SUMA REAL MENSUAL POR INDICADOR
               ===================================================== */
            $valoresMensuales = array_fill_keys($meses, 0);

            foreach ($acciones as $accion) {
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];

                foreach ($meses as $mes) {
                    if (isset($valoresAccion[$mes])) {
                        $valoresMensuales[$mes] += (float) $valoresAccion[$mes];
                    }
                }
            }

            /* =====================================================
               2) DATOS BASE DEL INDICADOR
               ===================================================== */
            $valorBase = (float) ($indicador->getValorBase() ?? 0);
            $meta      = (float) $indicador->getValor();
            $tendencia = $indicador->getTendencia();

            $serie = [];
            $porcentaje = 0;

            /* =====================================================
               3) CONSTRUCCIÓN DE SERIE SEGÚN TENDENCIA
               ===================================================== */
            if ($tendencia === 'POSITIVA') {

                $acumulado = $valorBase;

                foreach ($meses as $mes) {
                    $acumulado += $valoresMensuales[$mes];
                    $serie[$mes] = $acumulado;
                }

                if ($meta > $valorBase) {
                    $porcentaje = (($acumulado - $valorBase) / ($meta - $valorBase)) * 100;
                }

            } else { // NEGATIVA

                $actual = $valorBase;

                foreach ($meses as $mes) {
                    if ($valoresMensuales[$mes] > 0) {
                        $actual -= $valoresMensuales[$mes];
                    }

                    // Nunca permitir valores negativos
                    $actual = max(0, $actual);

                    $serie[$mes] = $actual;
                }

                // Porcentaje de avance negativo
                if ($valorBase > $meta) {
                    $porcentaje = (($valorBase - $actual) / ($valorBase - $meta)) * 100;
                }
            }


            $porcentaje = max(0, min(100, round($porcentaje, 1)));

            /* =====================================================
               4) RESUMEN POR ACCIONES
               ===================================================== */
            $accionesResumen = [];
            $totalIndicador = array_sum($valoresMensuales);

            foreach ($acciones as $accion) {
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];
                $totalAccion = 0;
                $mesesAccion = [];

                foreach ($meses as $mes) {
                    $valor = (float) ($valoresAccion[$mes] ?? 0);
                    $mesesAccion[$mes] = $valor;
                    $totalAccion += $valor;
                }

                $accionesResumen[] = [
                    'nombre'     => $accion->getAccion(),
                    'meses'      => $mesesAccion,
                    'total'      => $totalAccion,
                    'porcentaje' => $totalIndicador > 0
                        ? round(($totalAccion / $totalIndicador) * 100, 1)
                        : 0,
                ];
            }

            /* =====================================================
               5) RESULTADO FINAL
               ===================================================== */
            $graficas[] = [
                'indicador'   => $indicador->getIndicador(),
                'tendencia'   => $tendencia,
                'valor_base'  => $valorBase,
                'meta'        => $meta,
                'serie'       => $serie,
                'porcentaje'  => $porcentaje,
                'acciones'    => $accionesResumen,
            ];
        }

        return $graficas;
    }
}
