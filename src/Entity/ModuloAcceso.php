<?php

namespace App\Entity;

use App\Repository\ModuloAccesoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuloAccesoRepository::class)]
#[ORM\Table(name: 'modulo_acceso')]
#[ORM\UniqueConstraint(name: 'uniq_modulo_puesto_tipo', columns: ['modulo_id', 'puesto_id', 'tipo'])]
class ModuloAcceso
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ModuloSistema::class, inversedBy: 'accesos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ModuloSistema $modulo;

    #[ORM\ManyToOne(targetEntity: Puesto::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Puesto $puesto;

    /** 'encargado' | 'acceso' */
    #[ORM\Column(length: 20)]
    private string $tipo;

    public function getId(): ?int { return $this->id; }

    public function getModulo(): ModuloSistema { return $this->modulo; }
    public function setModulo(ModuloSistema $modulo): static { $this->modulo = $modulo; return $this; }

    public function getPuesto(): Puesto { return $this->puesto; }
    public function setPuesto(Puesto $puesto): static { $this->puesto = $puesto; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
}
