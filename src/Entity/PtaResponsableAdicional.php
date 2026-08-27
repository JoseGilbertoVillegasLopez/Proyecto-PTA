<?php

namespace App\Entity;

use App\Repository\PtaResponsableAdicionalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PtaResponsableAdicionalRepository::class)]
class PtaResponsableAdicional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'responsablesAdicionales', targetEntity: Encabezado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    #[ORM\ManyToOne(targetEntity: Personal::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

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

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(?Personal $personal): static
    {
        $this->personal = $personal;

        return $this;
    }
}
