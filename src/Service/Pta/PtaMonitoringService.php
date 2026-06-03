<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Entity\Acciones;

/**
 * =========================================================
 * PtaMonitoringService
 * ---------------------------------------------------------
 * Evalúa el estado de cumplimiento de los PTAs para el
 * panel de monitoreo de directivos.
 *
 * NUEVO MODELO:
 * - evaluarAccion() ya no consulta HistorialAccionesAtrasos
 *   (entidad eliminada). Ahora usa únicamente HistorialAcciones,
 *   donde valor=1 significa cumplida y valor=0 no cumplida.
 *
 * ESTADOS DE MES (sin cambio en el concepto):
 *   EN_TIEMPO          → cumplida (valor=1 en historial)
 *   NO_CUMPLIDA        → marcada como ✗ (valor=0 en historial)
 *   ATRASO_REGISTRADO  → mes pasado con registro tardío (tiene motivo)
 *   ATRASO_NO_REGISTRADO → mes pasado sin ningún registro
 *   FUTURO             → mes aún no llegó
 *
 * ESTADOS DE PTA:
 *   OK       → todas las acciones en tiempo o pendientes
 *   ATENCION → alguna acción con atraso registrado o no cumplida
 *   CRITICO  → alguna acción con mes pasado sin registro
 * =========================================================
 */
class PtaMonitoringService
{
    public function monitor(
        array $ptas,
        int $anio,
        int $mesActual,
        array $contexto
    ): array {

        // $anio es recibido para consistencia de la firma; el filtro por año
        // se aplica antes de llamar a este servicio (ver EncabezadoRepository).
        $rootType = $contexto['root_type'] ?? 'GLOBAL';
        $rootId   = $contexto['root_id'] ?? null;

        $ptas = $this->filtrarPtasPorContexto($ptas, $rootType, $rootId);

        $nivel = match ($rootType) {
            'GLOBAL'       => 'GLOBAL',
            'DEPARTAMENTO' => 'DEPARTAMENTO',
            'PUESTO'       => 'FINAL',
            default        => 'GLOBAL',
        };

        $resultado = [
            'contexto' => [
                'nivel'      => $nivel,
                'root_type'  => $rootType,
                'root_id'    => $rootId,
                'breadcrumb' => $this->buildBreadcrumb($rootType, $rootId),
            ],
            'resumen_global' => [
                'estado'        => 'OK',
                'total_ptas'    => 0,
                'ptas_ok'       => 0,
                'ptas_atencion' => 0,
                'ptas_critico'  => 0,
            ],
            'cards'   => [],
            'ptas'    => [],
            'graficas' => [
                'cumplimiento_general' => ['en_tiempo' => 0, 'atrasado' => 0],
                'atrasos'              => ['registrados' => 0, 'no_registrados' => 0],
            ],
        ];

        foreach ($ptas as $pta) {
            if (!$pta instanceof Encabezado) {
                continue;
            }

            $ptaData = $this->evaluarPta($pta, $mesActual);
            $resultado['ptas'][] = $ptaData;
            $resultado['resumen_global']['total_ptas']++;

            match ($ptaData['estado_global']) {
                'OK'       => $resultado['resumen_global']['ptas_ok']++,
                'ATENCION' => $resultado['resumen_global']['ptas_atencion']++,
                'CRITICO'  => $resultado['resumen_global']['ptas_critico']++,
            };
        }

        if ($resultado['resumen_global']['ptas_critico'] > 0) {
            $resultado['resumen_global']['estado'] = 'CRITICO';
        } elseif ($resultado['resumen_global']['ptas_atencion'] > 0) {
            $resultado['resumen_global']['estado'] = 'ATENCION';
        }

        $this->attachGraficaToResumenGlobal($resultado);

        if ($nivel !== 'FINAL') {
            $resultado['cards'] = $this->buildCards($ptas, $nivel, $mesActual);
        }

        return $resultado;
    }

    /* =====================================================
     * FILTRAR PTAs SEGÚN CONTEXTO JERÁRQUICO
     * ===================================================== */
    private function filtrarPtasPorContexto(array $ptas, string $rootType, ?int $rootId): array
    {
        if ($rootType === 'GLOBAL' || !$rootId) {
            return $ptas;
        }

        $filtrados = [];

        foreach ($ptas as $pta) {
            $puesto       = $pta->getResponsable()->getPuesto();
            $departamento = $pta->getResponsable()->getDepartamento();

            if ($rootType === 'DEPARTAMENTO' && $departamento->getId() === (int) $rootId) {
                $filtrados[] = $pta;
            }

            if ($rootType === 'PUESTO' && $puesto->getId() === (int) $rootId) {
                $filtrados[] = $pta;
            }
        }

        return $filtrados;
    }

