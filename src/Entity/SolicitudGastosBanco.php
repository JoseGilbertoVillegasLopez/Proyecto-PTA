<?php

namespace App\Entity;

use App\Repository\SolicitudGastosBancoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudGastosBancoRepository::class)]
#[ORM\Table(name: 'solicitud_gastos_bancos')]
class SolicitudGastosBanco
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(length: 20, options: ['default' => 'activo'])]
    private string $estado = 'activo';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}
