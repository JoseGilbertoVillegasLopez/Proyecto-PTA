<?php

namespace App\Service\SolicitudGastos;

use App\Entity\SolicitudGastos;
use App\Entity\SolicitudGastosComprobante;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SubirComprobanteSolicitudGastosService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {}

    public function subir(SolicitudGastos $solicitud, UploadedFile $archivo): void
    {
        if ($solicitud->getEstado() !== 'aceptada') {
            throw new \InvalidArgumentException('Solo se puede subir el comprobante de una solicitud aceptada.');
        }

        if (!$archivo->isValid()) {
            throw new \InvalidArgumentException('El archivo de comprobante no es válido.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($archivo->getPathname()) ?: $archivo->getMimeType();

        if (!str_starts_with($mimeType ?? '', 'image/') && $mimeType !== 'application/pdf') {
            throw new \InvalidArgumentException('El comprobante debe ser una imagen o un PDF.');
        }

        $uploadsDir = $this->projectDir . '/public/uploads/solicitud_gastos/' . $solicitud->getId() . '/comprobante';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->guessExtension() ?: 'bin');
        $nombreGuardado = bin2hex(random_bytes(16)) . '.' . $extension;

        $archivo->move($uploadsDir, $nombreGuardado);

        $comprobante = $solicitud->getComprobante() ?? new SolicitudGastosComprobante();
        $comprobante
            ->setArchivoNombreOriginal($archivo->getClientOriginalName())
            ->setArchivoNombreGuardado($nombreGuardado)
            ->setRuta('/uploads/solicitud_gastos/' . $solicitud->getId() . '/comprobante/' . $nombreGuardado)
            ->setMimeType($mimeType)
            ->setExtension($extension)
            ->setTamano(filesize($uploadsDir . '/' . $nombreGuardado) ?: 0)
            ->setCreadoFecha(new \DateTimeImmutable('today'));

        $solicitud->setComprobante($comprobante);
        $solicitud->setEstado('resuelto');

        $this->em->persist($comprobante);
        $this->em->flush();
    }
}
