<?php

namespace App\Entity;

use App\Repository\HistorialAccionesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistorialAccionesRepository::class)]
class HistorialAcciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'historialAcciones')]
    #[ORM\JoinColumn(nullable: false)]
    private Acciones $accion;

    #[ORM\Column(nullable: false)]
    private int $mes;

    #[ORM\Column(nullable: false)]
    private int $valor;

    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $fecha;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccion(): Acciones
    {
        return $this->accion;
    }

    public function setAccion(Acciones $accion): static
    {
        $this->accion = $accion;
        return $this;
    }

    public function getMes(): int
    {
        return $this->mes;
    }

    public function setMes(int $mes): static
    {
        $this->mes = $mes;
        return $this;
    }

    public function getValor(): int
    {
        return $this->valor;
    }

    public function setValor(int $valor): static
    {
        $this->valor = $valor;
        return $this;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): static
    {
        $this->fecha = $fecha;
        return $this;
    }
}
