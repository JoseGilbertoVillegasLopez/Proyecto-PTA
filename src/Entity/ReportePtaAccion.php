<?php

namespace App\Entity;

use App\Repository\ReportePtaAccionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaAccionRepository::class)]
class ReportePtaAccion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaAccions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReportePtaIndicador $reporteIndicador = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $accion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProcesoEstrategico $procesoEstrategico = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProcesoClave $procesoClave = null;

    /**
     * @var Collection<int, ReportePtaAccionPartida>
     */
    #[ORM\OneToMany(
    targetEntity: ReportePtaAccionPartida::class,
    mappedBy: 'reporteAccion',
    cascade: ['persist', 'remove'],
    orphanRemoval: true
)]
private Collection $reportePtaAccionPartidas;

    public function __construct()
    {
        $this->reportePtaAccionPartidas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporteIndicador(): ?ReportePtaIndicador
    {
        return $this->reporteIndicador;
    }

    public function setReporteIndicador(?ReportePtaIndicador $reporteIndicador): static
    {
        $this->reporteIndicador = $reporteIndicador;

        return $this;
    }

    public function getAccion(): ?string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): static
    {
        $this->accion = $accion;

        return $this;
    }

    public function getProcesoEstrategico(): ?ProcesoEstrategico
    {
        return $this->procesoEstrategico;
    }

    public function setProcesoEstrategico(?ProcesoEstrategico $procesoEstrategico): static
    {
        $this->procesoEstrategico = $procesoEstrategico;

        return $this;
    }

    public function getProcesoClave(): ?ProcesoClave
    {
        return $this->procesoClave;
    }

    public function setProcesoClave(?ProcesoClave $procesoClave): static
    {
        $this->procesoClave = $procesoClave;

        return $this;
    }

    /**
     * @return Collection<int, ReportePtaAccionPartida>
     */
    public function getReportePtaAccionPartidas(): Collection
    {
        return $this->reportePtaAccionPartidas;
    }

    public function addReportePtaAccionPartida(ReportePtaAccionPartida $reportePtaAccionPartida): static
    {
        if (!$this->reportePtaAccionPartidas->contains($reportePtaAccionPartida)) {
            $this->reportePtaAccionPartidas->add($reportePtaAccionPartida);
            $reportePtaAccionPartida->setReporteAccion($this);
        }

        return $this;
    }

    public function removeReportePtaAccionPartida(ReportePtaAccionPartida $reportePtaAccionPartida): static
{
    // Con orphanRemoval=true NO necesitamos setear null (y nullable=false lo prohíbe).
    $this->reportePtaAccionPartidas->removeElement($reportePtaAccionPartida);

    return $this;
}
}
