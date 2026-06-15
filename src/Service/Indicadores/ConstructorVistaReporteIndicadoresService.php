<?php

namespace App\Service\Indicadores;

use App\Entity\Personal;
use App\Entity\ReporteIndicadorTrimestre;
use App\Repository\ReporteIndicadorTrimestreRepository;

class ConstructorVistaReporteIndicadoresService
{
    public function __construct(
        private ReporteIndicadorTrimestreRepository $reporteRepository
    ) {
    }

    public function build(Personal $personal, ?int $anio = null, ?\DateTimeImmutable $fechaActual = null): array
    {
        $hoy = $fechaActual ?? new \DateTimeImmutable('today');
        $anio ??= (int) $hoy->format('Y');
        $reportes = $this->reporteRepository->findByPersonalAndAnio($personal, $anio);

        $reportesPorTrimestre = [];
        foreach ($reportes as $reporte) {
            $reportesPorTrimestre[(int) $reporte->getTrimestre()] = $reporte;
        }

        $trimestres = [];
        foreach ($this->getTrimestresConfig($anio) as $numero => $config) {
            $reporte = $reportesPorTrimestre[$numero] ?? null;
            $estado = $this->resolverEstado($reporte, $config['ventana_inicio'], $config['ventana_fin'], $hoy);

            $trimestres[] = [
                'numero' => $numero,
                'nombre' => $config['nombre'],
                'periodo_label' => $config['periodo_label'],
                'ventana_inicio' => $config['ventana_inicio'],
                'ventana_fin' => $config['ventana_fin'],
                'ventana_texto' => sprintf(
                    'Del %s al %s',
                    $config['ventana_inicio']->format('d/m/Y'),
                    $config['ventana_fin']->format('d/m/Y')
                ),
                'estado_key' => $estado['key'],
                'estado_label' => $estado['label'],
                'estado_mensaje' => $estado['mensaje'],
                'creado_fecha' => $reporte?->getCreadoFecha(),
                'entregado_fecha' => $reporte?->getEntregadoFecha(),
                'reporte_id' => $reporte?->getId(),
                'puede_abrir' => $estado['puede_abrir'],
                'puede_crear' => $estado['puede_crear'],
            ];
        }

        return [
            'anio_actual' => $anio,
            'personal' => $personal,
            'fechas_reporte' => $this->buildCalendario($anio),
            'trimestres' => $trimestres,
            'hoy' => $hoy,
        ];
    }

    private function getTrimestresConfig(int $anio): array
    {
        $anioSiguiente = $anio + 1;

        return [
            1 => [
                'nombre' => 'Enero - Marzo',
                'periodo_label' => 'Periodo enero-marzo',
                'ventana_inicio' => new \DateTimeImmutable($anio . '-04-01'),
                'ventana_fin' => new \DateTimeImmutable($anio . '-04-30'),
            ],
            2 => [
                'nombre' => 'Abril - Junio',
                'periodo_label' => 'Periodo abril-junio',
                'ventana_inicio' => new \DateTimeImmutable($anio . '-07-01'),
                'ventana_fin' => new \DateTimeImmutable($anio . '-07-30'),
            ],
            3 => [
                'nombre' => 'Julio - Septiembre',
                'periodo_label' => 'Periodo julio-septiembre',
                'ventana_inicio' => new \DateTimeImmutable($anio . '-10-01'),
                'ventana_fin' => new \DateTimeImmutable($anio . '-10-30'),
            ],
            4 => [
                'nombre' => 'Octubre - Diciembre',
                'periodo_label' => 'Periodo octubre-diciembre',
                'ventana_inicio' => new \DateTimeImmutable($anioSiguiente . '-01-01'),
                'ventana_fin' => new \DateTimeImmutable($anioSiguiente . '-01-30'),
            ],
        ];
    }

    private function buildCalendario(int $anio): array
    {
        return [
            [
                'periodo' => 'Enero - Marzo',
                'fecha' => 'Del 01 de abril al 30 de abril de ' . $anio,
            ],
            [
                'periodo' => 'Abril - Junio',
                'fecha' => 'Del 01 de julio al 30 de julio de ' . $anio,
            ],
            [
                'periodo' => 'Julio - Septiembre',
                'fecha' => 'Del 01 de octubre al 30 de octubre de ' . $anio,
            ],
            [
                'periodo' => 'Octubre - Diciembre',
                'fecha' => 'Del 01 de enero al 30 de enero de ' . ($anio + 1),
            ],
        ];
    }

    private function resolverEstado(
        ?ReporteIndicadorTrimestre $reporte,
        \DateTimeImmutable $inicioVentana,
        \DateTimeImmutable $finVentana,
        \DateTimeImmutable $hoy
    ): array {
        if ($reporte?->isEntregado()) {
            return [
                'key' => 'entregado',
                'label' => 'Entregado',
                'mensaje' => 'El reporte ya fue entregado.',
                'puede_abrir' => true,
                'puede_crear' => false,
            ];
        }

        if ($hoy > $finVentana) {
            return [
                'key' => 'retraso',
                'label' => 'Retraso',
                'mensaje' => 'El reporte tiene retraso. Es necesario que lo entregues.',
                'puede_abrir' => $reporte !== null,
                'puede_crear' => $reporte === null,
            ];
        }

        if ($reporte) {
            return [
                'key' => 'borrador',
                'label' => 'Borrador',
                'mensaje' => 'El reporte ya fue creado, pero aun no ha sido entregado.',
                'puede_abrir' => true,
                'puede_crear' => false,
            ];
        }

        if ($hoy < $inicioVentana) {
            return [
                'key' => 'pendiente',
                'label' => 'Pendiente',
                'mensaje' => 'Aun no es fecha para abrir este reporte.',
                'puede_abrir' => false,
                'puede_crear' => false,
            ];
        }

        return [
            'key' => 'sin_entregar',
            'label' => 'Sin entregar',
            'mensaje' => 'Ya puedes crear este reporte trimestral.',
            'puede_abrir' => false,
            'puede_crear' => true,
        ];
    }
}
