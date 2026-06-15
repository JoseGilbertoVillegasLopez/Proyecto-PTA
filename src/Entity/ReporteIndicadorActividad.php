<?php

namespace App\Entity;

use App\Repository\ReporteIndicadorActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReporteIndicadorActividadRepository::class)]
#[ORM\Table(name: 'reporte_indicador_actividad')]
class ReporteIndicadorActividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'reporte_trimestre_id', referencedColumnName: 'id', nullable: false)]
    private ?ReporteIndicadorTrimestre $reporteTrimestre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'indicador_basico_id', referencedColumnName: 'id', nullable: false)]
    private ?IndicadoresBasicos $indicadorBasico = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $accion = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descripcion = null;

    /**
     * @var Collection<int, ReporteIndicadorEvidencia>
     */
    #[ORM\OneToMany(mappedBy: 'actividad', targetEntity: ReporteIndicadorEvidencia::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['orden' => 'ASC'])]
    private Collection $evidencias;

    public function __construct()
    {
        $this->evidencias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporteTrimestre(): ?ReporteIndicadorTrimestre
    {
        return $this->reporteTrimestre;
    }

    public function setReporteTrimestre(?ReporteIndicadorTrimestre $reporteTrimestre): static
    {
        $this->reporteTrimestre = $reporteTrimestre;

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

    public function getAccion(): ?string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): static
    {
        $this->accion = $accion;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * @return Collection<int, ReporteIndicadorEvidencia>
     */
    public function getEvidencias(): Collection
    {
        return $this->evidencias;
    }

    public function addEvidencia(ReporteIndicadorEvidencia $evidencia): static
    {
        if (!$this->evidencias->contains($evidencia)) {
            $this->evidencias->add($evidencia);
            $evidencia->setActividad($this);
        }

        return $this;
    }

    public function removeEvidencia(ReporteIndicadorEvidencia $evidencia): static
    {
        $this->evidencias->removeElement($evidencia);

        return $this;
    }
}
