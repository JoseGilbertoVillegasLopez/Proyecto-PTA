<?php

namespace App\Entity;

use App\Entity\Personal;
use App\Repository\NombramientoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NombramientoRepository::class)]
class Nombramiento
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $archivo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombre_original = null;

    #[ORM\Column(length: 100)]
    private ?string $tipo = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $activo = true;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $fecha_subida = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $fecha_desactivacion = null;

    #[ORM\ManyToOne(inversedBy: 'nombramientos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArchivo(): ?string
    {
        return $this->archivo;
    }

    public function setArchivo(string $archivo): static
    {
        $this->archivo = $archivo;

        return $this;
    }

    public function getNombreOriginal(): ?string
    {
        return $this->nombre_original;
    }

    public function setNombreOriginal(?string $nombre_original): static
    {
        $this->nombre_original = $nombre_original;

        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    public function getFechaSubida(): ?\DateTimeInterface
    {
        return $this->fecha_subida;
    }

    public function setFechaSubida(\DateTimeInterface $fecha_subida): static
    {
        $this->fecha_subida = $fecha_subida;

        return $this;
    }

    public function getFechaDesactivacion(): ?\DateTimeInterface
    {
        return $this->fecha_desactivacion;
    }

    public function setFechaDesactivacion(?\DateTimeInterface $fecha_desactivacion): static
    {
        $this->fecha_desactivacion = $fecha_desactivacion;

        return $this;
    }

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(?Personal $personal): static
    {
        $this->personal = $personal;

        return $this;
    }
}
