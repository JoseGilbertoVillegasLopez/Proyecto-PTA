<?php

namespace App\Entity;

use App\Repository\ModuloSistemaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuloSistemaRepository::class)]
#[ORM\Table(name: 'modulo_sistema')]
class ModuloSistema
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column]
    private bool $usaEncargado = true;

    #[ORM\OneToMany(mappedBy: 'modulo', targetEntity: ModuloAcceso::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accesos;

    public function __construct()
    {
        $this->accesos = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }

    public function isUsaEncargado(): bool { return $this->usaEncargado; }
    public function setUsaEncargado(bool $usaEncargado): static { $this->usaEncargado = $usaEncargado; return $this; }

    /** @return Collection<int, ModuloAcceso> */
    public function getAccesos(): Collection { return $this->accesos; }

    public function addAcceso(ModuloAcceso $acceso): static
    {
        if (!$this->accesos->contains($acceso)) {
            $this->accesos->add($acceso);
            $acceso->setModulo($this);
        }
        return $this;
    }

    public function removeAcceso(ModuloAcceso $acceso): static
    {
        $this->accesos->removeElement($acceso);
        return $this;
    }
}
