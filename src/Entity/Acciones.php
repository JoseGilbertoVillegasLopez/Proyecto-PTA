<?php

namespace App\Entity;

use App\Repository\AccionesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * =========================================================
 * ENTIDAD: Acciones
 * ---------------------------------------------------------
 * Representa una acción dentro del PTA.
 * Cada acción pertenece a un Encabezado y está asociada
 * lógicamente a un Indicador mediante su índice (no FK).
 *
 * MODELO DE SEGUIMIENTO:
 * - mesesCumplidos: JSON que registra si la acción fue
 *   cumplida (true) o no (false) en cada mes del periodo.
 *   Ejemplo: {"Enero": true, "Febrero": false, "Marzo": null}
 *   null = aún no registrado para ese mes.
 *
 * - El historial completo de cambios (incluyendo correcciones
 *   de meses pasados) se guarda en HistorialAcciones.
 * =========================================================
 */
#[ORM\Entity(repositoryClass: AccionesRepository::class)]
class Acciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * PTA al que pertenece esta acción.
     */
    #[ORM\ManyToOne(inversedBy: 'acciones', targetEntity: Encabezado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    /**
     * Descripción textual de la acción.
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $accion = null;

    /**
     * Meses del año en que se planea ejecutar esta acción.
     * Almacenados como nombres en español: ["Enero", "Marzo", "Junio"]
     * Se usa para saber qué meses son activos (editables) en el seguimiento.
     */
    #[ORM\Column]
    private array $periodo = [];

    /**
     * Estado de cumplimiento por mes del periodo.
     *
     * Estructura:
     *   { "NombreMes": true|false|null }
     *   true  = acción cumplida ese mes
     *   false = acción NO cumplida ese mes (requirió motivo)
     *   null  = todavía no registrado
     *
     * Solo contiene los meses definidos en $periodo.
     * Los meses futuros se ignoran hasta que llegue su turno.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mesesCumplidos = null;

    /**
     * Índice lógico del indicador asociado a esta acción.
     *
     * IMPORTANTE: NO es el ID de la BD de Indicadores.
     * Es el valor del campo Indicadores::$indice, que es un
     * contador asignado por JS durante la creación del PTA.
     * La relación indicador↔acción existe solo dentro del
     * contexto de un mismo Encabezado.
     */
    #[ORM\Column]
    private ?int $indicador = null;

    /**
     * Historial completo de cambios de cumplimiento.
     * Cada registro representa una captura o corrección,
     * con motivo obligatorio si fue en mes pasado o marcada como ✗.
     *
     * @var Collection<int, HistorialAcciones>
     */
    #[ORM\OneToMany(targetEntity: HistorialAcciones::class, mappedBy: 'accion')]
    private Collection $historialAcciones;

    public function __construct()
    {
        $this->historialAcciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEncabezado(): ?Encabezado
    {
        return $this->encabezado;
    }

    public function setEncabezado(?Encabezado $encabezado): static
    {
        $this->encabezado = $encabezado;
        return $this;
    }

    public function getAccion(): ?string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): static
    {
        $this->accion = $accion;
        return $this;
    }

    public function getPeriodo(): array
    {
        return $this->periodo;
    }

    public function setPeriodo(array $periodo): static
    {
        $this->periodo = $periodo;
        return $this;
    }

    public function getMesesCumplidos(): ?array
    {
        return $this->mesesCumplidos;
    }

    public function setMesesCumplidos(?array $mesesCumplidos): static
    {
        $this->mesesCumplidos = $mesesCumplidos;
        return $this;
    }

    public function getIndicador(): ?int
    {
        return $this->indicador;
    }

    public function setIndicador(int $indicador): static
    {
        $this->indicador = $indicador;
        return $this;
    }

    /**
     * @return Collection<int, HistorialAcciones>
     */
    public function getHistorialAcciones(): Collection
    {
        return $this->historialAcciones;
    }

    public function addHistorialAccione(HistorialAcciones $historialAccione): static
    {
        if (!$this->historialAcciones->contains($historialAccione)) {
            $this->historialAcciones->add($historialAccione);
            $historialAccione->setAccion($this);
        }
        return $this;
    }

    public function removeHistorialAccione(HistorialAcciones $historialAccione): static
    {
        if ($this->historialAcciones->removeElement($historialAccione)) {
            if ($historialAccione->getAccion() === $this) {
                $historialAccione->setAccion(null);
            }
        }
        return $this;
    }
}
