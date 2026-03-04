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
use App\Repository\IndicadoresRepository;
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
        private IndicadoresRepository $indicadorRepo,
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
                $encabezado,
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

        /**
     * =========================================================
     * ACTUALIZAR REPORTE (EDIT)
     * ---------------------------------------------------------
     * Elimina hijos y vuelve a crear todo limpio
     * Mantiene el mismo trimestre (mismo ID)
     * =========================================================
     */
    public function actualizar(
    Encabezado $encabezado,
    int $numeroTrimestre,
    array $requestData,
    array $files,
    array $datosCalculados
): void {

    $this->em->beginTransaction();

    try {

        // =================================================
        // 1️⃣ Buscar trimestre existente
        // =================================================
        $trimestre = $this->trimestreRepo->findOneBy([
            'encabezado' => $encabezado,
            'trimestre'  => $numeroTrimestre
        ]);

        if (!$trimestre) {
            throw new \DomainException('No existe el reporte para este trimestre.');
        }

        // =================================================
        // 2️⃣ Preparar ruta base de uploads
        // =================================================
        $rutaBase = $this->projectDir . '/public/uploads/pta/' . $trimestre->getId() . '/';

        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        // =================================================
        // 3️⃣ LIMPIAR IMÁGENES FÍSICAS ELIMINADAS (ANTES de borrar BD)
        // =================================================
        $this->limpiarImagenesEliminadas($trimestre, $requestData, $rutaBase);

        // =================================================
        // 4️⃣ Eliminar indicadores hijos (cascade elimina acciones, partidas, evidencias)
        // =================================================
        foreach ($trimestre->getReportePtaIndicadors() as $indicador) {
            $this->em->remove($indicador);
        }

        $this->em->flush();

        // =================================================
        // 5️⃣ Volver a crear hijos con datos nuevos
        // =================================================
        $this->crearIndicadores(
            $trimestre,
            $encabezado,
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


    private function limpiarImagenesEliminadas(
    ReportePtaTrimestre $trimestre,
    array $requestData,
    string $rutaBase
): void {

    $imagenesActuales = [];

    // 1️⃣ Obtener todas las imágenes actuales en BD
    foreach ($trimestre->getReportePtaIndicadors() as $indicador) {
        foreach ($indicador->getReportePtaEvidencias() as $evidencia) {
            foreach ($evidencia->getImagenes() ?? [] as $nombre) {
                $imagenesActuales[] = $nombre;
            }
        }
    }

    // 2️⃣ Obtener imágenes que el usuario decidió conservar
    $imagenesConservadas = [];

    foreach ($requestData['evidencias'] ?? [] as $indicadorData) {
        foreach ($indicadorData as $bloque) {
            foreach ($bloque['imagenes_existentes'] ?? [] as $nombre) {
                $imagenesConservadas[] = $nombre;
            }
        }
    }

    // 3️⃣ Calcular cuáles eliminar
    $imagenesAEliminar = array_diff($imagenesActuales, $imagenesConservadas);

    // 4️⃣ Eliminar físicamente
    foreach ($imagenesAEliminar as $nombre) {
        $ruta = $rutaBase . $nombre;
        if (file_exists($ruta)) {
            unlink($ruta);
        }
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
        Encabezado $encabezado,
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

            // Indicador Básico
            $indicadorBasico = $this->indicadorBasicoRepo
                ->find($requestIndicador['indicador_basico'] ?? null);

            if (!$indicadorBasico) {
                throw new \DomainException("Indicador básico inválido en índice {$indice}");
            }

            $indicador->setIndicadorBasico($indicadorBasico);

            // Indicador PTA (Entidad real)
            $indicadorPtaId = $datosIndicador['id'] ?? null;
            $indicadorPta = $indicadorPtaId
                ? $this->indicadorRepo->find($indicadorPtaId)
                : null;

            if (!$indicadorPta) {
                throw new \DomainException("Indicador PTA inválido (id={$indicadorPtaId})");
            }

            $indicador->setIndicadorPta($indicadorPta);

            // Responsable Puesto
            $responsable = $encabezado->getResponsable();
            $puesto = $responsable ? $responsable->getPuesto() : null;

            if (!$puesto) {
                throw new \DomainException("No se pudo obtener el Puesto responsable.");
            }

            $indicador->setResponsablePuesto($puesto);

            // Campos simples
            $indicador->setUnidadMedida($requestIndicador['unidad_medida'] ?? '');
            $indicador->setMeta($datosIndicador['meta'] ?? '0');
            $indicador->setResultado($datosIndicador['resultado'] ?? '0');
            $indicador->setPorcentajeAvance((string)($datosIndicador['porcentaje'] ?? '0'));
            $indicador->setFormulaDescripcion($datosIndicador['formula_descripcion'] ?? '');
            $indicador->setMedioVerificacion($requestIndicador['medio_verificacion'] ?? '');

            // Formula opcional
            $formulaOpcional = trim($requestIndicador['formula_opcional'] ?? '');
            if ($formulaOpcional !== '') {
                $indicador->setFormula($formulaOpcional);
            }

            // Meta cumplida
            $porcentaje = (float)($datosIndicador['porcentaje'] ?? 0);
            if ($porcentaje >= 100) {
                $indicador->setMetaCumplida('Sí');
            } else {
                $justificacion = trim($requestIndicador['justificacion_meta'] ?? '');
                $indicador->setMetaCumplida(
                    $justificacion !== '' ? $justificacion : 'No'
                );
            }

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
            $accion->setAccion($accionData['descripcion'] ?? '');

            $gastoAccion = $requestData['gastos'][$indice][$accionIndex] ?? null;
            if (!$gastoAccion) {
                throw new \DomainException("Faltan datos de gastos para indicador {$indice}, acción {$accionIndex}");
            }

            $procesoE = $this->procesoEstrategicoRepo
                ->find($gastoAccion['proceso_estrategico'] ?? null);

            $procesoC = $this->procesoClaveRepo
                ->find($gastoAccion['proceso_clave'] ?? null);

            if (!$procesoE || !$procesoC) {
                throw new \DomainException("Proceso estratégico o clave inválido.");
            }

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

            $entidadPartida = $this->partidaRepo
                ->find($partidaData['partida_id'] ?? null);

            if (!$entidadPartida) continue;

            $partida = new ReportePtaAccionPartida();
            $partida->setReporteAccion($accion);
            $partida->setPartidaPresupuestal($entidadPartida);
            $partida->setCantidad($partidaData['monto'] ?? 0);

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

            // ======================================================
// 1️⃣ Imágenes existentes (EDIT)
// ======================================================
$imagenesGuardadas = $bloqueData['imagenes_existentes'] ?? [];

if (!is_array($imagenesGuardadas)) {
    $imagenesGuardadas = [];
}

// Seguridad básica (evitar rutas raras)
$imagenesGuardadas = array_values(array_filter(array_map(function ($n) {
    return basename((string)$n);
}, $imagenesGuardadas)));


// ======================================================
// 2️⃣ Imágenes nuevas subidas
// ======================================================
if (isset($bloquesFiles[$bloqueIndex]['imagenes'])) {

    foreach ($bloquesFiles[$bloqueIndex]['imagenes'] as $file) {

        if (!$file) continue;

        $extension = $file->guessExtension() ?: 'jpg';
        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;

        $file->move($rutaBase, $nombre);
        $imagenesGuardadas[] = $nombre;
    }
}


// ======================================================
// 3️⃣ Límite máximo 4 imágenes
// ======================================================
if (count($imagenesGuardadas) > 4) {
    throw new \DomainException("Máximo 4 imágenes por bloque.");
}

            $evidencia = new ReportePtaEvidencias();
            $evidencia->setReportePtaIndicador($indicador);
            $evidencia->setDescripcion($bloqueData['descripcion'] ?? '');
            $evidencia->setImagenes($imagenesGuardadas);

            $this->em->persist($evidencia);
        }
    }
}