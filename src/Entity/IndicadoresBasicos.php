<?php

namespace App\Entity;

use App\Repository\IndicadoresBasicosRepository;
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

}
