<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Repository\ReportePtaTrimestreRepository;

class ConstructorVistaReportePtaIndexService
{
    public function __construct(
        private ReportePtaTrimestreRepository $reporteRepository
    ) {
    }

    public function build(Encabezado $encabezado): array
    {
        /*
        |--------------------------------------------------------------------------
        | AÑO DEL PTA
        |--------------------------------------------------------------------------
        | Este NO depende del reloj del sistema.
        | Siempre usamos el año de ejecución real del PTA.
        |--------------------------------------------------------------------------
        */
        $anioPta = (int) $encabezado->getAnioEjecucion();

        /*
        |--------------------------------------------------------------------------
        | FECHA "HOY" PARA LÓGICA DE ESTADOS
        |--------------------------------------------------------------------------
        | OPCIÓN 1 (REAL):
        | Usa la fecha actual del sistema.
        |
        | OPCIÓN 2 (PRUEBAS):
        | Descomenta la línea manual y comenta la real para simular fechas.
        |
        | Ejemplos de pruebas:
        | new \DateTimeImmutable('2026-04-10') -> trimestre 1 disponible
        | new \DateTimeImmutable('2026-07-05') -> trimestre 2 disponible
        | new \DateTimeImmutable('2026-10-15') -> trimestre 3 disponible
        | new \DateTimeImmutable('2027-01-10') -> trimestre 4 disponible
        |--------------------------------------------------------------------------
        */

        // ===== OPCIÓN REAL =====
        //$hoy = new \DateTimeImmutable('today');

        // ===== OPCIÓN PRUEBAS =====
        $hoy = new \DateTimeImmutable('2026-04-10');

        /*
        |--------------------------------------------------------------------------
        | REPORTES EXISTENTES DEL PTA
        |--------------------------------------------------------------------------
        | OJO:
        | Todos los trimestres pertenecen al año del PTA.
        | Incluso el trimestre 4, aunque se capture en enero del año siguiente,
        | sigue guardándose con el anio del PTA.
        |--------------------------------------------------------------------------
        */
        $reportes = $this->reporteRepository->findBy([
            'encabezado' => $encabezado,
            'anio'       => $anioPta,
        ]);

        $reportesPorTrimestre = [];
        foreach ($reportes as $reporte) {
            $reportesPorTrimestre[(int) $reporte->getTrimestre()] = $reporte;
        }

        $trimestresConfig = $this->getTrimestresConfig($anioPta);

        $trimestres = [];

        foreach ($trimestresConfig as $numero => $config) {
            $reporte = $reportesPorTrimestre[$numero] ?? null;

            $estado = $this->resolverEstado(
                $reporte,
                $config['ventana_inicio'],
                $config['ventana_fin'],
                $hoy
            );

            $trimestres[] = [
                'numero'           => $numero,
                'nombre'           => $config['nombre'],
                'periodo_label'    => $config['periodo_label'],
                'ventana_inicio'   => $config['ventana_inicio'],
                'ventana_fin'      => $config['ventana_fin'],
                'ventana_texto'    => sprintf(
                    'Del %s al %s',
                    $config['ventana_inicio']->format('d/m/Y'),
                    $config['ventana_fin']->format('d/m/Y')
                ),

                'estado_key'       => $estado['key'],
                'estado_label'     => $estado['label'],
                'estado_mensaje'   => $estado['mensaje'],

                'creado_fecha'     => $reporte?->getCreadoFecha(),
                'entregado_fecha'  => $reporte?->getEntregadoFecha(),

                'puede_ver'        => $estado['puede_ver'],
                'puede_editar'     => $estado['puede_editar'],
                'puede_crear'      => $estado['puede_crear'],
            ];
        }

        return [
            'anio_actual'     => $anioPta,
            'encabezado'      => $encabezado,
            'fechas_reporte'  => $this->buildCalendario($anioPta),
            'trimestres'      => $trimestres,

            /*
            |--------------------------------------------------------------------------
            | SOLO INFORMATIVO PARA DEBUG
            |--------------------------------------------------------------------------
            | Esto te deja ver qué fecha está usando el servicio.
            |--------------------------------------------------------------------------
            */
            'debug_hoy'       => $hoy,
        ];
    }

