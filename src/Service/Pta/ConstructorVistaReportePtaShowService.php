<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Repository\ReportePtaTrimestreRepository;
use App\Repository\IndicadoresBasicosRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\PartidasPresupuestalesRepository;

class ConstructorVistaReportePtaShowService
{
    public function __construct(
        private ReportePtaTrimestreRepository $trimestreRepo,
        private IndicadoresBasicosRepository $indicadoresBasicosRepo,
        private ProcesoClaveRepository $procesoClaveRepo,
        private ProcesoEstrategicoRepository $procesoEstrategicoRepo,
        private PartidasPresupuestalesRepository $partidasRepo,
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

            // ✅ NOMBRE DEL INDICADOR (tu entidad real usa getIndicador(), no getNombre())
            $indicadorPta = $indicador->getIndicadorPta();
            $nombreIndicador = $indicadorPta ? ($indicadorPta->getIndicador() ?? ('Indicador ' . $indice)) : ('Indicador ' . $indice);

            // =========================
            // Indicador básico (puede venir como relación o como ID)
            // =========================
            $indicadorBasicoNombre = '';
            if (method_exists($indicador, 'getIndicadorBasico') && $indicador->getIndicadorBasico()) {
                $indicadorBasicoNombre = $indicador->getIndicadorBasico()?->getNombreIndicador() ?? '';
            } elseif (method_exists($indicador, 'getIndicadorBasicoId') && $indicador->getIndicadorBasicoId()) {
                $ib = $this->indicadoresBasicosRepo->find($indicador->getIndicadorBasicoId());
                $indicadorBasicoNombre = $ib?->getNombreIndicador() ?? '';
            }

            // =========================
            // Acciones (con procesos y partidas)
            // =========================
            $acciones = [];
            $accionIndex = 1;

            foreach ($indicador->getReportePtaAccions() as $accion) {

                // Proceso Estratégico (relación o ID)
                $procesoEstrategicoNombre = '';
                if (method_exists($accion, 'getProcesoEstrategico') && $accion->getProcesoEstrategico()) {
                    $procesoEstrategicoNombre = $accion->getProcesoEstrategico()?->getNombre() ?? '';
                } elseif (method_exists($accion, 'getProcesoEstrategicoId') && $accion->getProcesoEstrategicoId()) {
                    $pe = $this->procesoEstrategicoRepo->find($accion->getProcesoEstrategicoId());
                    $procesoEstrategicoNombre = $pe?->getNombre() ?? '';
                }

                // Proceso Clave (relación o ID)
                $procesoClaveNombre = '';
                if (method_exists($accion, 'getProcesoClave') && $accion->getProcesoClave()) {
                    $procesoClaveNombre = $accion->getProcesoClave()?->getNombre() ?? '';
                } elseif (method_exists($accion, 'getProcesoClaveId') && $accion->getProcesoClaveId()) {
                    $pc = $this->procesoClaveRepo->find($accion->getProcesoClaveId());
                    $procesoClaveNombre = $pc?->getNombre() ?? '';
                }

                // Partidas
                $partidas = [];
                $partidaIndex = 1;

                foreach ($accion->getReportePtaAccionPartidas() as $partida) {

                    // Partida presupuestal (relación o ID)
                    $pp = null;
                    if (method_exists($partida, 'getPartidaPresupuestal') && $partida->getPartidaPresupuestal()) {
                        $pp = $partida->getPartidaPresupuestal();
                    } elseif (method_exists($partida, 'getPartidaPresupuestalId') && $partida->getPartidaPresupuestalId()) {
                        $pp = $this->partidasRepo->find($partida->getPartidaPresupuestalId());
                    }

                    // Monto (en tu módulo a veces se llama cantidad o monto)
                    $monto = null;
                    if (method_exists($partida, 'getMonto')) {
                        $monto = $partida->getMonto();
                    } elseif (method_exists($partida, 'getCantidad')) {
                        $monto = $partida->getCantidad();
                    }

                    $partidas[$partidaIndex] = [
                        'capitulo'     => $pp?->getCapitulo(),
                        'partida'      => $pp?->getPartida(),
                        'descripcion'  => $pp?->getDescripcion(),
                        'monto'        => $monto,
                    ];

                    $partidaIndex++;
                }

                $acciones[$accionIndex] = [
                    'descripcion'         => method_exists($accion, 'getAccion') ? $accion->getAccion() : '',
                    'proceso_estrategico' => $procesoEstrategicoNombre,
                    'proceso_clave'       => $procesoClaveNombre,
                    'partidas'            => $partidas,
                ];

                $accionIndex++;
            }

            // =========================
            // Evidencias
            // =========================
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
                'nombre'              => $nombreIndicador,

                'meta'                => $indicador->getMeta(),
                'resultado'           => $indicador->getResultado(),
                'porcentaje'          => $indicador->getPorcentajeAvance(),
                'formula_descripcion' => $indicador->getFormulaDescripcion(),

                'indicador_basico'    => $indicadorBasicoNombre,
                'unidad_medida'       => $indicador->getUnidadMedida(),
                'medio_verificacion'  => $indicador->getMedioVerificacion(),
                'meta_cumplida'       => $indicador->getMetaCumplida(),

                'acciones'            => $acciones,
                'evidencias'          => $evidencias,
            ];
        }

        $responsable = $pta->getResponsable();
        $puesto = $responsable?->getPuesto();

        return [
            'pta' => [
                'id' => $pta->getId(),
                'nombre' => $pta->getNombre(),
                'objetivo' => $pta->getObjetivo(),
                'puesto_responsable' => $puesto?->getNombre(),
            ],
            'trimestre' => $numeroTrimestre,
            'reporte_trimestre_id' => $trimestre->getId(),
            'estado' => (bool) $trimestre->isEstado(),
            'entregado_fecha' => method_exists($trimestre, 'getEntregadoFecha') ? $trimestre->getEntregadoFecha() : null,
            'uploads' => [
                'base_url' => '/uploads/pta/' . $trimestre->getId() . '/',
            ],
            'indicadores' => $indicadores,
        ];
    }
}