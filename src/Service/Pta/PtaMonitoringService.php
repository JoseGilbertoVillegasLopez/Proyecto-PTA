<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Entity\Acciones;

class PtaMonitoringService
{
    /**
     * =====================================================
     * MONITOREO GENERAL PTA
     * =====================================================
     */
    public function monitor(
        array $ptas,
        int $anio,
        int $mesActual,
        array $contexto
    ): array {

        $rootType = $contexto['root_type'] ?? 'GLOBAL';
        $rootId   = $contexto['root_id'] ?? null;

         /*
         * =================================================
         * 1. FILTRAR PTAs SEGÚN NODO ACTUAL
         * =================================================
         */
        $ptas = $this->filtrarPtasPorContexto($ptas, $rootType, $rootId);

        /*
         * =================================================
         * 2. DETERMINAR NIVEL JERÁRQUICO
         * =================================================
         */
        $nivel = match ($rootType) {
            'GLOBAL'       => 'GLOBAL',
            'DEPARTAMENTO' => 'DEPARTAMENTO',
            'PUESTO'       => 'FINAL', // ⬅️ CLAVE
            default        => 'GLOBAL',
        };

        /*
         * =================================================
         * 3. ESTRUCTURA BASE
         * =================================================
         */
        $resultado = [
            'contexto' => [
                'nivel' => $nivel,
                'root_type' => $rootType,
                'root_id' => $rootId,
                'breadcrumb' => $this->buildBreadcrumb($rootType, $rootId),
            ],

            'resumen_global' => [
                'estado' => 'OK',
                'total_ptas' => 0,
                'ptas_ok' => 0,
                'ptas_atencion' => 0,
                'ptas_critico' => 0,
            ],

            'cards' => [],
            'ptas' => [],
            'graficas' => [
                'cumplimiento_general' => [
                    'en_tiempo' => 0,
                    'atrasado' => 0,
                ],
                'atrasos' => [
                    'registrados' => 0,
                    'no_registrados' => 0,
                ],
            ],
        ];

        /*
         * =================================================
         * 4. EVALUAR PTAs
         * =================================================
         */
        foreach ($ptas as $pta) {

            if (!$pta instanceof Encabezado) {
                continue;
            }

            $ptaData = $this->evaluarPta($pta, $mesActual);

            $resultado['ptas'][] = $ptaData;
            $resultado['resumen_global']['total_ptas']++;

            match ($ptaData['estado_global']) {
                'OK' => $resultado['resumen_global']['ptas_ok']++,
                'ATENCION' => $resultado['resumen_global']['ptas_atencion']++,
                'CRITICO' => $resultado['resumen_global']['ptas_critico']++,
            };
        }

        /*
         * =================================================
         * 5. ESTADO GLOBAL
         * =================================================
         */
        if ($resultado['resumen_global']['ptas_critico'] > 0) {
            $resultado['resumen_global']['estado'] = 'CRITICO';
        } elseif ($resultado['resumen_global']['ptas_atencion'] > 0) {
            $resultado['resumen_global']['estado'] = 'ATENCION';
        }
        // 5.5 GRAFICA GLOBAL (para el card global)
        $this->attachGraficaToResumenGlobal($resultado);

        /*
         * =================================================
         * 6. CARDS (SOLO SI NO ES FINAL)
         * =================================================
         */
        if ($nivel !== 'FINAL') {
            $resultado['cards'] = $this->buildCards($ptas, $nivel, $mesActual);
        }

        return $resultado;
    }

