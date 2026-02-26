<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Entity\ReportePtaTrimestre;
use App\Entity\ReportePtaIndicador;
use App\Entity\ReportePtaAccion;
use App\Entity\ReportePtaAccionPartida;
use App\Entity\ReportePtaEvidencias;

use App\Repository\ReportePtaTrimestreRepository;
use App\Repository\IndicadoresBasicosRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\PartidasPresupuestalesRepository;

use Doctrine\ORM\EntityManagerInterface;

class GuardarReportePtaService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReportePtaTrimestreRepository $trimestreRepo,
        private IndicadoresBasicosRepository $indicadorBasicoRepo,
        private ProcesoEstrategicoRepository $procesoEstrategicoRepo,
        private ProcesoClaveRepository $procesoClaveRepo,
        private PartidasPresupuestalesRepository $partidaRepo,
        private string $projectDir
    ) {}

    public function guardar(
        Encabezado $encabezado,
        int $numeroTrimestre,
        array $requestData,
        array $files,
        array $datosCalculados
    ): void {

        $this->em->beginTransaction();

        try {

            $this->verificarNoExisteTrimestre($encabezado, $numeroTrimestre);

            $trimestre = $this->crearTrimestre($encabezado, $numeroTrimestre);

            $this->em->persist($trimestre);
            $this->em->flush();

            $rutaBase = $this->crearCarpetaTrimestre($trimestre->getId());

            $this->crearIndicadores(
                $trimestre,
                $requestData,
                $files,
                $datosCalculados,
                $rutaBase
            );

            $this->em->flush();
            $this->em->commit();

        } catch (\Throwable $e) {

            $this->em->rollback();
            throw $e;
        }
    }

    private function verificarNoExisteTrimestre(Encabezado $encabezado, int $numero): void
    {
        $existe = $this->trimestreRepo->findOneBy([
            'encabezado' => $encabezado,
            'trimestre' => $numero
        ]);

        if ($existe) {
            throw new \DomainException('Ya existe un reporte para este trimestre.');
        }
    }

    private function crearTrimestre(Encabezado $encabezado, int $numero): ReportePtaTrimestre
    {
        $trimestre = new ReportePtaTrimestre();
        $trimestre->setEncabezado($encabezado);
        $trimestre->setAnio($encabezado->getAnioEjecucion());
        $trimestre->setTrimestre($numero);
        $trimestre->setEstado(false);
        $trimestre->setCreadoFecha(new \DateTime());

        return $trimestre;
    }

    private function crearCarpetaTrimestre(int $id): string
    {
        $ruta = $this->projectDir . '/public/uploads/pta/' . $id . '/';

        if (!is_dir($ruta)) {
            mkdir($ruta, 0777, true);
        }

        return $ruta;
    }

    private function crearIndicadores(
        ReportePtaTrimestre $trimestre,
        array $requestData,
        array $files,
        array $datosCalculados,
        string $rutaBase
    ): void {

        foreach ($datosCalculados['resultados'] as $indice => $datosIndicador) {

            $requestIndicador = $requestData['reporte']['indicadores'][$indice] ?? null;
            if (!$requestIndicador) continue;

            $indicador = new ReportePtaIndicador();
            $indicador->setReporteTrimestre($trimestre);

            $indicadorBasico = $this->indicadorBasicoRepo
                ->find($requestIndicador['indicador_basico']);

            $indicador->setIndicadorBasico($indicadorBasico);
            $indicador->setIndicadorPta($datosIndicador['indicadorPta']);
            $indicador->setUnidadMedida($requestIndicador['unidad_medida']);
            $indicador->setMeta($datosIndicador['meta']);
            $indicador->setResultado($datosIndicador['resultado']);
            $indicador->setPorcentajeAvance($datosIndicador['porcentaje']);
            $indicador->setFormulaDescripcion($datosIndicador['formula_descripcion']);
            $indicador->setMedioVerificacion($requestIndicador['medio_verificacion']);
            $indicador->setMetaCumplida(
                $datosIndicador['porcentaje'] >= 100 ? 'Sí' : 'No'
            );
            $indicador->setResponsablePuesto($datosIndicador['responsablePuesto']);

            $this->em->persist($indicador);

            $this->crearAcciones($indicador, $requestData, $indice);
            $this->crearEvidencias($indicador, $requestData, $files, $indice, $rutaBase);
        }
    }

    private function crearAcciones(
        ReportePtaIndicador $indicador,
        array $requestData,
        int $indice
    ): void {

        $acciones = $requestData['acciones'][$indice] ?? [];

        foreach ($acciones as $accionIndex => $accionData) {

            $accion = new ReportePtaAccion();
            $accion->setReporteIndicador($indicador);
            $accion->setAccion($accionData['descripcion']);

            $procesoE = $this->procesoEstrategicoRepo
                ->find($requestData['gastos'][$indice][$accionIndex]['proceso_estrategico']);

            $procesoC = $this->procesoClaveRepo
                ->find($requestData['gastos'][$indice][$accionIndex]['proceso_clave']);

            $accion->setProcesoEstrategico($procesoE);
            $accion->setProcesoClave($procesoC);

            $this->em->persist($accion);

            $this->crearPartidas($accion, $requestData, $indice, $accionIndex);
        }
    }

    private function crearPartidas(
        ReportePtaAccion $accion,
        array $requestData,
        int $indice,
        int $accionIndex
    ): void {

        $partidas = $requestData['gastos'][$indice][$accionIndex]['partidas'] ?? [];

        foreach ($partidas as $partidaData) {

            $partida = new ReportePtaAccionPartida();
            $partida->setReporteAccion($accion);

            $entidadPartida = $this->partidaRepo
                ->find($partidaData['partida_id']);

            $partida->setPartidaPresupuestal($entidadPartida);
            $partida->setCantidad($partidaData['monto']);

            $this->em->persist($partida);
        }
    }

    private function crearEvidencias(
        ReportePtaIndicador $indicador,
        array $requestData,
        array $files,
        int $indice,
        string $rutaBase
    ): void {

        $bloquesDescripcion = $requestData['evidencias'][$indice] ?? [];
        $bloquesFiles = $files['evidencias'][$indice] ?? [];

        foreach ($bloquesDescripcion as $bloqueIndex => $bloqueData) {

            $imagenesGuardadas = [];

            if (isset($bloquesFiles[$bloqueIndex]['imagenes'])) {

                foreach ($bloquesFiles[$bloqueIndex]['imagenes'] as $file) {

                    if (!$file) continue;

                    $extension = $file->guessExtension() ?: 'jpg';

                    // 🔥 UUID nativo sin dependencias
                    $nombre = bin2hex(random_bytes(16)) . '.' . $extension;

                    $file->move($rutaBase, $nombre);
                    $imagenesGuardadas[] = $nombre;
                }
            }

            $evidencia = new ReportePtaEvidencias();
            $evidencia->setReportePtaIndicador($indicador);
            $evidencia->setDescripcion($bloqueData['descripcion'] ?? '');
            $evidencia->setImagenes($imagenesGuardadas);

            $this->em->persist($evidencia);
        }
    }
}