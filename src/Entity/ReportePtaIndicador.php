<?php

namespace App\Entity;

use App\Repository\ReportePtaIndicadorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaIndicadorRepository::class)]
#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'unique_reporte_trimestre_indicador',
            columns: ['encabezado_id', 'indicador_pta_id', 'anio', 'trimestre']
        )
    ]
)]
class ReportePtaIndicador
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaIndicadors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IndicadoresBasicos $indicadorBasico = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicadores $indicadorPta = null;

    #[ORM\Column]
    private ?int $anio = null;

    #[ORM\Column]
    private ?int $trimestre = null;

    #[ORM\Column(length: 255)]
    private ?string $unidadMedida = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $meta = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $resultado = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $porcentajeAvance = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $formula = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $medioVerificacion = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $metaCumplida = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Puesto $responsablePuesto = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $creadoFecha = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $entregadoFecha = null;


    #[ORM\Column (options: ['default' => false])]
    private ?bool $estado = false;

    /**
     * @var Collection<int, ReportePtaAccion>
     */
    #[ORM\OneToMany(targetEntity: ReportePtaAccion::class, mappedBy: 'reporteIndicador')]
    private Collection $reportePtaAccions;

    public function __construct()
    {
        $this->reportePtaAccions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEncabezado(): ?Encabezado
    {
        return $this->encabezado;
    }

    public function setEncabezado(?Encabezado $encabezado): static
    {
        $this->encabezado = $encabezado;

        return $this;
    }

    public function getIndicadorBasico(): ?IndicadoresBasicos
    {
        return $this->indicadorBasico;
    }

    public function setIndicadorBasico(?IndicadoresBasicos $indicadorBasico): static
    {
        $this->indicadorBasico = $indicadorBasico;

        return $this;
    }

    public function getIndicadorPta(): ?Indicadores
    {
        return $this->indicadorPta;
    }

    public function setIndicadorPta(?Indicadores $indicadorPta): static
    {
        $this->indicadorPta = $indicadorPta;

        return $this;
    }

    public function getAnio(): ?int
    {
        return $this->anio;
    }

    public function setAnio(int $anio): static
    {
        $this->anio = $anio;

        return $this;
    }

    public function getTrimestre(): ?int
    {
        return $this->trimestre;
    }

    public function setTrimestre(int $trimestre): static
    {
        $this->trimestre = $trimestre;

        return $this;
    }

    public function getUnidadMedida(): ?string
    {
        return $this->unidadMedida;
    }

    public function setUnidadMedida(string $unidadMedida): static
    {
        $this->unidadMedida = $unidadMedida;

        return $this;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setMeta(string $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function getResultado(): ?string
    {
        return $this->resultado;
    }

    public function setResultado(string $resultado): static
    {
        $this->resultado = $resultado;

        return $this;
    }

    public function getPorcentajeAvance(): ?string
    {
        return $this->porcentajeAvance;
    }

    public function setPorcentajeAvance(string $porcentajeAvance): static
    {
        $this->porcentajeAvance = $porcentajeAvance;

        return $this;
    }

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function setFormula(string $formula): static
    {
        $this->formula = $formula;

        return $this;
    }

    public function getMedioVerificacion(): ?string
    {
        return $this->medioVerificacion;
    }

    public function setMedioVerificacion(string $medioVerificacion): static
    {
        $this->medioVerificacion = $medioVerificacion;

        return $this;
    }

    public function getMetaCumplida(): ?string
    {
        return $this->metaCumplida;
    }

    public function setMetaCumplida(string $metaCumplida): static
    {
        $this->metaCumplida = $metaCumplida;

        return $this;
    }

    public function getResponsablePuesto(): ?Puesto
    {
        return $this->responsablePuesto;
    }

    public function setResponsablePuesto(?Puesto $responsablePuesto): static
    {
        $this->responsablePuesto = $responsablePuesto;

        return $this;
    }

    public function getCreadoFecha(): ?\DateTime
    {
        return $this->creadoFecha;
    }

    public function setCreadoFecha(\DateTime $creadoFecha): static
    {
        $this->creadoFecha = $creadoFecha;

        return $this;
    }

    public function getEntregadoFecha(): ?\DateTime
    {
        return $this->entregadoFecha;
    }

    public function setEntregadoFecha(\DateTime $entregadoFecha): static
    {
        $this->entregadoFecha = $entregadoFecha;

        return $this;
    }

    public function isEstado(): ?bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection<int, ReportePtaAccion>
     */
    public function getReportePtaAccions(): Collection
    {
        return $this->reportePtaAccions;
    }

    public function addReportePtaAccion(ReportePtaAccion $reportePtaAccion): static
    {
        if (!$this->reportePtaAccions->contains($reportePtaAccion)) {
            $this->reportePtaAccions->add($reportePtaAccion);
            $reportePtaAccion->setReporteIndicador($this);
        }

        return $this;
    }

    public function removeReportePtaAccion(ReportePtaAccion $reportePtaAccion): static
    {
        if ($this->reportePtaAccions->removeElement($reportePtaAccion)) {
            // set the owning side to null (unless already changed)
            if ($reportePtaAccion->getReporteIndicador() === $this) {
                $reportePtaAccion->setReporteIndicador(null);
            }
        }

        return $this;
    }
}
