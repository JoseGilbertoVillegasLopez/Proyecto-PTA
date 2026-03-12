<?php

namespace App\Entity;

use App\Repository\ReportePtaEvidenciasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportePtaEvidenciasRepository::class)]
class ReportePtaEvidencias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $imagenes = [];

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descripcion = null;

    #[ORM\ManyToOne(inversedBy: 'reportePtaEvidencias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ReportePtaIndicador $reportePtaIndicador = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImagenes(): array
    {
        return $this->imagenes;
    }

    public function setImagenes(array $imagenes): static
    {
        $this->imagenes = $imagenes;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getReportePtaIndicador(): ?ReportePtaIndicador
    {
        return $this->reportePtaIndicador;
    }

    public function setReportePtaIndicador(?ReportePtaIndicador $reportePtaIndicador): static
    {
        $this->reportePtaIndicador = $reportePtaIndicador;

        return $this;
    }
}
