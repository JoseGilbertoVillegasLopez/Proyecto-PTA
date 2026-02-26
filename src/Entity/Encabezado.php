<?php

namespace App\Entity;

use App\Repository\EncabezadoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EncabezadoRepository::class)]
class Encabezado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $objetivo = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $fechaCreacion = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $fechaConcluido = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'pta')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $responsable = null;

    #[Assert\Count(min: 1, minMessage: 'Debe agregar al menos un indicador.')]
    #[ORM\OneToMany(mappedBy: 'encabezado', targetEntity: Indicadores::class, cascade: ['persist', 'remove'])]
    private Collection $indicadores;

    #[ORM\OneToMany(mappedBy: 'encabezado', targetEntity: Acciones::class, cascade: ['persist', 'remove'])]
    private Collection $acciones;

    #[ORM\OneToOne(mappedBy: 'encabezado', cascade: ['persist', 'remove'])]
    private ?Responsables $responsables = null;

    #[ORM\Column]
    private ?int $anioEjecucion = null;

    #[ORM\OneToMany(mappedBy: 'encabezado', targetEntity: ReportePtaTrimestre::class, cascade: ['persist', 'remove'])]
    private Collection $reportePtaTrimestres;

    public function __construct()
    {
        $this->indicadores = new ArrayCollection();
        $this->acciones = new ArrayCollection();
        $this->reportePtaTrimestres = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getObjetivo(): ?string { return $this->objetivo; }
    public function setObjetivo(string $objetivo): static { $this->objetivo = $objetivo; return $this; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getFechaCreacion(): ?\DateTime { return $this->fechaCreacion; }
    public function setFechaCreacion(\DateTime $fechaCreacion): static { $this->fechaCreacion = $fechaCreacion; return $this; }

    public function getFechaConcluido(): ?\DateTime { return $this->fechaConcluido; }
    public function setFechaConcluido(?\DateTime $fechaConcluido): static { $this->fechaConcluido = $fechaConcluido; return $this; }

    public function isStatus(): ?bool { return $this->status; }
    public function setStatus(bool $status): static { $this->status = $status; return $this; }

    public function getResponsable(): ?Personal { return $this->responsable; }
    public function setResponsable(?Personal $responsable): static { $this->responsable = $responsable; return $this; }

    public function getIndicadores(): Collection { return $this->indicadores; }
    public function getAcciones(): Collection { return $this->acciones; }

    /**
     * ===============================
     * RELACIÓN CON RESPONSABLES
     * ===============================
     */
    public function getResponsables(): ?Responsables
    {
        return $this->responsables;
    }

    public function setResponsables(Responsables $responsables): static
    {
        if ($responsables->getEncabezado() !== $this) {
            $responsables->setEncabezado($this);
        }

        $this->responsables = $responsables;

        return $this;
    }

    public function getAnioEjecucion(): ?int { return $this->anioEjecucion; }
    public function setAnioEjecucion(int $anioEjecucion): static { $this->anioEjecucion = $anioEjecucion; return $this; }

    public function getReportePtaTrimestres(): Collection { return $this->reportePtaTrimestres; }

    public function addReportePtaTrimestre(ReportePtaTrimestre $trimestre): static
    {
        if (!$this->reportePtaTrimestres->contains($trimestre)) {
            $this->reportePtaTrimestres->add($trimestre);
            $trimestre->setEncabezado($this);
        }
        return $this;
    }

    public function removeReportePtaTrimestre(ReportePtaTrimestre $trimestre): static
    {
        if ($this->reportePtaTrimestres->removeElement($trimestre)) {
            if ($trimestre->getEncabezado() === $this) {
                $trimestre->setEncabezado(null);
            }
        }
        return $this;
    }
}
