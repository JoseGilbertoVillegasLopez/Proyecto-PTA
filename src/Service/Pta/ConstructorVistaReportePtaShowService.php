<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Repository\ReportePtaTrimestreRepository;

class ConstructorVistaReportePtaShowService
{
    public function __construct(
        private ReportePtaTrimestreRepository $trimestreRepo,
    ) {}

    public function build(Encabezado $pta, int $numeroTrimestre): array
    {
        $trimestre = $this->trimestreRepo->findOneBy([
            'encabezado' => $pta,
            'trimestre'  => $numeroTrimestre,
        ]);

        if (!$trimestre) {
            throw new \DomainException('El reporte del trimestre no existe.');
        }

        $indicadores = [];

        foreach ($trimestre->getReportePtaIndicadors() as $indicador) {

            $indice = $indicador->getIndicadorPta()?->getIndice();
            if ($indice === null) {
                continue;
            }

            // Acciones
            $acciones = [];
            $accionIndex = 1;

            foreach ($indicador->getReportePtaAccions() as $accion) {

                // Partidas
                $partidas = [];
                $partidaIndex = 1;

                foreach ($accion->getReportePtaAccionPartidas() as $partida) {
                    $pp = $partida->getPartidaPresupuestal();

                    $partidas[$partidaIndex] = [
                        'capitulo' => $pp?->getCapitulo(),
                        'partida'  => $pp?->getPartida(),
                        'descripcion' => $pp?->getDescripcion(),
                        'monto'    => $partida->getCantidad(),
                    ];

                    $partidaIndex++;
                }

                $acciones[$accionIndex] = [
                    'descripcion' => $accion->getAccion(),
                    'proceso_estrategico' => $accion->getProcesoEstrategico()?->getNombre(),
                    'proceso_clave'       => $accion->getProcesoClave()?->getNombre(),
                    'partidas'            => $partidas,
                ];

                $accionIndex++;
            }

            // Evidencias
            $evidencias = [];
            $bloqueIndex = 1;

            foreach ($indicador->getReportePtaEvidencias() as $evidencia) {

                $evidencias[$bloqueIndex] = [
                    'descripcion' => $evidencia->getDescripcion(),
                    'imagenes'    => $evidencia->getImagenes() ?? [],
                ];

                $bloqueIndex++;
            }

            $indicadores[$indice] = [
                // Para accordion header
                'nombre' => $indicador->getIndicadorPta()?->getNombre() ?? ('Indicador ' . $indice),

                // Datos guardados (snapshot)
                'meta'       => $indicador->getMeta(),
                'resultado'  => $indicador->getResultado(),
                'porcentaje' => $indicador->getPorcentajeAvance(),
                'formula_descripcion' => $indicador->getFormulaDescripcion(),

                // Info reporte
                'indicador_basico' => $indicador->getIndicadorBasico()?->getNombreIndicador(),
                'unidad_medida' => $indicador->getUnidadMedida(),
                'medio_verificacion' => $indicador->getMedioVerificacion(),
                'meta_cumplida' => $indicador->getMetaCumplida(),

                // Hijos
                'acciones'   => $acciones,
                'evidencias' => $evidencias,
            ];
        }

        return [
            'pta' => [
                'id' => $pta->getId(),
                'nombre' => $pta->getNombre(),
                'objetivo' => $pta->getObjetivo(),
                'puesto_responsable' => $pta->getResponsable()?->getPuesto()?->getNombre(),
            ],
            'trimestre' => $numeroTrimestre,
            'reporte_trimestre_id' => $trimestre->getId(),
            'estado' => (bool) $trimestre->isEstado(),
            'entregado_fecha' => $trimestre->getEntregadoFecha(),
            'uploads' => [
                'base_url' => '/uploads/pta/' . $trimestre->getId() . '/',
            ],
            'indicadores' => $indicadores,
        ];
    }
}