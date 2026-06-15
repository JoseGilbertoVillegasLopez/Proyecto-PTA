<?php

namespace App\Entity;

use App\Repository\ReporteIndicadoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReporteIndicadoresRepository::class)]
#[ORM\Table(name: 'reporte_indicadores')]
class ReporteIndicadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creadoPor = null;

    #[ORM\Column(length: 160)]
    private ?string $titulo = null;

    #[ORM\Column(length: 40, options: ['default' => 'Borrador'])]
    private string $estado = 'Borrador';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $creadoFecha = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $actualizadoFecha = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreadoPor(): ?User
    {
        return $this->creadoPor;
    }

    public function setCreadoPor(?User $creadoPor): static
    {
        $this->creadoPor = $creadoPor;

        return $this;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

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

    public function getActualizadoFecha(): ?\DateTimeImmutable
    {
        return $this->actualizadoFecha;
    }

    public function setActualizadoFecha(?\DateTimeImmutable $actualizadoFecha): static
    {
        $this->actualizadoFecha = $actualizadoFecha;

        return $this;
    }
}
