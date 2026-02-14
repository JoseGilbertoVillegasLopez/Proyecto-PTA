<?php

namespace App\Entity;

use App\Repository\ReportePtaAccionPartidaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaAccionPartidaRepository::class)]
class ReportePtaAccionPartida
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaAccionPartidas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReportePtaAccion $reporteAccion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PartidasPresupuestales $partidaPresupuestal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $cantidad = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporteAccion(): ?ReportePtaAccion
    {
        return $this->reporteAccion;
    }

    public function setReporteAccion(?ReportePtaAccion $reporteAccion): static
    {
        $this->reporteAccion = $reporteAccion;

        return $this;
    }

    public function getPartidaPresupuestal(): ?PartidasPresupuestales
    {
        return $this->partidaPresupuestal;
    }

    public function setPartidaPresupuestal(?PartidasPresupuestales $partidaPresupuestal): static
    {
        $this->partidaPresupuestal = $partidaPresupuestal;

        return $this;
    }

    public function getCantidad(): ?string
    {
        return $this->cantidad;
    }

    public function setCantidad(string $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }
}
