<?php

namespace App\Entity;

use App\Repository\ReporteIndicadorTrimestreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReporteIndicadorTrimestreRepository::class)]
#[ORM\Table(name: 'reporte_indicador_trimestre')]
#[ORM\UniqueConstraint(name: 'UNIQ_REPORTE_INDICADOR_PERSONAL_ANIO_TRIMESTRE', columns: ['personal_id', 'anio', 'trimestre'])]
class ReporteIndicadorTrimestre
{
    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ENTREGADO = 'entregado';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'personal_id', referencedColumnName: 'id', nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column]
    private ?int $anio = null;

    #[ORM\Column]
    private ?int $trimestre = null;

    #[ORM\Column(length: 20, options: ['default' => self::ESTADO_BORRADOR])]
    private string $estado = self::ESTADO_BORRADOR;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $creadoFecha = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $entregadoFecha = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'departamento_id', referencedColumnName: 'id', nullable: false)]
    private ?Departamento $departamento = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'puesto_id', referencedColumnName: 'id', nullable: false)]
    private ?Puesto $puesto = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAnio(): ?int
    {
        return $this->anio;
    }

    public function setAnio(int $anio): static
    {
        $this->anio = $anio;

        return $this;
    }

    public function getTrimestre(): ?int
    {
        return $this->trimestre;
    }

    public function setTrimestre(int $trimestre): static
    {
        $this->trimestre = $trimestre;

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

    public function isEntregado(): bool
    {
        return $this->estado === self::ESTADO_ENTREGADO;
    }

    public function getCreadoFecha(): ?\DateTimeImmutable
    {
        return $this->creadoFecha;
    }

    public function setCreadoFecha(\DateTimeImmutable $creadoFecha): static
    {
        $this->creadoFecha = $creadoFecha;

        return $this;
    }

    public function getEntregadoFecha(): ?\DateTimeImmutable
    {
        return $this->entregadoFecha;
    }

    public function setEntregadoFecha(?\DateTimeImmutable $entregadoFecha): static
    {
        $this->entregadoFecha = $entregadoFecha;

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

    public function getPuesto(): ?Puesto
    {
        return $this->puesto;
    }

    public function setPuesto(?Puesto $puesto): static
    {
        $this->puesto = $puesto;

        return $this;
    }
}
