<?php

namespace App\Entity;

use App\Repository\TipoSolicitudRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipoSolicitudRepository::class)]
class TipoSolicitud
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

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

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}
