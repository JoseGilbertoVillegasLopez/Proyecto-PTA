<?php

namespace App\Entity;

use App\Repository\SolicitudGastosFolioSerieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contador de folio independiente para una Serie (Puesto::serie), usado cuando
 * SolicitudGastosConfiguracion::folioAlcance = 'por_serie'. Cada serie lleva su
 * propio consecutivo (los números se repiten entre series, ej. RM-10 y RF-10
 * pueden coexistir).
 */
#[ORM\Entity(repositoryClass: SolicitudGastosFolioSerieRepository::class)]
#[ORM\Table(name: 'solicitud_gastos_folio_serie')]
class SolicitudGastosFolioSerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $serie = '';

    #[ORM\Column(options: ['default' => 0])]
    private int $contadorActual = 0;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $periodoActual = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerie(): string
    {
        return $this->serie;
    }

    public function setSerie(string $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    public function getContadorActual(): int
    {
        return $this->contadorActual;
    }

    public function setContadorActual(int $contadorActual): static
    {
        $this->contadorActual = $contadorActual;

        return $this;
    }

    public function getPeriodoActual(): ?string
    {
        return $this->periodoActual;
    }

    public function setPeriodoActual(?string $periodoActual): static
    {
        $this->periodoActual = $periodoActual;

        return $this;
    }
}
