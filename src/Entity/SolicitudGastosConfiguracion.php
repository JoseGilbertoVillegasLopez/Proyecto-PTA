<?php

namespace App\Entity;

use App\Repository\SolicitudGastosConfiguracionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fila única (id=1) con las reglas de negocio del módulo que finanzas no ha
 * confirmado por escrito (criterio de aprobación, si se informa el motivo de
 * rechazo, y el ciclo de folios). Configurable desde /finanzas/configuracion
 * para no seguir esperando respuesta y poder ajustarlo sin tocar código.
 */
#[ORM\Entity(repositoryClass: SolicitudGastosConfiguracionRepository::class)]
#[ORM\Table(name: 'solicitud_gastos_configuracion')]
class SolicitudGastosConfiguracion
{
    public const CRITERIO_UNANIME = 'unanime';
    public const CRITERIO_MAYORIA = 'mayoria';
    public const CRITERIOS_APROBACION = [self::CRITERIO_UNANIME, self::CRITERIO_MAYORIA];

    public const FOLIO_SOLO_ACEPTADAS = 'solo_aceptadas';
    public const FOLIO_ACEPTADAS_Y_RECHAZADAS = 'aceptadas_y_rechazadas';
    public const FOLIO_APLICA_A_OPCIONES = [self::FOLIO_SOLO_ACEPTADAS, self::FOLIO_ACEPTADAS_Y_RECHAZADAS];

    public const FOLIO_CICLO_CONTINUO = 'continuo';
    public const FOLIO_CICLO_SEMESTRAL = 'semestral';
    public const FOLIO_CICLO_ANUAL = 'anual';
    public const FOLIO_CICLOS = [self::FOLIO_CICLO_CONTINUO, self::FOLIO_CICLO_SEMESTRAL, self::FOLIO_CICLO_ANUAL];

    public const FOLIO_ALCANCE_GLOBAL = 'global';
    public const FOLIO_ALCANCE_POR_SERIE = 'por_serie';
    public const FOLIO_ALCANCES = [self::FOLIO_ALCANCE_GLOBAL, self::FOLIO_ALCANCE_POR_SERIE];

    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(length: 20, options: ['default' => self::CRITERIO_UNANIME])]
    private string $criterioAprobacion = self::CRITERIO_UNANIME;

    #[ORM\Column(options: ['default' => false])]
    private bool $mostrarMotivoRechazo = false;

    #[ORM\Column(length: 30, options: ['default' => self::FOLIO_SOLO_ACEPTADAS])]
    private string $folioAplicaA = self::FOLIO_SOLO_ACEPTADAS;

    #[ORM\Column(length: 20, options: ['default' => self::FOLIO_CICLO_CONTINUO])]
    private string $folioCicloReinicio = self::FOLIO_CICLO_CONTINUO;

    #[ORM\Column(length: 20, options: ['default' => self::FOLIO_ALCANCE_GLOBAL])]
    private string $folioAlcance = self::FOLIO_ALCANCE_GLOBAL;

    #[ORM\Column(options: ['default' => 0])]
    private int $folioContadorActual = 0;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $folioPeriodoActual = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCriterioAprobacion(): string
    {
        return $this->criterioAprobacion;
    }

    public function setCriterioAprobacion(string $criterioAprobacion): static
    {
        $this->criterioAprobacion = $criterioAprobacion;

        return $this;
    }

    public function isMayoria(): bool
    {
        return $this->criterioAprobacion === self::CRITERIO_MAYORIA;
    }

    public function isMostrarMotivoRechazo(): bool
    {
        return $this->mostrarMotivoRechazo;
    }

    public function setMostrarMotivoRechazo(bool $mostrarMotivoRechazo): static
    {
        $this->mostrarMotivoRechazo = $mostrarMotivoRechazo;

        return $this;
    }

    public function getFolioAplicaA(): string
    {
        return $this->folioAplicaA;
    }

    public function setFolioAplicaA(string $folioAplicaA): static
    {
        $this->folioAplicaA = $folioAplicaA;

        return $this;
    }

    public function aplicaFolioARechazadas(): bool
    {
        return $this->folioAplicaA === self::FOLIO_ACEPTADAS_Y_RECHAZADAS;
    }

    public function getFolioCicloReinicio(): string
    {
        return $this->folioCicloReinicio;
    }

    public function setFolioCicloReinicio(string $folioCicloReinicio): static
    {
        $this->folioCicloReinicio = $folioCicloReinicio;

        return $this;
    }

    public function getFolioContadorActual(): int
    {
        return $this->folioContadorActual;
    }

    public function setFolioContadorActual(int $folioContadorActual): static
    {
        $this->folioContadorActual = $folioContadorActual;

        return $this;
    }

    public function getFolioPeriodoActual(): ?string
    {
        return $this->folioPeriodoActual;
    }

    public function setFolioPeriodoActual(?string $folioPeriodoActual): static
    {
        $this->folioPeriodoActual = $folioPeriodoActual;

        return $this;
    }

    public function getFolioAlcance(): string
    {
        return $this->folioAlcance;
    }

    public function setFolioAlcance(string $folioAlcance): static
    {
        $this->folioAlcance = $folioAlcance;

        return $this;
    }

    public function esFolioPorSerie(): bool
    {
        return $this->folioAlcance === self::FOLIO_ALCANCE_POR_SERIE;
    }
}