    /**
     * =====================================================
     * FILTRAR PTAs SEGÚN CONTEXTO
     * =====================================================
     */
    private function filtrarPtasPorContexto(array $ptas, string $rootType, ?int $rootId): array
    {
        if ($rootType === 'GLOBAL' || !$rootId) {
            return $ptas;
        }

        $filtrados = [];

        foreach ($ptas as $pta) {

            $puesto = $pta->getResponsable()->getPuesto();
            $departamento = $pta->getResponsable()->getDepartamento();

            if ($rootType === 'DEPARTAMENTO') {

                if ($departamento->getId() === (int) $rootId) {
                    $filtrados[] = $pta;
                }
            }

            if ($rootType === 'PUESTO') {

                if ($puesto->getId() === (int) $rootId) {
                    $filtrados[] = $pta;
                }
            }
        }

        return $filtrados;
    }

    /**
     * =====================================================
     * BREADCRUMB
     * =====================================================
     */
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
     * CONSTRUIR CARDS
     * ===================================================== */
    private function buildCards(array $ptas, string $nivel, int $mesActual): array
    {
        $cards = [];

        foreach ($ptas as $pta) {

            $responsable = $pta->getResponsable();
            $puesto = $responsable->getPuesto();
            $departamento = $responsable->getDepartamento();

            $key = match ($nivel) {
                'GLOBAL' => 'DEP_' . $departamento->getId(),
                'DEPARTAMENTO' => 'PUESTO_' . $puesto->getId(),
                default => null,
            };

            if (!$key) {
                continue;
            }

            if (!isset($cards[$key])) {
                $cards[$key] = [
                    'type' => $nivel === 'GLOBAL' ? 'DEPARTAMENTO' : 'PUESTO',
                    'id' => $nivel === 'GLOBAL'
                        ? $departamento->getId()
                        : $puesto->getId(),
                    'nombre' => $nivel === 'GLOBAL'
                        ? $departamento->getNombre()
                        : $puesto->getNombre(),
                    'estado_global' => 'OK',
                    'resumen' => [
                        'total_ptas' => 0,
                        'ok' => 0,
                        'atencion' => 0,
                        'critico' => 0,
                    ],
                    'link' => [
                        'root_type' => $nivel === 'GLOBAL' ? 'DEPARTAMENTO' : 'PUESTO',
                        'root_id' => $nivel === 'GLOBAL'
                            ? $departamento->getId()
                            : $puesto->getId(),
                    ],
                ];
            }

            $ptaEstado = $this->evaluarPta($pta, $mesActual)['estado_global'];

            $cards[$key]['resumen']['total_ptas']++;

            match ($ptaEstado) {
                'OK' => $cards[$key]['resumen']['ok']++,
                'ATENCION' => $cards[$key]['resumen']['atencion']++,
                'CRITICO' => $cards[$key]['resumen']['critico']++,
            };

            if ($ptaEstado === 'CRITICO') {
                $cards[$key]['estado_global'] = 'CRITICO';
            } elseif (
                $ptaEstado === 'ATENCION'
                && $cards[$key]['estado_global'] !== 'CRITICO'
            ) {
                $cards[$key]['estado_global'] = 'ATENCION';
            }
        }
        // Adjuntar grafica a cada card
foreach ($cards as &$c) {
    $t = $c['resumen']['total_ptas'];
    $ok = $c['resumen']['ok'];
    $at = $c['resumen']['atencion'];
    $cr = $c['resumen']['critico'];

    $c['grafica'] = $this->buildGraficaFromResumen($t, $ok, $at, $cr);
}
unset($c);


        return array_values($cards);
    }

    /* =====================================================
     * EVALUAR PTA
     * ===================================================== */
    private function evaluarPta(Encabezado $pta, int $mesActual): array
    {
        $estadoPta = 'OK';
        $accionesData = [];

        foreach ($pta->getAcciones() as $accion) {
            $accionData = $this->evaluarAccion($accion, $mesActual);
            $accionesData[] = $accionData;

            if ($accionData['estado_global'] === 'CRITICO') {
                $estadoPta = 'CRITICO';
            } elseif (
                $accionData['estado_global'] === 'ATENCION'
                && $estadoPta !== 'CRITICO'
            ) {
                $estadoPta = 'ATENCION';
            }
        }

        return [
            'pta_id' => $pta->getId(),
            'nombre' => $pta->getNombre(),
            'departamento' => $pta->getResponsable()->getDepartamento()->getNombre(),
            'puesto' => $pta->getResponsable()->getPuesto()->getNombre(),
            'estado_global' => $estadoPta,
            'acciones' => $accionesData,
        ];

    }

