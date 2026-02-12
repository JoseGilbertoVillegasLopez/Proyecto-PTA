<?php

namespace App\Entity;

use App\Repository\PartidasPresupuestalesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartidasPresupuestalesRepository::class)]
class PartidasPresupuestales
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $capitulo = null;

    #[ORM\Column]
    private ?int $partida = null;

    #[ORM\Column(length: 255)]
    private ?string $descripcion = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $activo = true;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCapitulo(): ?int
    {
        return $this->capitulo;
    }

    public function setCapitulo(int $capitulo): static
    {
        $this->capitulo = $capitulo;

        return $this;
    }

    public function getPartida(): ?int
    {
        return $this->partida;
    }

    public function setPartida(int $partida): static
    {
        $this->partida = $partida;

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
