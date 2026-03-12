<?php

namespace App\Entity;

use App\Repository\AccionesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccionesRepository::class)]
class Acciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'acciones', targetEntity: Encabezado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $accion = null;

    #[ORM\Column]
    private array $periodo = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $valorAlcanzado = null;

    #[ORM\Column]
    private ?int $indicador = null;

    /**
     * @var Collection<int, HistorialAccionesAtrasos>
     */
    #[ORM\OneToMany(targetEntity: HistorialAccionesAtrasos::class, mappedBy: 'accion')]
    private Collection $historialAccionesAtrasos;

    /**
     * @var Collection<int, HistorialAcciones>
     */
    #[ORM\OneToMany(targetEntity: HistorialAcciones::class, mappedBy: 'accion')]
    private Collection $historialAcciones;

    public function __construct()
    {
        $this->historialAccionesAtrasos = new ArrayCollection();
        $this->historialAcciones = new ArrayCollection();
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

    public function getAccion(): ?string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): static
    {
        $this->accion = $accion;

        return $this;
    }

    public function getPeriodo(): array
    {
        return $this->periodo;
    }

    public function setPeriodo(array $periodo): static
    {
        $this->periodo = $periodo;

        return $this;
    }

    public function getValorAlcanzado(): ?array
    {
        return $this->valorAlcanzado;
    }

    public function setValorAlcanzado(array $valorAlcanzado): static
    {
        $this->valorAlcanzado = $valorAlcanzado;

        return $this;
    }

    public function getIndicador(): ?int
    {
        return $this->indicador;
    }

    public function setIndicador(int $indicador): static
    {
        $this->indicador = $indicador;

        return $this;
    }

    /**
     * @return Collection<int, HistorialAccionesAtrasos>
     */
    public function getHistorialAccionesAtrasos(): Collection
    {
        return $this->historialAccionesAtrasos;
    }

    public function addHistorialAccionesAtraso(HistorialAccionesAtrasos $historialAccionesAtraso): static
    {
        if (!$this->historialAccionesAtrasos->contains($historialAccionesAtraso)) {
            $this->historialAccionesAtrasos->add($historialAccionesAtraso);
            $historialAccionesAtraso->setAccion($this);
        }

        return $this;
    }

    public function removeHistorialAccionesAtraso(HistorialAccionesAtrasos $historialAccionesAtraso): static
    {
        if ($this->historialAccionesAtrasos->removeElement($historialAccionesAtraso)) {
            // set the owning side to null (unless already changed)
            if ($historialAccionesAtraso->getAccion() === $this) {
                $historialAccionesAtraso->setAccion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HistorialAcciones>
     */
    public function getHistorialAcciones(): Collection
    {
        return $this->historialAcciones;
    }

    public function addHistorialAccione(HistorialAcciones $historialAccione): static
    {
        if (!$this->historialAcciones->contains($historialAccione)) {
            $this->historialAcciones->add($historialAccione);
            $historialAccione->setAccion($this);
        }

        return $this;
    }

    public function removeHistorialAccione(HistorialAcciones $historialAccione): static
    {
        if ($this->historialAcciones->removeElement($historialAccione)) {
            // set the owning side to null (unless already changed)
            if ($historialAccione->getAccion() === $this) {
                $historialAccione->setAccion(null);
            }
        }

        return $this;
    }
}
