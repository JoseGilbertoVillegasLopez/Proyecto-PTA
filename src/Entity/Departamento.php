<?php

namespace App\Entity;

use App\Repository\DepartamentoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartamentoRepository::class)]
class Departamento
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'boolean')]
    private bool $activo = true;

    #[ORM\ManyToMany(targetEntity: IndicadoresBasicos::class)]
    #[ORM\JoinTable(name: 'departamento_indicadores_basicos')]
    private Collection $indicadoresBasicos;

    public function __construct()
    {
        $this->indicadoresBasicos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;
        return $this;
    }

    public function getIndicadoresBasicos(): Collection
    {
        return $this->indicadoresBasicos;
    }

    public function addIndicadoresBasico(IndicadoresBasicos $indicadoresBasico): static
    {
        if (!$this->indicadoresBasicos->contains($indicadoresBasico)) {
            $this->indicadoresBasicos->add($indicadoresBasico);
        }

        return $this;
    }

    public function removeIndicadoresBasico(IndicadoresBasicos $indicadoresBasico): static
    {
        $this->indicadoresBasicos->removeElement($indicadoresBasico);

        return $this;
    }
}