    private function getTrimestresConfig(int $anioPta): array
    {
        $anioSiguiente = $anioPta + 1;

        return [
            1 => [
                'nombre'         => 'Enero - Marzo',
                'periodo_label'  => 'Periodo enero-marzo',
                'ventana_inicio' => new \DateTimeImmutable($anioPta . '-04-01'),
                'ventana_fin'    => new \DateTimeImmutable($anioPta . '-04-30'),
            ],
            2 => [
                'nombre'         => 'Abril - Junio',
                'periodo_label'  => 'Periodo abril-junio',
                'ventana_inicio' => new \DateTimeImmutable($anioPta . '-07-01'),
                'ventana_fin'    => new \DateTimeImmutable($anioPta . '-07-30'),
            ],
            3 => [
                'nombre'         => 'Julio - Septiembre',
                'periodo_label'  => 'Periodo julio-septiembre',
                'ventana_inicio' => new \DateTimeImmutable($anioPta . '-10-01'),
                'ventana_fin'    => new \DateTimeImmutable($anioPta . '-10-30'),
            ],
            4 => [
                'nombre'         => 'Octubre - Diciembre',
                'periodo_label'  => 'Periodo octubre-diciembre',
                'ventana_inicio' => new \DateTimeImmutable($anioSiguiente . '-01-01'),
                'ventana_fin'    => new \DateTimeImmutable($anioSiguiente . '-01-30'),
            ],
        ];
    }

    private function buildCalendario(int $anioPta): array
    {
        $anioSiguiente = $anioPta + 1;

        return [
            [
                'periodo' => 'Enero - Marzo',
                'fecha'   => 'Del 01 de abril al 30 de abril de ' . $anioPta,
            ],
            [
                'periodo' => 'Abril - Junio',
                'fecha'   => 'Del 01 de julio al 30 de julio de ' . $anioPta,
            ],
            [
                'periodo' => 'Julio - Septiembre',
                'fecha'   => 'Del 01 de octubre al 30 de octubre de ' . $anioPta,
            ],
            [
                'periodo' => 'Octubre - Diciembre',
                'fecha'   => 'Del 01 de enero al 30 de enero de ' . $anioSiguiente,
            ],
        ];
    }

    private function resolverEstado(
        mixed $reporte,
        \DateTimeImmutable $inicioVentana,
        \DateTimeImmutable $finVentana,
        \DateTimeImmutable $hoy
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1) SI YA EXISTE REPORTE
        |--------------------------------------------------------------------------
        */
        if ($reporte) {
            if ($reporte->isEstado()) {
                return [
                    'key'          => 'entregado',
                    'label'        => 'Entregado',
                    'mensaje'      => 'El reporte ya fue entregado.',
                    'puede_ver'    => true,
                    'puede_editar' => false,
                    'puede_crear'  => false,
                ];
            }

            return [
                'key'          => 'en_proceso',
                'label'        => 'En proceso',
                'mensaje'      => 'El reporte ya fue creado, pero aún no ha sido entregado.',
                'puede_ver'    => true,
                'puede_editar' => true,
                'puede_crear'  => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2) SI AÚN NO LLEGA LA FECHA
        |--------------------------------------------------------------------------
        */
        if ($hoy < $inicioVentana) {
            return [
                'key'          => 'pendiente',
                'label'        => 'Pendiente',
                'mensaje'      => 'Aún no es fecha para crear este reporte.',
                'puede_ver'    => false,
                'puede_editar' => false,
                'puede_crear'  => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3) SI YA LLEGÓ LA FECHA Y NO EXISTE
        |--------------------------------------------------------------------------
        */
        return [
            'key'          => 'sin_entregar',
            'label'        => 'Sin entregar',
            'mensaje'      => 'Ya puedes crear este reporte trimestral.',
            'puede_ver'    => false,
            'puede_editar' => false,
            'puede_crear'  => true,
        ];
    }
}