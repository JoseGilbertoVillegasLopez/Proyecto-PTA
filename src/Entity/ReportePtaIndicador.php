<?php

namespace App\Entity;

use App\Repository\ReportePtaIndicadorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaIndicadorRepository::class)]
class ReportePtaIndicador
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaIndicadors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReportePtaTrimestre $reporteTrimestre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IndicadoresBasicos $indicadorBasico = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicadores $indicadorPta = null;

    #[ORM\Column(length: 255)]
    private ?string $unidadMedida = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $meta = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $resultado = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $porcentajeAvance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $formula = null;


    #[ORM\Column(type: Types::TEXT)]
    private ?string $medioVerificacion = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $metaCumplida = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Puesto $responsablePuesto = null;

    #[ORM\OneToMany(mappedBy: 'reporteIndicador', targetEntity: ReportePtaAccion::class, cascade: ['persist', 'remove'])]
    private Collection $reportePtaAccions;

    /**
     * @var Collection<int, ReportePtaEvidencias>
     */
    #[ORM\OneToMany(targetEntity: ReportePtaEvidencias::class, mappedBy: 'reportePtaIndicador')]
    private Collection $reportePtaEvidencias;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $formulaDescripcion = null;

    public function __construct()
    {
        $this->reportePtaAccions = new ArrayCollection();
        $this->reportePtaEvidencias = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getReporteTrimestre(): ?ReportePtaTrimestre { return $this->reporteTrimestre; }
    public function setReporteTrimestre(?ReportePtaTrimestre $trimestre): static { $this->reporteTrimestre = $trimestre; return $this; }

    public function getIndicadorBasico(): ?IndicadoresBasicos { return $this->indicadorBasico; }
    public function setIndicadorBasico(?IndicadoresBasicos $indicadorBasico): static { $this->indicadorBasico = $indicadorBasico; return $this; }

    public function getIndicadorPta(): ?Indicadores { return $this->indicadorPta; }
    public function setIndicadorPta(?Indicadores $indicadorPta): static { $this->indicadorPta = $indicadorPta; return $this; }

    public function getUnidadMedida(): ?string { return $this->unidadMedida; }
    public function setUnidadMedida(string $unidadMedida): static { $this->unidadMedida = $unidadMedida; return $this; }

    public function getMeta(): ?string { return $this->meta; }
    public function setMeta(string $meta): static { $this->meta = $meta; return $this; }

    public function getResultado(): ?string { return $this->resultado; }
    public function setResultado(string $resultado): static { $this->resultado = $resultado; return $this; }

    public function getPorcentajeAvance(): ?string { return $this->porcentajeAvance; }
    public function setPorcentajeAvance(string $porcentajeAvance): static { $this->porcentajeAvance = $porcentajeAvance; return $this; }

    public function getFormula(): ?string { return $this->formula; }
    public function setFormula(string $formula): static { $this->formula = $formula; return $this; }

    public function getMedioVerificacion(): ?string { return $this->medioVerificacion; }
    public function setMedioVerificacion(string $medioVerificacion): static { $this->medioVerificacion = $medioVerificacion; return $this; }

    public function getMetaCumplida(): ?string { return $this->metaCumplida; }
    public function setMetaCumplida(string $metaCumplida): static { $this->metaCumplida = $metaCumplida; return $this; }

    public function getResponsablePuesto(): ?Puesto { return $this->responsablePuesto; }
    public function setResponsablePuesto(?Puesto $puesto): static { $this->responsablePuesto = $puesto; return $this; }

    public function getReportePtaAccions(): Collection { return $this->reportePtaAccions; }

    /**
     * @return Collection<int, ReportePtaEvidencias>
     */
    public function getReportePtaEvidencias(): Collection
    {
        return $this->reportePtaEvidencias;
    }

    public function addReportePtaEvidencia(ReportePtaEvidencias $reportePtaEvidencia): static
    {
        if (!$this->reportePtaEvidencias->contains($reportePtaEvidencia)) {
            $this->reportePtaEvidencias->add($reportePtaEvidencia);
            $reportePtaEvidencia->setReportePtaIndicador($this);
        }

        return $this;
    }

    public function removeReportePtaEvidencia(ReportePtaEvidencias $reportePtaEvidencia): static
    {
        if ($this->reportePtaEvidencias->removeElement($reportePtaEvidencia)) {
            // set the owning side to null (unless already changed)
            if ($reportePtaEvidencia->getReportePtaIndicador() === $this) {
                $reportePtaEvidencia->setReportePtaIndicador(null);
            }
        }

        return $this;
    }

    public function getFormulaDescripcion(): ?string
    {
        return $this->formulaDescripcion;
    }

    public function setFormulaDescripcion(string $formulaDescripcion): static
    {
        $this->formulaDescripcion = $formulaDescripcion;

        return $this;
    }
}
