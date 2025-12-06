<?php

namespace App\Entity;

use App\Repository\PersonalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalRepository::class)]
class Personal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $ap_paterno = null;

    #[ORM\Column(length: 255)]
    private ?string $ap_materno = null;

    #[ORM\Column(length: 255)]
    private ?string $correo = null;

    #[ORM\Column (options: ['default' => true])]
    private ?bool $activo = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Puesto $puesto = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departamento $departamento = null;

    #[ORM\OneToOne(mappedBy: 'personal')]
    private ?User $user = null;

    /**
     * @var Collection<int, Encabezado>
     */
    #[ORM\OneToMany(targetEntity: Encabezado::class, mappedBy: 'responsable')]
    private Collection $pta;

    /**
     * @var Collection<int, Responsables>
     */
    #[ORM\OneToMany(targetEntity: Responsables::class, mappedBy: 'supervisor')]
    private Collection $supervisor;

    /**
     * @var Collection<int, Responsables>
     */
    #[ORM\OneToMany(targetEntity: Responsables::class, mappedBy: 'aval')]
    private Collection $aval;

    public function __construct()
    {
        $this->pta = new ArrayCollection();
        $this->supervisor = new ArrayCollection();
        $this->aval = new ArrayCollection();
    }

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

    public function getApPaterno(): ?string
    {
        return $this->ap_paterno;
    }

    public function setApPaterno(string $ap_paterno): static
    {
        $this->ap_paterno = $ap_paterno;

        return $this;
    }

    public function getApMaterno(): ?string
    {
        return $this->ap_materno;
    }

    public function setApMaterno(string $ap_materno): static
    {
        $this->ap_materno = $ap_materno;

        return $this;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): static
    {
        $this->correo = $correo;

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

    public function getPuesto(): ?Puesto
    {
        return $this->puesto;
    }

    public function setPuesto(?Puesto $puesto): static
    {
        $this->puesto = $puesto;

        return $this;
    }

    public function getDepartamento(): ?Departamento
    {
        return $this->departamento;
    }

    public function setDepartamento(?Departamento $departamento): static
    {
        $this->departamento = $departamento;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        // set the owning side of the relation if necessary
        if ($user->getPersonal() !== $this) {
            $user->setPersonal($this);
        }

        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Encabezado>
     */
    public function getPta(): Collection
    {
        return $this->pta;
    }

    public function addPtum(Encabezado $ptum): static
    {
        if (!$this->pta->contains($ptum)) {
            $this->pta->add($ptum);
            $ptum->setResponsable($this);
        }

        return $this;
    }

    public function removePtum(Encabezado $ptum): static
    {
        if ($this->pta->removeElement($ptum)) {
            // set the owning side to null (unless already changed)
            if ($ptum->getResponsable() === $this) {
                $ptum->setResponsable(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Responsables>
     */
    public function getSupervisor(): Collection
    {
        return $this->supervisor;
    }

    public function addSupervisor(Responsables $supervisor): static
    {
        if (!$this->supervisor->contains($supervisor)) {
            $this->supervisor->add($supervisor);
            $supervisor->setSupervisor($this);
        }

        return $this;
    }

    public function removeSupervisor(Responsables $supervisor): static
    {
        if ($this->supervisor->removeElement($supervisor)) {
            // set the owning side to null (unless already changed)
            if ($supervisor->getSupervisor() === $this) {
                $supervisor->setSupervisor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Responsables>
     */
    public function getAval(): Collection
    {
        return $this->aval;
    }

    public function addAval(Responsables $aval): static
    {
        if (!$this->aval->contains($aval)) {
            $this->aval->add($aval);
            $aval->setAval($this);
        }

        return $this;
    }

    public function removeAval(Responsables $aval): static
    {
        if ($this->aval->removeElement($aval)) {
            // set the owning side to null (unless already changed)
            if ($aval->getAval() === $this) {
                $aval->setAval(null);
            }
        }

        return $this;
    }

    //agregando funcion para mostrar el nombre completo en el formulario de Usuario
    public function __toString(): string
    {
        return $this->nombre . ' ' . $this->ap_paterno . ' ' . $this->ap_materno;
    }
}