    private function buildBreadcrumb(string $rootType, ?int $rootId): array
    {
        return match ($rootType) {
            'GLOBAL' => [
                ['label' => 'Global', 'root_type' => 'GLOBAL', 'root_id' => null],
            ],
            'DEPARTAMENTO' => [
                ['label' => 'Global', 'root_type' => 'GLOBAL', 'root_id' => null],
                ['label' => 'Departamento', 'root_type' => 'DEPARTAMENTO', 'root_id' => $rootId],
            ],
            'PUESTO' => [
                ['label' => 'Global', 'root_type' => 'GLOBAL', 'root_id' => null],
                ['label' => 'Departamento', 'root_type' => 'DEPARTAMENTO', 'root_id' => null],
                ['label' => 'Puesto', 'root_type' => 'PUESTO', 'root_id' => $rootId],
            ],
            default => [],
        };
    }

    /* =====================================================
     * CONSTRUIR CARDS AGREGADAS POR DEPARTAMENTO/PUESTO
     * ===================================================== */
    private function buildCards(array $ptas, string $nivel, int $mesActual): array
    {
        $cards = [];

        foreach ($ptas as $pta) {
            $responsable  = $pta->getResponsable();
            $puesto       = $responsable->getPuesto();
            $departamento = $responsable->getDepartamento();

            $key = match ($nivel) {
                'GLOBAL'       => 'DEP_' . $departamento->getId(),
                'DEPARTAMENTO' => 'PUESTO_' . $puesto->getId(),
                default        => null,
            };

            if (!$key) {
                continue;
            }

            if (!isset($cards[$key])) {
                $cards[$key] = [
                    'type'         => $nivel === 'GLOBAL' ? 'DEPARTAMENTO' : 'PUESTO',
                    'id'           => $nivel === 'GLOBAL' ? $departamento->getId() : $puesto->getId(),
                    'nombre'       => $nivel === 'GLOBAL' ? $departamento->getNombre() : $puesto->getNombre(),
                    'estado_global' => 'OK',
                    'resumen'      => ['total_ptas' => 0, 'ok' => 0, 'atencion' => 0, 'critico' => 0],
                    'link'         => [
                        'root_type' => $nivel === 'GLOBAL' ? 'DEPARTAMENTO' : 'PUESTO',
                        'root_id'   => $nivel === 'GLOBAL' ? $departamento->getId() : $puesto->getId(),
                    ],
                ];
            }

            $ptaEstado = $this->evaluarPta($pta, $mesActual)['estado_global'];
            $cards[$key]['resumen']['total_ptas']++;

            match ($ptaEstado) {
                'OK'       => $cards[$key]['resumen']['ok']++,
                'ATENCION' => $cards[$key]['resumen']['atencion']++,
                'CRITICO'  => $cards[$key]['resumen']['critico']++,
            };

            if ($ptaEstado === 'CRITICO') {
                $cards[$key]['estado_global'] = 'CRITICO';
            } elseif ($ptaEstado === 'ATENCION' && $cards[$key]['estado_global'] !== 'CRITICO') {
                $cards[$key]['estado_global'] = 'ATENCION';
            }
        }

        foreach ($cards as &$c) {
            $t  = $c['resumen']['total_ptas'];
            $c['grafica'] = $this->buildGraficaFromResumen($t, $c['resumen']['ok'], $c['resumen']['atencion'], $c['resumen']['critico']);
        }
        unset($c);

        return array_values($cards);
    }

    /* =====================================================
     * EVALUAR PTA — agrega estados de sus acciones
     * ===================================================== */
    private function evaluarPta(Encabezado $pta, int $mesActual): array
    {
        $estadoPta    = 'OK';
        $accionesData = [];

        foreach ($pta->getAcciones() as $accion) {
            $accionData = $this->evaluarAccion($accion, $mesActual);
            $accionesData[] = $accionData;

            if ($accionData['estado_global'] === 'CRITICO') {
                $estadoPta = 'CRITICO';
            } elseif ($accionData['estado_global'] === 'ATENCION' && $estadoPta !== 'CRITICO') {
                $estadoPta = 'ATENCION';
            }
        }

        return [
            'pta_id'        => $pta->getId(),
            'nombre'        => $pta->getNombre(),
            'departamento'  => $pta->getResponsable()->getDepartamento()->getNombre(),
            'puesto'        => $pta->getResponsable()->getPuesto()->getNombre(),
            'estado_global' => $estadoPta,
            'acciones'      => $accionesData,
        ];
    }

