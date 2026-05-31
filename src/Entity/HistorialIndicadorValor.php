<?php

namespace App\Entity;

use App\Repository\HistorialIndicadorValorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * =========================================================
 * ENTIDAD: HistorialIndicadorValor
 * ---------------------------------------------------------
 * Registra cada captura o corrección del valor mensual
 * de un indicador (el snapshot acumulado al corte del mes).
 *
 * Por qué existe:
 * - El campo Indicadores::$valorMensual solo guarda el
 *   ÚLTIMO valor por mes (estado actual).
 * - Esta entidad guarda el HISTORIAL COMPLETO de cambios,
 *   permitiendo a los directivos ver quién cambió qué y cuándo.
 *
 * SEMÁNTICA DEL CAMPO $motivo:
 *   null  = registro en tiempo (mes actual, primera captura)
 *   texto = registro que requirió justificación:
 *           - Mes pasado: primera vez que se registra (tardío)
 *           - Cualquier mes: corrección de un valor ya guardado
 *
 * Ejemplo de uso:
 *   En agosto el responsable registra 600 → motivo null
 *   En septiembre corrige agosto a 620 → motivo "Error de captura"
 *   Ambos registros quedan en el historial con su fecha.
 * =========================================================
 */
#[ORM\Entity(repositoryClass: HistorialIndicadorValorRepository::class)]
class HistorialIndicadorValor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Indicador al que corresponde este registro.
     */
    #[ORM\ManyToOne(inversedBy: 'historialValores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicadores $indicador = null;

    /**
     * Número de mes al que corresponde el valor (1=Enero … 12=Diciembre).
     */
    #[ORM\Column(nullable: false)]
    private int $mes;

    /**
     * Valor acumulado registrado al corte de este mes.
     * Siempre es un valor absoluto, sin importar esPorcentaje del indicador.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $valor;

    /**
     * Justificación del registro.
     * null  → captura en tiempo sin necesidad de explicación.
     * texto → registro tardío o corrección de valor anterior.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motivo = null;

    /**
     * Fecha y hora exacta de la captura.
     */
    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $fecha;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndicador(): ?Indicadores
    {
        return $this->indicador;
    }

    public function setIndicador(?Indicadores $indicador): static
    {
        $this->indicador = $indicador;
        return $this;
    }

    public function getMes(): int
    {
        return $this->mes;
    }

    public function setMes(int $mes): static
    {
        $this->mes = $mes;
        return $this;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function setValor(string $valor): static
    {
        $this->valor = $valor;
        return $this;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function setMotivo(?string $motivo): static
    {
        $this->motivo = $motivo;
        return $this;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): static
    {
        $this->fecha = $fecha;
        return $this;
    }
}
