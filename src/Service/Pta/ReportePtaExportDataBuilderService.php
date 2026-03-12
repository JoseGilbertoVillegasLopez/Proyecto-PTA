<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Entity\ReportePtaIndicador;
use App\Entity\ReportePtaTrimestre;
use App\Repository\ReportePtaTrimestreRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReportePtaExportDataBuilderService
{
    public function __construct(
    private ReportePtaTrimestreRepository $trimestreRepository,
    private ParameterBagInterface $parameterBag,
) {
}

    /**
     * =========================================================
     * BUILD
     * ---------------------------------------------------------
     * Construye toda la data necesaria para exportar un reporte
     * PTA trimestral a Word o PDF.
     * =========================================================
     */
    public function build(Encabezado $encabezado, int $numeroTrimestre): array
    {
        $reporteTrimestre = $this->trimestreRepository->findOneBy([
            'encabezado' => $encabezado,
            'anio'       => $encabezado->getAnioEjecucion(),
            'trimestre'  => $numeroTrimestre,
        ]);

        if (!$reporteTrimestre) {
            throw new \DomainException('No existe el reporte para este trimestre.');
        }

        $indicadores = [];

        foreach ($reporteTrimestre->getReportePtaIndicadors() as $reporteIndicador) {
            $indicadores[] = $this->buildIndicadorData($reporteIndicador, $reporteTrimestre);
        }

        usort($indicadores, function (array $a, array $b) {
            return ($a['indice'] ?? 9999) <=> ($b['indice'] ?? 9999);
        });

        return [
            'reporte_trimestre_id' => $reporteTrimestre->getId(),
            'anio'                 => $reporteTrimestre->getAnio(),
            'trimestre'            => $reporteTrimestre->getTrimestre(),
            'trimestre_label'      => $this->getTrimestreLabel($reporteTrimestre->getTrimestre()),
            'resultado_label'      => $this->getResultadoLabel($reporteTrimestre->getTrimestre()),
            'estado'               => $reporteTrimestre->isEstado(),
            'creado_fecha'         => $reporteTrimestre->getCreadoFecha(),
            'entregado_fecha'      => $reporteTrimestre->getEntregadoFecha(),

            'pta' => [
                'id'       => $encabezado->getId(),
                'nombre'   => $encabezado->getNombre(),
                'objetivo' => $encabezado->getObjetivo(),
                'anio'     => $encabezado->getAnioEjecucion(),
            ],

            'indicadores' => $indicadores,
        ];
    }

    /**
     * =========================================================
     * BUILD INDICADOR
     * =========================================================
     */
    private function buildIndicadorData(
        ReportePtaIndicador $reporteIndicador,
        ReportePtaTrimestre $reporteTrimestre
    ): array {
        $indicadorPta = $reporteIndicador->getIndicadorPta();
        $indicadorBasico = $reporteIndicador->getIndicadorBasico();
        $responsablePuesto = $reporteIndicador->getResponsablePuesto();

        $acciones = [];
        foreach ($reporteIndicador->getReportePtaAccions() as $accion) {
            $acciones[] = [
                'descripcion'         => $accion->getAccion(),
                'proceso_estrategico' => $accion->getProcesoEstrategico()?->getNombre(),
                'proceso_clave'       => $accion->getProcesoClave()?->getNombre(),
            ];
        }

        $evidencias = [];
        foreach ($reporteIndicador->getReportePtaEvidencias() as $evidencia) {
            $imagenes = [];

            foreach ($evidencia->getImagenes() as $nombreImagen) {
                $rutaFisica = $this->buildImageAbsolutePath(
                    $reporteTrimestre->getId(),
                    $nombreImagen
                );

                $imagenes[] = [
                    'nombre'      => $nombreImagen,
                    'path'        => $rutaFisica,
                    'exists'      => is_file($rutaFisica),
                    'public_path' => '/uploads/pta/' . $reporteTrimestre->getId() . '/' . $nombreImagen,
                ];
            }

            $evidencias[] = [
                'descripcion' => $evidencia->getDescripcion(),
                'imagenes'    => $imagenes,
            ];
        }

        return [
            'id'                   => $reporteIndicador->getId(),
            'indice'               => $indicadorPta?->getIndice(),
            'indicador_basico'     => $indicadorBasico?->getNombreIndicador(),
            'indicador_pta'        => $indicadorPta?->getIndicador(),
            'unidad_medida'        => $reporteIndicador->getUnidadMedida(),
            'meta'                 => $reporteIndicador->getMeta(),
            'resultado'            => $reporteIndicador->getResultado(),
            'porcentaje_avance'    => $reporteIndicador->getPorcentajeAvance(),
            'formula_descripcion'  => $reporteIndicador->getFormulaDescripcion(),
            'formula_empleada'     => $reporteIndicador->getFormula(),
            'medio_verificacion'   => $reporteIndicador->getMedioVerificacion(),
            'meta_cumplida'        => $reporteIndicador->getMetaCumplida(),
            'responsable_puesto'   => $responsablePuesto?->getNombre(),
            'acciones'             => $acciones,
            'evidencias'           => $evidencias,
        ];
    }

    /**
     * =========================================================
     * BUILD IMAGE ABSOLUTE PATH
     * =========================================================
     */
    private function buildImageAbsolutePath(int $reporteTrimestreId, string $nombreImagen): string
{
    return $this->parameterBag->get('kernel.project_dir')
        . '/public/uploads/pta/'
        . $reporteTrimestreId
        . '/'
        . basename($nombreImagen);
}

    /**
     * =========================================================
     * LABEL DEL TRIMESTRE
     * =========================================================
     */
    private function getTrimestreLabel(?int $trimestre): string
    {
        return match ($trimestre) {
            1 => 'Enero - Marzo',
            2 => 'Abril - Junio',
            3 => 'Julio - Septiembre',
            4 => 'Octubre - Diciembre',
            default => 'Trimestre desconocido',
        };
    }

    /**
     * =========================================================
     * LABEL DEL RESULTADO SEGÚN TRIMESTRE
     * =========================================================
     */
    private function getResultadoLabel(?int $trimestre): string
    {
        return match ($trimestre) {
            1 => 'Resultado a marzo',
            2 => 'Resultado a junio',
            3 => 'Resultado a septiembre',
            4 => 'Resultado a diciembre',
            default => 'Resultado del trimestre',
        };
    }
}