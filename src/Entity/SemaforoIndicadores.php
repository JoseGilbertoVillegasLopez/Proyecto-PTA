<?php

namespace App\Entity;

use App\Repository\SemaforoIndicadoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SemaforoIndicadoresRepository::class)]
#[ORM\Table(name: 'semaforo_indicadores')]
#[ORM\UniqueConstraint(name: 'UNIQ_SEMAFORO_INDICADOR_CICLO', columns: ['id_indicadorbasico', 'ciclo_indicadores_id'])]
class SemaforoIndicadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'ciclo_indicadores_id', referencedColumnName: 'id', nullable: false)]
    private ?CicloIndicadores $ciclo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_indicadorbasico', referencedColumnName: 'id', nullable: false)]
    private ?IndicadoresBasicos $indicadorBasico = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $cantidad1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $cantidad2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $resultadoCiclo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCiclo(): ?CicloIndicadores
    {
        return $this->ciclo;
    }

    public function setCiclo(?CicloIndicadores $ciclo): static
    {
        $this->ciclo = $ciclo;

        return $this;
    }

    public function getIndicadorBasico(): ?IndicadoresBasicos
    {
        return $this->indicadorBasico;
    }

    public function setIndicadorBasico(?IndicadoresBasicos $indicadorBasico): static
    {
        $this->indicadorBasico = $indicadorBasico;

        return $this;
    }

    public function getCantidad1(): ?string
    {
        return $this->cantidad1;
    }

    public function setCantidad1(?string $cantidad1): static
    {
        $this->cantidad1 = $cantidad1;

        return $this;
    }

    public function getCantidad2(): ?string
    {
        return $this->cantidad2;
    }

    public function setCantidad2(?string $cantidad2): static
    {
        $this->cantidad2 = $cantidad2;

        return $this;
    }

    public function getResultadoCiclo(): ?string
    {
        return $this->resultadoCiclo;
    }

    public function setResultadoCiclo(?string $resultadoCiclo): static
    {
        $this->resultadoCiclo = $resultadoCiclo;

        return $this;
    }
}
