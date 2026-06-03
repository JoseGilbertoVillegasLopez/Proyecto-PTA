<?php

namespace App\Entity;

use App\Repository\HistorialAccionesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * =========================================================
 * ENTIDAD: HistorialAcciones
 * ---------------------------------------------------------
 * Registra cada captura o corrección del estado de
 * cumplimiento de una acción en un mes específico.
 *
 * SEMÁNTICA DEL CAMPO $valor:
 *   1 = la acción fue marcada como CUMPLIDA ese mes
 *   0 = la acción fue marcada como NO CUMPLIDA ese mes
 *
 * SEMÁNTICA DEL CAMPO $motivo:
 *   null  = registro en tiempo (mes actual, marcada como cumplida)
 *   texto = registro que requirió justificación:
 *           - Mes pasado (cualquier marca, tardía)
 *           - Mes actual marcada como NO cumplida (¿por qué no?)
 *
 * Cada vez que el responsable cambia el estado de un mes
 * (incluyendo correcciones posteriores) se genera un nuevo
 * registro. Esto da trazabilidad completa para directivos.
 * =========================================================
 */
#[ORM\Entity(repositoryClass: HistorialAccionesRepository::class)]
class HistorialAcciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Acción a la que corresponde este registro de historial.
     */
    #[ORM\ManyToOne(inversedBy: 'historialAcciones')]
    #[ORM\JoinColumn(nullable: false)]
    private Acciones $accion;

    /**
     * Número de mes (1 = Enero, 12 = Diciembre).
     */
    #[ORM\Column(nullable: false)]
    private int $mes;

    /**
     * Estado registrado:
     *   1 = CUMPLIDA
     *   0 = NO CUMPLIDA
     */
    #[ORM\Column(nullable: false)]
    private int $valor;

    /**
     * Motivo del registro (ver semántica en el docblock de clase).
     * null cuando es un registro normal en tiempo sin justificación.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motivo = null;

    /**
     * Fecha y hora exacta en que se realizó el registro.
     */
    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $fecha;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccion(): Acciones
    {
        return $this->accion;
    }

    public function setAccion(Acciones $accion): static
    {
        $this->accion = $accion;
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

    public function getValor(): int
    {
        return $this->valor;
    }

    public function setValor(int $valor): static
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
