<?php

namespace App\Entity;

use App\Repository\AccionesRepository;
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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valorAlcanzado = null;

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

    public function getValorAlcanzado(): ?string
    {
        return $this->valorAlcanzado;
    }

    public function setValorAlcanzado(string $valorAlcanzado): static
    {
        $this->valorAlcanzado = $valorAlcanzado;

        return $this;
    }
}
