<?php

namespace App\Entity;

use App\Repository\ResponsablesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsablesRepository::class)]
class Responsables
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'responsables', cascade: ['persist', 'remove'], targetEntity: Encabezado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    #[ORM\ManyToOne(inversedBy: 'supervisor', targetEntity: Personal::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $supervisor = null;

    #[ORM\ManyToOne(inversedBy: 'aval', targetEntity: Personal::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $aval = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEncabezado(): ?Encabezado
    {
        return $this->encabezado;
    }

    public function setEncabezado(Encabezado $encabezado): static
    {
        $this->encabezado = $encabezado;

        return $this;
    }

    public function getSupervisor(): ?Personal
    {
        return $this->supervisor;
    }

    public function setSupervisor(?Personal $supervisor): static
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    public function getAval(): ?Personal
    {
        return $this->aval;
    }

    public function setAval(?Personal $aval): static
    {
        $this->aval = $aval;

        return $this;
    }
}
