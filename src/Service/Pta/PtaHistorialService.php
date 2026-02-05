<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

class PtaHistorialService
{
    public function buildHistorial(Encabezado $encabezado): array
{
    $mesesOrden = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    //$mesActual = (int) date('n');
    $mesActual =5;


    $historial = [];

    /* =====================================================
       1️⃣ Inicializar indicadores
       ===================================================== */
    foreach ($encabezado->getIndicadores() as $indicador) {
        $historial[$indicador->getIndice()] = [
            'info' => [
                'indicador'  => $indicador->getIndicador(),
                'formula'    => $indicador->getFormula(),
                'valor_base' => (float) $indicador->getValorBase(),
                'meta'       => (float) $indicador->getValor(),
                'tendencia'  => $indicador->getTendencia(),
            ],
            'acciones' => [],
            'grafica'  => null,
        ];
    }

    /* =====================================================
       2️⃣ Recorrer acciones
       ===================================================== */
    foreach ($encabezado->getAcciones() as $accion) {

        $indiceIndicador = $accion->getIndicador();
        if (!isset($historial[$indiceIndicador])) {
            continue;
        }

        $accionId = $accion->getId();
        $periodoAccion = $accion->getPeriodo(); // meses en string

        // Inicializar meses de la acción
        $meses = [];
        foreach ($mesesOrden as $num => $nombreMes) {
            $meses[$nombreMes] = [
                'estado'  => in_array($nombreMes, $periodoAccion, true)
                    ? ($num < $mesActual ? 'atraso_critico' : 'pendiente')
                    : 'fuera_periodo',
                'eventos' => [],
            ];
        }

        // Crear acción si no existe
        if (!isset($historial[$indiceIndicador]['acciones'][$accionId])) {
            $historial[$indiceIndicador]['acciones'][$accionId] = [
                'nombre' => $accion->getAccion(),
                'meses'  => $meses,
            ];
        }

        /* =============================
           CAPTURAS / CORRECCIONES
           ============================= */
        foreach ($accion->getHistorialAcciones() as $evento) {

            $mesNombre = $mesesOrden[$evento->getMes()] ?? null;
            if (!$mesNombre) {
                continue;
            }

            $historial[$indiceIndicador]['acciones'][$accionId]['meses'][$mesNombre]['estado'] = 'ok';

            $historial[$indiceIndicador]['acciones'][$accionId]['meses'][$mesNombre]['eventos'][] = [
                'tipo'   => 'CAPTURA',
                'valor'  => (float) $evento->getValor(),
                'fecha'  => $evento->getFecha(),
                'motivo' => null,
            ];
        }

        /* =============================
           ATRASOS
           ============================= */
        foreach ($accion->getHistorialAccionesAtrasos() as $evento) {

            $mesNombre = $mesesOrden[$evento->getMes()] ?? null;
            if (!$mesNombre) {
                continue;
            }

            $historial[$indiceIndicador]['acciones'][$accionId]['meses'][$mesNombre]['estado'] = 'atraso';

            $historial[$indiceIndicador]['acciones'][$accionId]['meses'][$mesNombre]['eventos'][] = [
                'tipo'   => 'ATRASO',
                'valor'  => (float) $evento->getValor(),
                'fecha'  => $evento->getFecha(),
                'motivo' => $evento->getMotivo(),
            ];
        }
    }

    return $historial;
}

}
