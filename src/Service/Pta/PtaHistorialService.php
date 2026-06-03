<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;

/**
 * =========================================================
 * PtaHistorialService
 * ---------------------------------------------------------
 * Construye el historial completo de un PTA para mostrarlo
 * en la vista de historial. Permite a los directivos ver
 * todos los cambios realizados con fechas y motivos.
 *
 * El historial se organiza por INDICADOR y tiene DOS secciones:
 *
 * 1. ACCIONES: estado de cumplimiento ✓/✗ por mes,
 *    con el historial de cambios (quién lo marcó y cuándo).
 *
 * 2. INDICADOR: valores snapshot mensuales registrados,
 *    con el historial de cada captura o corrección.
 *
 * ESTADOS DE MES EN ACCIONES:
 *   ok              = cumplida en tiempo (valor=1, sin atraso)
 *   no_cumplida     = marcada como ✗ en tiempo
 *   corregido       = cambió de estado en mes pasado (tiene motivo)
 *   atraso_critico  = mes pasado sin ningún registro
 *   pendiente       = mes actual o futuro sin registro
 *   fuera_periodo   = no está en el periodo de la acción
 * =========================================================
 */
class PtaHistorialService
{
    private const MESES_ORDEN = [
        1 => 'Enero',   2 => 'Febrero',  3 => 'Marzo',
        4 => 'Abril',   5 => 'Mayo',     6 => 'Junio',
        7 => 'Julio',   8 => 'Agosto',   9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Construye el historial completo del PTA.
     *
     * @return array Estructura indexada por Indicadores::$indice.
     *   Cada elemento contiene:
     *     info      → datos del indicador
     *     acciones  → historial de acciones del indicador
     *     indicador → historial de valores snapshot del indicador
     */
    public function buildHistorial(Encabezado $encabezado): array
    {
        $mesActual = (int) date('n');
        $historial = [];

        /* =====================================================
         * 1) INICIALIZAR ESTRUCTURA POR INDICADOR
         * ===================================================== */
        foreach ($encabezado->getIndicadores() as $indicador) {

            $mesesReportables = $this->calcularMesesReportables($encabezado, $indicador->getIndice());
            $valorMensual     = $indicador->getValorMensual() ?? [];

            // Construir estructura de meses del indicador (snapshot + eventos)
            $mesesIndicador = [];
            foreach ($mesesReportables as $nombreMes) {
                $numMes = $this->mesANumero($nombreMes);

                $mesesIndicador[$nombreMes] = [
                    'valor'   => $valorMensual[$nombreMes] ?? null,
                    'estado'  => $this->estadoMesIndicador($numMes, $mesActual, $valorMensual[$nombreMes] ?? null),
                    'eventos' => [],
                ];
            }

            // Cargar historial de eventos del indicador (capturas y correcciones)
            foreach ($indicador->getHistorialValores() as $evento) {
                $nombreMes = self::MESES_ORDEN[$evento->getMes()] ?? null;
                if (!$nombreMes || !isset($mesesIndicador[$nombreMes])) {
                    continue;
                }

                $mesesIndicador[$nombreMes]['eventos'][] = [
                    'tipo'   => $evento->getMotivo() ? 'CORRECCION' : 'CAPTURA',
                    'valor'  => (float) $evento->getValor(),
                    'fecha'  => $evento->getFecha(),
                    'motivo' => $evento->getMotivo(),
                ];
            }

            $historial[$indicador->getIndice()] = [
                'info' => [
                    'indicador'    => $indicador->getIndicador(),
                    'formula'      => $indicador->getFormula(),
                    'valor_base'   => (float) $indicador->getValorBase(),
                    'meta'         => (float) $indicador->getValor(),
                    'tendencia'    => $indicador->getTendencia(),
                    'es_porcentaje' => $indicador->isEsPorcentaje(),
                ],
                'acciones'  => [],
                'indicador' => $mesesIndicador,
            ];
        }

        /* =====================================================
         * 2) CONSTRUIR HISTORIAL DE ACCIONES
         * ===================================================== */
        foreach ($encabezado->getAcciones() as $accion) {

            $indiceIndicador = $accion->getIndicador();
            if (!isset($historial[$indiceIndicador])) {
                continue;
            }

            $accionId       = $accion->getId();
            $periodoAccion  = $accion->getPeriodo();
            $mesesCumplidos = $accion->getMesesCumplidos() ?? [];

            // Inicializar estructura de meses para esta acción
            $meses = [];
            foreach (self::MESES_ORDEN as $numMes => $nombreMes) {

                $enPeriodo = in_array($nombreMes, $periodoAccion, true);
                $cumplida  = $mesesCumplidos[$nombreMes] ?? null;

                $meses[$nombreMes] = [
                    'estado'  => $enPeriodo
                        ? $this->estadoMesAccion($numMes, $mesActual, $cumplida)
                        : 'fuera_periodo',
                    'eventos' => [],
                ];
            }

            // Cargar historial de eventos de esta acción
            foreach ($accion->getHistorialAcciones() as $evento) {
                $nombreMes = self::MESES_ORDEN[$evento->getMes()] ?? null;
                if (!$nombreMes) {
                    continue;
                }

                // Si el evento tiene motivo, es una corrección o registro tardío
                $tipo = match (true) {
                    $evento->getMotivo() !== null && $evento->getValor() === 1 => 'CORRECCION_CUMPLIDA',
                    $evento->getMotivo() !== null && $evento->getValor() === 0 => 'CORRECCION_NO_CUMPLIDA',
                    $evento->getValor() === 1                                  => 'CAPTURA_CUMPLIDA',
                    default                                                     => 'CAPTURA_NO_CUMPLIDA',
                };

                $meses[$nombreMes]['eventos'][] = [
                    'tipo'    => $tipo,
                    'cumplida' => $evento->getValor() === 1,
                    'fecha'   => $evento->getFecha(),
                    'motivo'  => $evento->getMotivo(),
                ];

                // Refinar el estado del mes según el historial más reciente
                if (isset($meses[$nombreMes]) && $meses[$nombreMes]['estado'] !== 'fuera_periodo') {
                    $meses[$nombreMes]['estado'] = $this->estadoMesAccion(
                        $evento->getMes(),
                        $mesActual,
                        $evento->getValor() === 1
                    );
                }
            }

            $historial[$indiceIndicador]['acciones'][$accionId] = [
                'nombre' => $accion->getAccion(),
                'meses'  => $meses,
            ];
        }

        return $historial;
    }

    /**
     * Calcula los meses reportables de un indicador como la unión
     * de los periodos de todas sus acciones, ordenados por mes del año.
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

        // Ordenar por posición en el año
        $orden = array_flip(array_values(self::MESES_ORDEN));
        usort($mesesUnion, fn($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));

        return $mesesUnion;
    }

    /**
     * Determina el estado de un mes para una ACCIÓN.
     *
     * Estados posibles:
     *   ok           → cumplida (puede ser en tiempo o con corrección)
     *   no_cumplida  → marcada como ✗
     *   atraso_critico → mes pasado sin ningún registro
     *   pendiente    → mes actual o futuro sin registro
     */
    private function estadoMesAccion(int $numMes, int $mesActual, ?bool $cumplida): string
    {
        if ($cumplida === true) {
            return 'ok';
        }

        if ($cumplida === false) {
            return 'no_cumplida';
        }

        // Sin registro todavía
        if ($numMes < $mesActual) {
            return 'atraso_critico';
        }

        return 'pendiente';
    }

    /**
     * Determina el estado de un mes para un INDICADOR (valor snapshot).
     *
     * Estados posibles:
     *   registrado   → tiene valor snapshot
     *   sin_registro → mes pasado sin valor
     *   pendiente    → mes actual o futuro sin valor
     */
    private function estadoMesIndicador(int $numMes, int $mesActual, ?string $valor): string
    {
        if ($valor !== null) {
            return 'registrado';
        }

        if ($numMes < $mesActual) {
            return 'sin_registro';
        }

        return 'pendiente';
    }

    /**
     * Convierte nombre de mes en español a número (1-12).
     */
    private function mesANumero(string $mes): int
    {
        return (int) (array_search($mes, self::MESES_ORDEN, true) ?: 0);
    }
}
