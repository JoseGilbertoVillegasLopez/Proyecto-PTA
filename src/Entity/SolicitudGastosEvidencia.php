<?php

namespace App\Entity;

use App\Repository\SolicitudGastosEvidenciaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudGastosEvidenciaRepository::class)]
#[ORM\Table(name: 'solicitud_gastos_evidencia')]
class SolicitudGastosEvidencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evidencias')]
    #[ORM\JoinColumn(name: 'solicitud_id', referencedColumnName: 'id', nullable: false)]
    private ?SolicitudGastos $solicitud = null;

    #[ORM\Column(length: 255)]
    private ?string $archivoNombreOriginal = null;

    #[ORM\Column(length: 255)]
    private ?string $archivoNombreGuardado = null;

    #[ORM\Column(length: 255)]
    private ?string $ruta = null;

    #[ORM\Column(length: 120)]
    private ?string $mimeType = null;

    #[ORM\Column(length: 20)]
    private ?string $extension = null;

    #[ORM\Column]
    private ?int $tamano = null;

    #[ORM\Column]
    private ?int $orden = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $creadoFecha = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolicitud(): ?SolicitudGastos
    {
        return $this->solicitud;
    }

    public function setSolicitud(?SolicitudGastos $solicitud): static
    {
        $this->solicitud = $solicitud;

        return $this;
    }

    public function getArchivoNombreOriginal(): ?string
    {
        return $this->archivoNombreOriginal;
    }

    public function setArchivoNombreOriginal(string $archivoNombreOriginal): static
    {
        $this->archivoNombreOriginal = $archivoNombreOriginal;

        return $this;
    }

    public function getArchivoNombreGuardado(): ?string
    {
        return $this->archivoNombreGuardado;
    }

    public function setArchivoNombreGuardado(string $archivoNombreGuardado): static
    {
        $this->archivoNombreGuardado = $archivoNombreGuardado;

        return $this;
    }

    public function getRuta(): ?string
    {
        return $this->ruta;
    }

    public function setRuta(string $ruta): static
    {
        $this->ruta = $ruta;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getTamano(): ?int
    {
        return $this->tamano;
    }

    public function setTamano(int $tamano): static
    {
        $this->tamano = $tamano;

        return $this;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;

        return $this;
    }

    public function getCreadoFecha(): ?\DateTimeImmutable
    {
        return $this->creadoFecha;
    }

    public function setCreadoFecha(\DateTimeImmutable $creadoFecha): static
    {
        $this->creadoFecha = $creadoFecha;

        return $this;
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->mimeType ?? '', 'image/');
    }
}
