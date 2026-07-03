<?php

namespace App\Service\SolicitudGastos;

use App\Entity\Personal;
use App\Entity\SolicitudGastos;
use App\Entity\SolicitudGastosEvidencia;
use App\Entity\SolicitudGastosPartida;
use App\Repository\PartidasPresupuestalesRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\SolicitudGastosBancoRepository;
use App\Repository\TipoSolicitudRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GuardarSolicitudGastosService
{
    private const MIN_EVIDENCIAS = 1;
    private const MAX_EVIDENCIAS = 7;

    public function __construct(
        private readonly TipoSolicitudRepository $tipoSolicitudRepository,
        private readonly ProcesoEstrategicoRepository $procesoEstrategicoRepository,
        private readonly ProcesoClaveRepository $procesoClaveRepository,
        private readonly PartidasPresupuestalesRepository $partidasRepository,
        private readonly SolicitudGastosBancoRepository $bancoRepository,
        private readonly EntityManagerInterface $em,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {}

    /**
     * @param UploadedFile[] $archivosEvidencia
     */
    public function guardar(array $data, Personal $solicitante, array $archivosEvidencia = []): SolicitudGastos
    {
        $solicitud = new SolicitudGastos();
        $solicitud->setSolicitante($solicitante);

        $tipoId = (int) ($data['tipo_solicitud'] ?? 0);
        $tipo = $this->tipoSolicitudRepository->find($tipoId);
        if ($tipo) {
            $solicitud->setTipoSolicitud($tipo);
        }

        $fechaNecesita = $data['fecha_necesita'] ?? null;
        if ($fechaNecesita) {
            $solicitud->setFechaNecesita(new \DateTime($fechaNecesita));
        }

        $transferencia = trim($data['transferencia_en_beneficio_de'] ?? '');
        $solicitud->setTransferenciaEnBeneficioDe($transferencia !== '' ? $transferencia : null);

        $ctaClave = trim($data['cta_clave_beneficiario'] ?? '');
        $solicitud->setCtaClaveBeneficiario($ctaClave !== '' ? $ctaClave : null);
        $solicitud->setPorConceptoDe(trim($data['por_concepto_de'] ?? ''));

        $peId = (int) ($data['proceso_estrategico'] ?? 0);
        if ($peId) {
            $pe = $this->procesoEstrategicoRepository->find($peId);
            if ($pe) {
                $solicitud->setProcesoEstrategico($pe);
            }
        }

        $pcId = (int) ($data['proceso_clave'] ?? 0);
        if ($pcId) {
            $pc = $this->procesoClaveRepository->find($pcId);
            if ($pc) {
                $solicitud->setProcesoClave($pc);
            }
        }

        $bancoId = (int) ($data['banco'] ?? 0);
        if ($bancoId) {
            $banco = $this->bancoRepository->find($bancoId);
            if ($banco) {
                $solicitud->setBanco($banco);
            }
        }

        $total = '0.00';
        $partidas = $data['partidas'] ?? [];

        foreach ($partidas as $item) {
            $partidaId = (int) ($item['partida_id'] ?? 0);
            $monto = $this->normalizarDecimal($item['monto'] ?? null);

            if (!$partidaId || $monto === null) {
                continue;
            }

            $partida = $this->partidasRepository->find($partidaId);
            if (!$partida) {
                continue;
            }

            $linea = new SolicitudGastosPartida();
            $linea->setPartida($partida);
            $linea->setMonto($monto);

            $solicitud->addPartida($linea);
            $total = number_format((float) $total + (float) $monto, 2, '.', '');
        }

        $solicitud->setCantidadTotal($total);

        $documentos = $data['documentos_verificacion'] ?? [];
        $documentos = is_array($documentos)
            ? array_values(array_intersect($documentos, array_keys(SolicitudGastos::DOCUMENTOS_VERIFICACION)))
            : [];

        if (empty($documentos)) {
            throw new \InvalidArgumentException('Selecciona al menos un documento de verificación.');
        }

        $solicitud->setDocumentosVerificacion($documentos);

        $this->em->persist($solicitud);
        $this->em->flush();

        $this->guardarEvidencias($solicitud, $archivosEvidencia);
        $this->em->flush();

        return $solicitud;
    }

    /**
     * @param UploadedFile[] $archivos
     */
    private function guardarEvidencias(SolicitudGastos $solicitud, array $archivos): void
    {
        $archivos = array_values(array_filter(
            $archivos,
            static fn ($archivo) => $archivo instanceof UploadedFile && $archivo->isValid()
        ));

        if (count($archivos) < self::MIN_EVIDENCIAS || count($archivos) > self::MAX_EVIDENCIAS) {
            throw new \InvalidArgumentException(sprintf(
                'Debes adjuntar entre %d y %d archivos de evidencia.',
                self::MIN_EVIDENCIAS,
                self::MAX_EVIDENCIAS
            ));
        }

        $uploadsDir = $this->projectDir . '/public/uploads/solicitud_gastos/' . $solicitud->getId();

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $orden = 1;

        foreach ($archivos as $archivo) {
            $mimeType = $finfo->file($archivo->getPathname()) ?: $archivo->getMimeType();

            if (!str_starts_with($mimeType ?? '', 'image/') && $mimeType !== 'application/pdf') {
                throw new \InvalidArgumentException('Solo se permiten imágenes o PDF como evidencia.');
            }

            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->guessExtension() ?: 'bin');
            $nombreGuardado = bin2hex(random_bytes(16)) . '.' . $extension;

            $archivo->move($uploadsDir, $nombreGuardado);

            $evidencia = (new SolicitudGastosEvidencia())
                ->setArchivoNombreOriginal($archivo->getClientOriginalName())
                ->setArchivoNombreGuardado($nombreGuardado)
                ->setRuta('/uploads/solicitud_gastos/' . $solicitud->getId() . '/' . $nombreGuardado)
                ->setMimeType($mimeType)
                ->setExtension($extension)
                ->setTamano(filesize($uploadsDir . '/' . $nombreGuardado) ?: 0)
                ->setOrden($orden)
                ->setCreadoFecha(new \DateTimeImmutable('today'));

            $solicitud->addEvidencia($evidencia);
            $this->em->persist($evidencia);

            $orden++;
        }
    }

    private function normalizarDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);

        if (!is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
