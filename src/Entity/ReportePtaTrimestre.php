<?php

namespace App\Entity;

use App\Repository\ReportePtaTrimestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaTrimestreRepository::class)]
#[ORM\Table(name: "reporte_pta_trimestre")]
#[ORM\UniqueConstraint(
    name: "uniq_reporte",
    columns: ["encabezado_id", "trimestre"]
)]
class ReportePtaTrimestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaTrimestres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    #[ORM\Column]
    private ?int $anio = null;

    #[ORM\Column]
    private ?int $trimestre = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $estado = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $creadoFecha = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $entregadoFecha = null;

    #[ORM\OneToMany(
    mappedBy: 'reporteTrimestre',
    targetEntity: ReportePtaIndicador::class,
    cascade: ['persist', 'remove'],
    orphanRemoval: true
)]
    private Collection $reportePtaIndicadors;

    public function __construct()
    {
        $this->reportePtaIndicadors = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEncabezado(): ?Encabezado { return $this->encabezado; }
    public function setEncabezado(?Encabezado $encabezado): static { $this->encabezado = $encabezado; return $this; }

    public function getAnio(): ?int { return $this->anio; }
    public function setAnio(int $anio): static { $this->anio = $anio; return $this; }

    public function getTrimestre(): ?int { return $this->trimestre; }
    public function setTrimestre(int $trimestre): static { $this->trimestre = $trimestre; return $this; }

    public function isEstado(): bool { return $this->estado; }
    public function setEstado(bool $estado): static { $this->estado = $estado; return $this; }

    public function getCreadoFecha(): ?\DateTime { return $this->creadoFecha; }
    public function setCreadoFecha(\DateTime $fecha): static { $this->creadoFecha = $fecha; return $this; }

    public function getEntregadoFecha(): ?\DateTimeInterface
{
    return $this->entregadoFecha;
}

public function setEntregadoFecha(?\DateTimeInterface $entregadoFecha): self
{
    $this->entregadoFecha = $entregadoFecha;
    return $this;
}

    public function getReportePtaIndicadors(): Collection { return $this->reportePtaIndicadors; }

    public function addReportePtaIndicador(ReportePtaIndicador $indicador): static
{
    if (!$this->reportePtaIndicadors->contains($indicador)) {
        $this->reportePtaIndicadors->add($indicador);
        $indicador->setReporteTrimestre($this);
    }

    return $this;
}

public function removeReportePtaIndicador(ReportePtaIndicador $indicador): static
{
    // orphanRemoval=true => al quitarlo de la colección, Doctrine lo elimina.
    // No hacemos set null porque el JoinColumn del hijo es nullable=false.
    $this->reportePtaIndicadors->removeElement($indicador);

    return $this;
}
}
