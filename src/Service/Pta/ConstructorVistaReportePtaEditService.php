<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Repository\ReportePtaTrimestreRepository;

class ConstructorVistaReportePtaEditService
{
    private ConstructorVistaReportePtaService $constructorNew;
    private ReportePtaTrimestreRepository $trimestreRepository;

    public function __construct(
        ConstructorVistaReportePtaService $constructorNew,
        ReportePtaTrimestreRepository $trimestreRepository
    ) {
        $this->constructorNew = $constructorNew;
        $this->trimestreRepository = $trimestreRepository;
    }

    /**
     * =========================================================
     * Construye datos completos para la vista EDIT
     * =========================================================
     */
    public function build(Encabezado $pta, int $numeroTrimestre): array
    {
        // =====================================================
        // 1️⃣ Construir base (igual que NEW)
        // =====================================================
        $base = $this->constructorNew->build($pta, $numeroTrimestre);

        // =====================================================
        // 2️⃣ Buscar trimestre existente
        // =====================================================
        $reporteTrimestre = $this->trimestreRepository->findOneBy([
            'encabezado' => $pta,
            'trimestre'  => $numeroTrimestre
        ]);

        if (!$reporteTrimestre) {
            throw new \RuntimeException('El reporte del trimestre no existe.');
        }

        // =====================================================
        // 3️⃣ Construir estructura inicial precargada
        // =====================================================
        $inicial = [];

        foreach ($reporteTrimestre->getReportePtaIndicadors() as $indicadorGuardado) {

            $indice = $indicadorGuardado->getIndicadorPta()?->getIndice();

            if ($indice === null) {
                continue;
            }

            $inicial[$indice] = [
                'indicador_basico_id' => $indicadorGuardado->getIndicadorBasico()?->getId(),
                'unidad_medida'       => $indicadorGuardado->getUnidadMedida(),
                'medio_verificacion'  => $indicadorGuardado->getMedioVerificacion(),
                'meta_cumplida'       => $indicadorGuardado->getMetaCumplida(),
                'formula_opcional'    => $indicadorGuardado->getFormula(),
                'acciones'            => [],
                'evidencias'          => []
            ];

            // =================================================
            // 4️⃣ Acciones
            // =================================================
            $accionIndex = 1;

            foreach ($indicadorGuardado->getReportePtaAccions() as $accion) {

                $inicial[$indice]['acciones'][$accionIndex] = [
                    'descripcion'           => $accion->getAccion(),
                    'proceso_estrategico_id'=> $accion->getProcesoEstrategico()?->getId(),
                    'proceso_clave_id'      => $accion->getProcesoClave()?->getId(),
                    'partidas'              => []
                ];

                $partidaIndex = 1;

                foreach ($accion->getReportePtaAccionPartidas() as $partida) {

                    $inicial[$indice]['acciones'][$accionIndex]['partidas'][$partidaIndex] = [
                        'partida_id' => $partida->getPartidaPresupuestal()?->getId(),
                        'cantidad'   => $partida->getCantidad()
                    ];

                    $partidaIndex++;
                }

                $accionIndex++;
            }

            // =================================================
            // 5️⃣ Evidencias
            // =================================================
            $bloqueIndex = 1;

            foreach ($indicadorGuardado->getReportePtaEvidencias() as $evidencia) {

                $inicial[$indice]['evidencias'][$bloqueIndex] = [
                    'descripcion' => $evidencia->getDescripcion(),
                    'imagenes'    => $evidencia->getImagenes() ?? []
                ];

                $bloqueIndex++;
            }
        }

        // =====================================================
        // 6️⃣ Retornar todo combinado
        // =====================================================
        return array_merge($base, [
            'reporte_trimestre_id' => $reporteTrimestre->getId(),
            'inicial'              => $inicial,
            'uploads'              => [
                'base_url' => '/uploads/pta/' . $reporteTrimestre->getId() . '/'
            ]
        ]);
    }
}