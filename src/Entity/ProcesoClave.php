<?php

namespace App\Entity;

use App\Repository\ProcesoClaveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcesoClaveRepository::class)]
class ProcesoClave
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $pei = null;

    #[ORM\Column(length: 255)]
    private ?string $paig = null;

    #[ORM\Column(length: 255)]
    private ?string $metaPdiPta = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $activo = true;


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

    public function getPei(): ?string
    {
        return $this->pei;
    }

    public function setPei(string $pei): static
    {
        $this->pei = $pei;

        return $this;
    }

    public function getPaig(): ?string
    {
        return $this->paig;
    }

    public function setPaig(string $paig): static
    {
        $this->paig = $paig;

        return $this;
    }

    public function getMetaPdiPta(): ?string
    {
        return $this->metaPdiPta;
    }

    public function setMetaPdiPta(string $metaPdiPta): static
    {
        $this->metaPdiPta = $metaPdiPta;

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