    /* =====================================================
     * EVALUAR ACCIÓN
     * ===================================================== */
    private function evaluarAccion(Acciones $accion, int $mesActual): array
    {
        $estadoAccion = 'OK';
        $meses = [];

        $enTiempo = [];
        foreach ($accion->getHistorialAcciones() as $h) {
            $enTiempo[$h->getMes()] = true;
        }

        $atrasos = [];
        foreach ($accion->getHistorialAccionesAtrasos() as $h) {
            $atrasos[$h->getMes()] = true;
        }

        foreach ($accion->getPeriodo() as $mesNombre) {

            $mes = $this->mesANumero($mesNombre);

            if ($mes > $mesActual) {
                $meses[] = ['mes' => $mes, 'estado' => 'FUTURO'];
                continue;
            }

            if (isset($enTiempo[$mes])) {
                $meses[] = ['mes' => $mes, 'estado' => 'EN_TIEMPO'];
                continue;
            }

            if (isset($atrasos[$mes])) {
                $meses[] = ['mes' => $mes, 'estado' => 'ATRASO_REGISTRADO'];
                if ($estadoAccion === 'OK') {
                    $estadoAccion = 'ATENCION';
                }
                continue;
            }

            $meses[] = ['mes' => $mes, 'estado' => 'ATRASO_NO_REGISTRADO'];
            $estadoAccion = 'CRITICO';
        }

        return [
            'accion_id' => $accion->getId(),
            'descripcion' => $accion->getAccion(),
            'estado_global' => $estadoAccion,
            'meses' => $meses,
        ];
    }

    private function mesANumero(string $mes): int
    {
        return match ($mes) {
            'Enero' => 1,
            'Febrero' => 2,
            'Marzo' => 3,
            'Abril' => 4,
            'Mayo' => 5,
            'Junio' => 6,
            'Julio' => 7,
            'Agosto' => 8,
            'Septiembre' => 9,
            'Octubre' => 10,
            'Noviembre' => 11,
            'Diciembre' => 12,
            default => 0,
        };
    }

    /* =====================================================
 * GRAFICA DESDE RESUMEN (3 BARRAS)
 * ===================================================== */
private function buildGraficaFromResumen(int $total, int $enTiempo, int $atencion, int $critico): array
{
    if ($total <= 0) {
        return [
            'total' => 0,
            'en_tiempo' => ['count' => 0, 'pct' => 0],
            'atencion'  => ['count' => 0, 'pct' => 0],
            'critico'   => ['count' => 0, 'pct' => 0],
        ];
    }

    $pct = fn(int $n) => (int) round(($n * 100) / $total);

    return [
        'total' => $total,
        'en_tiempo' => ['count' => $enTiempo, 'pct' => $pct($enTiempo)],
        'atencion'  => ['count' => $atencion, 'pct' => $pct($atencion)],
        'critico'   => ['count' => $critico, 'pct' => $pct($critico)],
    ];
}

/* =====================================================
 * ASIGNAR GRAFICA A RESUMEN GLOBAL
 * ===================================================== */
private function attachGraficaToResumenGlobal(array &$resultado): void
{
    $total   = $resultado['resumen_global']['total_ptas'] ?? 0;
    $ok      = $resultado['resumen_global']['ptas_ok'] ?? 0;
    $aten    = $resultado['resumen_global']['ptas_atencion'] ?? 0;
    $crit    = $resultado['resumen_global']['ptas_critico'] ?? 0;

    $resultado['resumen_global']['grafica'] = $this->buildGraficaFromResumen(
        $total,
        $ok,
        $aten,
        $crit
    );
}

}
