<?php

namespace App\Entity;

use App\Repository\SolicitudGastosRevisionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudGastosRevisionRepository::class)]
#[ORM\Table(name: 'solicitud_gastos_revision')]
#[ORM\UniqueConstraint(name: 'uniq_solicitud_cargo', columns: ['solicitud_id', 'cargo'])]
class SolicitudGastosRevision
{
    /** 'pendiente' | 'revisando' | 'aceptada' | 'rechazada' */
    public const ESTADOS = ['pendiente', 'revisando', 'aceptada', 'rechazada'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'revisiones')]
    #[ORM\JoinColumn(name: 'solicitud_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?SolicitudGastos $solicitud = null;

    /** 'revisor' | 'supervisor' | 'autoriza' — ver ModuloAcceso::CARGOS */
    #[ORM\Column(length: 20)]
    private ?string $cargo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Personal $personal = null;

    #[ORM\Column(length: 20, options: ['default' => 'pendiente'])]
    private string $estado = 'pendiente';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comentario = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaApertura = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaResolucion = null;

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

    public function getCargo(): ?string
    {
        return $this->cargo;
    }

    public function setCargo(string $cargo): static
    {
        $this->cargo = $cargo;

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

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
    }

    public function getFechaApertura(): ?\DateTimeInterface
    {
        return $this->fechaApertura;
    }

    public function setFechaApertura(?\DateTimeInterface $fechaApertura): static
    {
        $this->fechaApertura = $fechaApertura;

        return $this;
    }

    public function getFechaResolucion(): ?\DateTimeInterface
    {
        return $this->fechaResolucion;
    }

    public function setFechaResolucion(?\DateTimeInterface $fechaResolucion): static
    {
        $this->fechaResolucion = $fechaResolucion;

        return $this;
    }
}
