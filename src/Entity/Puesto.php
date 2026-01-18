<?php

namespace App\Entity;

use App\Repository\PuestoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PuestoRepository::class)]
class Puesto
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

    #[ORM\Column(type: 'boolean')]
    private bool $activo = true;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subordinados')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $supervisorDirecto = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'supervisorDirecto')]
    private Collection $subordinados;

    public function __construct()
    {
        $this->subordinados = new ArrayCollection();
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;
        return $this;
    }

    public function getSupervisorDirecto(): ?self
    {
        return $this->supervisorDirecto;
    }

    public function setSupervisorDirecto(?self $supervisorDirecto): static
    {
        $this->supervisorDirecto = $supervisorDirecto;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubordinados(): Collection
    {
        return $this->subordinados;
    }

    public function addSubordinado(self $subordinado): static
    {
        if (!$this->subordinados->contains($subordinado)) {
            $this->subordinados->add($subordinado);
            $subordinado->setSupervisorDirecto($this);
        }

        return $this;
    }

    public function removeSubordinado(self $subordinado): static
    {
        if ($this->subordinados->removeElement($subordinado)) {
            // set the owning side to null (unless already changed)
            if ($subordinado->getSupervisorDirecto() === $this) {
                $subordinado->setSupervisorDirecto(null);
            }
        }

        return $this;
    }

    /**
 * =====================================================
 * JERARQUÍA — SUBORDINADOS RECURSIVOS
 * -----------------------------------------------------
 * Devuelve TODOS los puestos subordinados
 * (subordinados directos + indirectos)
 * =====================================================
 *
 * @return Collection<int, self>
 */
public function getSubordinadosRecursivos(): Collection
{
    $todos = new ArrayCollection();

    foreach ($this->subordinados as $sub) {
        if (!$todos->contains($sub)) {
            $todos->add($sub);

            foreach ($sub->getSubordinadosRecursivos() as $subSub) {
                if (!$todos->contains($subSub)) {
                    $todos->add($subSub);
                }
            }
        }
    }

    return $todos;
}


}