    /* =====================================================
     * EVALUAR ACCIÓN — determina el estado de cada mes
     *
     * NUEVO MODELO:
     * Lee HistorialAcciones (unificado):
     *   valor=1 sin motivo → EN_TIEMPO (cumplida en el mes)
     *   valor=1 con motivo → ATRASO_REGISTRADO (cumplida tardíamente)
     *   valor=0 con motivo → NO_CUMPLIDA (registrada como ✗ con justificación)
     *   sin registro       → FUTURO o ATRASO_NO_REGISTRADO
     * ===================================================== */
    private function evaluarAccion(Acciones $accion, int $mesActual): array
    {
        $estadoAccion = 'OK';
        $meses        = [];

        // Construir mapa: mes_numero → último HistorialAcciones
        // (tomamos el más reciente por mes para reflejar la corrección actual)
        $historialPorMes = [];
        foreach ($accion->getHistorialAcciones() as $h) {
            // Si hay múltiples registros del mismo mes, el último prevalece
            $historialPorMes[$h->getMes()] = $h;
        }

        foreach ($accion->getPeriodo() as $mesNombre) {
            $mes = $this->mesANumero($mesNombre);

            if ($mes > $mesActual) {
                $meses[] = ['mes' => $mes, 'estado' => 'FUTURO'];
                continue;
            }

            if (!isset($historialPorMes[$mes])) {
                // Mes pasado o actual sin ningún registro
                $meses[] = ['mes' => $mes, 'estado' => 'ATRASO_NO_REGISTRADO'];
                $estadoAccion = 'CRITICO';
                continue;
            }

            $registro = $historialPorMes[$mes];
            $cumplida = $registro->getValor() === 1;
            $conMotivo = $registro->getMotivo() !== null;

            if ($cumplida && !$conMotivo) {
                // Cumplida en tiempo (mes actual, sin justificación)
                $meses[] = ['mes' => $mes, 'estado' => 'EN_TIEMPO'];
            } elseif ($cumplida && $conMotivo) {
                // Cumplida tardíamente (mes pasado o corrección)
                $meses[] = ['mes' => $mes, 'estado' => 'ATRASO_REGISTRADO'];
                if ($estadoAccion === 'OK') {
                    $estadoAccion = 'ATENCION';
                }
            } else {
                // Marcada como ✗ (no cumplida, con motivo)
                $meses[] = ['mes' => $mes, 'estado' => 'NO_CUMPLIDA'];
                if ($estadoAccion === 'OK') {
                    $estadoAccion = 'ATENCION';
                }
            }
        }

        return [
            'accion_id'     => $accion->getId(),
            'descripcion'   => $accion->getAccion(),
            'estado_global' => $estadoAccion,
            'meses'         => $meses,
        ];
    }

    private function mesANumero(string $mes): int
    {
        $mapa = [
            'Enero'=>1,'Febrero'=>2,'Marzo'=>3,'Abril'=>4,
            'Mayo'=>5,'Junio'=>6,'Julio'=>7,'Agosto'=>8,
            'Septiembre'=>9,'Octubre'=>10,'Noviembre'=>11,'Diciembre'=>12,
        ];
        return $mapa[$mes] ?? 0;
    }

    private function buildGraficaFromResumen(int $total, int $enTiempo, int $atencion, int $critico): array
    {
        if ($total <= 0) {
            return [
                'total'      => 0,
                'en_tiempo'  => ['count' => 0, 'pct' => 0],
                'atencion'   => ['count' => 0, 'pct' => 0],
                'critico'    => ['count' => 0, 'pct' => 0],
            ];
        }

        $pct = fn(int $n) => (int) round(($n * 100) / $total);

        return [
            'total'     => $total,
            'en_tiempo' => ['count' => $enTiempo, 'pct' => $pct($enTiempo)],
            'atencion'  => ['count' => $atencion,  'pct' => $pct($atencion)],
            'critico'   => ['count' => $critico,   'pct' => $pct($critico)],
        ];
    }

    private function attachGraficaToResumenGlobal(array &$resultado): void
    {
        $total = $resultado['resumen_global']['total_ptas'] ?? 0;
        $ok    = $resultado['resumen_global']['ptas_ok']    ?? 0;
        $aten  = $resultado['resumen_global']['ptas_atencion'] ?? 0;
        $crit  = $resultado['resumen_global']['ptas_critico']  ?? 0;

        $resultado['resumen_global']['grafica'] = $this->buildGraficaFromResumen($total, $ok, $aten, $crit);
    }
}
