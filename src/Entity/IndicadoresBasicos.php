<?php

namespace App\Entity;

use App\Repository\IndicadoresBasicosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IndicadoresBasicosRepository::class)]
class IndicadoresBasicos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreIndicador = null;

    #[ORM\Column(length: 255)]
    private ?string $formula = null;

    #[ORM\Column(length: 255)]
    private ?string $observaciones = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $activo = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GrupoIndicadoresBasicos $grupo = null;

    #[ORM\ManyToMany(targetEntity: Departamento::class, inversedBy: 'indicadoresBasicos')]
    #[ORM\JoinTable(
        name: 'departamento_indicadores_basicos',
        joinColumns: [new ORM\JoinColumn(name: 'indicadores_basicos_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'departamento_id', referencedColumnName: 'id')]
    )]
    private Collection $departamentos;

    public function __construct()
    {
        $this->departamentos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombreIndicador(): ?string
    {
        return $this->nombreIndicador;
    }

    public function setNombreIndicador(string $nombreIndicador): static
    {
        $this->nombreIndicador = $nombreIndicador;

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

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(string $observaciones): static
    {
        $this->observaciones = $observaciones;

        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    public function getGrupo(): ?GrupoIndicadoresBasicos
    {
        return $this->grupo;
    }

    public function setGrupo(?GrupoIndicadoresBasicos $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }

    public function getDepartamentos(): Collection
    {
        return $this->departamentos;
    }

    public function addDepartamento(Departamento $departamento): static
    {
        if (!$this->departamentos->contains($departamento)) {
            $this->departamentos->add($departamento);
        }

        return $this;
    }

    public function removeDepartamento(Departamento $departamento): static
    {
        $this->departamentos->removeElement($departamento);

        return $this;
    }

}