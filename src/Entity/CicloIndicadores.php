<?php

namespace App\Entity;

use App\Repository\CicloIndicadoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CicloIndicadoresRepository::class)]
#[ORM\Table(name: 'ciclo_indicadores')]
#[ORM\UniqueConstraint(name: 'UNIQ_CICLO_INDICADORES_NOMBRE', columns: ['nombre'])]
class CicloIndicadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fechaApertura = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fechaCierre = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $activo = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $visible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getFechaApertura(): ?\DateTimeInterface
    {
        return $this->fechaApertura;
    }

    public function setFechaApertura(\DateTimeInterface $fechaApertura): static
    {
        $this->fechaApertura = $fechaApertura;

        return $this;
    }

    public function getFechaCierre(): ?\DateTimeInterface
    {
        return $this->fechaCierre;
    }

    public function setFechaCierre(\DateTimeInterface $fechaCierre): static
    {
        $this->fechaCierre = $fechaCierre;

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }
}
