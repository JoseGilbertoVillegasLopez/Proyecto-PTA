<?php

namespace App\Entity;

use App\Repository\SolicitudGastosPartidaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudGastosPartidaRepository::class)]
class SolicitudGastosPartida
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'partidas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SolicitudGastos $solicitud = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PartidasPresupuestales $partida = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolicitud(): ?SolicitudGastos
    {
        return $this->solicitud;
    }

    public function setSolicitud(?SolicitudGastos $solicitud): static
    {
        $this->solicitud = $solicitud;

        return $this;
    }

    public function getPartida(): ?PartidasPresupuestales
    {
        return $this->partida;
    }

    public function setPartida(?PartidasPresupuestales $partida): static
    {
        $this->partida = $partida;

        return $this;
    }

    public function getMonto(): string
    {
        return $this->monto;
    }

    public function setMonto(string $monto): static
    {
        $this->monto = $monto;

        return $this;
    }
}
