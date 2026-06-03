<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GrupoIndicadoresBasicos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $grupo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrupo(): ?string
    {
        return $this->grupo;
    }

    public function setGrupo(string $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }
}
