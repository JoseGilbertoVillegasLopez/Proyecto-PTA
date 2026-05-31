<?php

namespace App\Entity;

use App\Repository\IndicadoresRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * =========================================================
 * ENTIDAD: Indicadores
 * ---------------------------------------------------------
 * Representa un indicador de desempeño dentro del PTA.
 * Cada indicador define la meta que el responsable quiere
 * alcanzar durante el año de ejecución.
 *
 * MODELO DE SEGUIMIENTO (nuevo):
 * - valorMensual: snapshot acumulado registrado por el
 *   responsable en cada "mes reportable" del indicador.
 *   Un mes es reportable si pertenece al periodo de al menos
 *   una de las acciones asociadas a este indicador.
 *
 * - esPorcentaje: si es true, la meta ($valor) es un
 *   porcentaje de cambio relativo al valorBase (Opción A).
 *   Ejemplo: base=700, meta=30% → objetivo real = 700 + 210 = 910.
 *   Los valores mensuales SIEMPRE son absolutos, independiente
 *   de este flag. El flag solo cambia la fórmula de avance y el display.
 *
 * FÓRMULAS DE AVANCE:
 *   esPorcentaje=false, POSITIVA: ((actual-base)/(meta-base)) * 100
 *   esPorcentaje=false, NEGATIVA: ((base-actual)/(base-meta)) * 100
 *   esPorcentaje=true,  POSITIVA: ((actual-base)/(base*meta/100)) * 100
 *   esPorcentaje=true,  NEGATIVA: ((base-actual)/(base*meta/100)) * 100
 * =========================================================
 */
#[ORM\Entity(repositoryClass: IndicadoresRepository::class)]
class Indicadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * PTA al que pertenece este indicador.
     */
    #[ORM\ManyToOne(inversedBy: 'indicadores', targetEntity: Encabezado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Encabezado $encabezado = null;

    /**
     * Nombre/descripción del indicador.
     */
    #[ORM\Column(length: 255)]
    private ?string $indicador = null;

    /**
     * Descripción de cómo se calcula el indicador (texto libre).
     */
    #[ORM\Column(length: 255)]
    private ?string $formula = null;

    /**
     * Meta a alcanzar.
     * - esPorcentaje=false: valor neto absoluto (ej. 1000 alumnos)
     * - esPorcentaje=true:  porcentaje de cambio relativo al valorBase
     *   (ej. 30 = incrementar 30% sobre el base)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valor = null;

    /**
     * Periodicidad de medición del indicador (texto, generalmente "Anual").
     * Diferente al periodo de las acciones — este es informativo.
     */
    #[ORM\Column(length: 255)]
    private ?string $periodo = null;

    /**
     * Índice lógico único dentro del PTA.
     * Asignado por JS al crear el PTA; NO es el ID de la BD.
     * Se usa para relacionar este indicador con sus acciones.
     */
    #[ORM\Column]
    private ?int $indice = null;

    /**
     * Dirección esperada del indicador:
     * POSITIVA = debe crecer (ej. alumnos titulados)
     * NEGATIVA = debe decrecer (ej. quejas recibidas)
     */
    #[ORM\Column(length: 255)]
    private ?string $tendencia = null;

    /**
     * Valor de partida (estado actual al crear el PTA).
     * Siempre es un valor absoluto, independientemente de esPorcentaje.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valorBase = null;

    /**
     * Indica si la meta ($valor) está expresada como porcentaje
     * de cambio relativo al valorBase (Opción A).
     *
     * false → meta es un valor neto (ej. llegar a 1000)
     * true  → meta es % de incremento/decremento sobre el base
     *          Los valores mensuales siguen siendo absolutos.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $esPorcentaje = false;

    /**
     * Valores mensuales registrados por el responsable.
     *
     * Estructura:
     *   { "NombreMes": "decimal_string" }
     *   Ejemplo: { "Agosto": "600.00", "Diciembre": "950.00" }
     *
     * Cada valor es un SNAPSHOT ACUMULADO al corte de ese mes
     * (no un delta del mes). Se usa directamente para graficar
     * y calcular el porcentaje de avance.
     *
     * Los meses disponibles (reportables) se determinan como
     * la unión de los periodos de todas las acciones del indicador.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $valorMensual = null;

    /**
     * Historial de cambios en los valores mensuales.
     * Cada registro almacena el valor capturado, el mes,
     * la fecha y el motivo (obligatorio si fue mes pasado).
     *
     * @var Collection<int, HistorialIndicadorValor>
     */
    #[ORM\OneToMany(
        targetEntity: HistorialIndicadorValor::class,
        mappedBy: 'indicador',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $historialValores;

    public function __construct()
    {
        $this->historialValores = new ArrayCollection();
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

    public function getIndicador(): ?string
    {
        return $this->indicador;
    }

    public function setIndicador(string $indicador): static
    {
        $this->indicador = $indicador;
        return $this;
    }

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function setFormula(string $formula): static
    {
        $this->formula = $formula;
        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(string $valor): static
    {
        $this->valor = $valor;
        return $this;
    }

    public function getPeriodo(): ?string
    {
        return $this->periodo;
    }

    public function setPeriodo(string $periodo): static
    {
        $this->periodo = $periodo;
        return $this;
    }

    public function getIndice(): ?int
    {
        return $this->indice;
    }

    public function setIndice(int $indice): static
    {
        $this->indice = $indice;
        return $this;
    }

    public function getTendencia(): ?string
    {
        return $this->tendencia;
    }

    public function setTendencia(string $tendencia): static
    {
        $this->tendencia = $tendencia;
        return $this;
    }

    public function getValorBase(): ?string
    {
        return $this->valorBase;
    }

    public function setValorBase(string $valorBase): static
    {
        $this->valorBase = $valorBase;
        return $this;
    }

    public function isEsPorcentaje(): bool
    {
        return $this->esPorcentaje;
    }

    public function setEsPorcentaje(bool $esPorcentaje): static
    {
        $this->esPorcentaje = $esPorcentaje;
        return $this;
    }

    public function getValorMensual(): ?array
    {
        return $this->valorMensual;
    }

    public function setValorMensual(?array $valorMensual): static
    {
        $this->valorMensual = $valorMensual;
        return $this;
    }

    /**
     * @return Collection<int, HistorialIndicadorValor>
     */
    public function getHistorialValores(): Collection
    {
        return $this->historialValores;
    }

    public function addHistorialValor(HistorialIndicadorValor $historialValor): static
    {
        if (!$this->historialValores->contains($historialValor)) {
            $this->historialValores->add($historialValor);
            $historialValor->setIndicador($this);
        }
        return $this;
    }

    public function removeHistorialValor(HistorialIndicadorValor $historialValor): static
    {
        if ($this->historialValores->removeElement($historialValor)) {
            if ($historialValor->getIndicador() === $this) {
                $historialValor->setIndicador(null);
            }
        }
        return $this;
    }
}
