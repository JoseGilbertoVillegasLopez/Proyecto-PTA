<?php

namespace App\Entity;

use App\Repository\EncabezadoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(nullable: true)]
    private ?bool $tendencia = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'pta', targetEntity: Personal::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $responsable = null;

    /**
     * @var Collection<int, Indicadores>
     */
    #[ORM\OneToMany(targetEntity: Indicadores::class, mappedBy: 'encabezado')]
    private Collection $indicadores;

    public function __construct()
    {
        $this->indicadores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjetivo(): ?string
    {
        return $this->objetivo;
    }

    public function setObjetivo(string $objetivo): static
    {
        $this->objetivo = $objetivo;

        return $this;
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

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTime $fechaCreacion): static
    {
        $this->fechaCreacion = $fechaCreacion;

        return $this;
    }

    public function getFechaConcluido(): ?\DateTime
    {
        return $this->fechaConcluido;
    }

    public function setFechaConcluido(?\DateTime $fechaConcluido): static
    {
        $this->fechaConcluido = $fechaConcluido;

        return $this;
    }

    public function isTendencia(): ?bool
    {
        return $this->tendencia;
    }

    public function setTendencia(?bool $tendencia): static
    {
        $this->tendencia = $tendencia;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getResponsable(): ?Personal
    {
        return $this->responsable;
    }

    public function setResponsable(?Personal $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * @return Collection<int, Indicadores>
     */
    public function getIndicadores(): Collection
    {
        return $this->indicadores;
    }

    public function addIndicadore(Indicadores $indicadore): static
    {
        if (!$this->indicadores->contains($indicadore)) {
            $this->indicadores->add($indicadore);
            $indicadore->setEncabezado($this);
        }

        return $this;
    }

    public function removeIndicadore(Indicadores $indicadore): static
    {
        if ($this->indicadores->removeElement($indicadore)) {
            // set the owning side to null (unless already changed)
            if ($indicadore->getEncabezado() === $this) {
                $indicadore->setEncabezado(null);
            }
        }

        return $this;
    }
}
