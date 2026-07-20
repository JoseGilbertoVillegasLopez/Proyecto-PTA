<?php

namespace App\Service\SolicitudGastos;

use App\Entity\SolicitudGastos;
use App\Entity\SolicitudGastosConfiguracion;
use App\Entity\SolicitudGastosFolioSerie;
use App\Repository\SolicitudGastosConfiguracionRepository;
use App\Repository\SolicitudGastosFolioSerieRepository;

/**
 * Asigna el folio consecutivo real a una solicitud ya resuelta, según la
 * configuración vigente (a qué estados aplica, ciclo de reinicio, y si el
 * contador es global o independiente por serie). No hace flush: el caller
 * (RevisionSolicitudGastosService::votar()) persiste el cambio junto con el
 * voto en el mismo flush.
 */
class SolicitudGastosFolioService
{
    public function __construct(
        private readonly SolicitudGastosConfiguracionRepository $configRepo,
        private readonly SolicitudGastosFolioSerieRepository $folioSerieRepo,
    ) {
    }

    public function asignarSiAplica(SolicitudGastos $solicitud): void
    {
        if ($solicitud->getFolio() !== null) {
            return;
        }

        $config = $this->configRepo->obtener();
        $estado = $solicitud->getEstado();

        $aplica = $estado === 'aceptada' || ($estado === 'rechazada' && $config->aplicaFolioARechazadas());
        if (!$aplica) {
            return;
        }

        if ($config->esFolioPorSerie()) {
            $this->asignarPorSerie($solicitud, $config);
        } else {
            $this->asignarGlobal($solicitud, $config);
        }
    }

    private function asignarGlobal(SolicitudGastos $solicitud, SolicitudGastosConfiguracion $config): void
    {
        $periodoActual = $this->calcularPeriodoActual($config->getFolioCicloReinicio());
        if ($periodoActual !== null && $periodoActual !== $config->getFolioPeriodoActual()) {
            $config->setFolioContadorActual(0);
            $config->setFolioPeriodoActual($periodoActual);
        }

        $config->setFolioContadorActual($config->getFolioContadorActual() + 1);
        $solicitud->setFolio($config->getFolioContadorActual());
        $solicitud->setFolioSerie('');
        $solicitud->setFolioPeriodo($periodoActual ?? '');
    }

    /**
     * Sin serie capturada en el puesto del solicitante, la solicitud se queda
     * sin folio (igual criterio que el resto del módulo cuando falta un dato:
     * se omite, no se rompe el flujo).
     */
    private function asignarPorSerie(SolicitudGastos $solicitud, SolicitudGastosConfiguracion $config): void
    {
        $serie = $solicitud->getSolicitante()?->getPuesto()?->getSerie();
        if ($serie === null || trim($serie) === '') {
            return;
        }

        $folioSerie = $this->folioSerieRepo->obtenerOCrear($serie);

        $periodoActual = $this->calcularPeriodoActual($config->getFolioCicloReinicio());
        if ($periodoActual !== null && $periodoActual !== $folioSerie->getPeriodoActual()) {
            $folioSerie->setContadorActual(0);
            $folioSerie->setPeriodoActual($periodoActual);
        }

        $folioSerie->setContadorActual($folioSerie->getContadorActual() + 1);
        $solicitud->setFolio($folioSerie->getContadorActual());
        $solicitud->setFolioSerie($serie);
        $solicitud->setFolioPeriodo($periodoActual ?? '');
    }

    /**
     * Ajuste manual desde /finanzas/configuracion (modo global): "el último
     * folio que usamos en físico fue X, que las digitales sigan desde ahí".
     * Además de fijar el contador, actualiza folioPeriodoActual al periodo
     * vigente para el ciclo seleccionado — si no se hiciera, la siguiente
     * asignación detectaría un cambio de periodo y reiniciaría el contador a
     * 0, perdiendo el ajuste.
     */
    public function ajustarContadorManual(SolicitudGastosConfiguracion $config, int $ultimoFolioUsado): void
    {
        $config->setFolioContadorActual($ultimoFolioUsado);
        $config->setFolioPeriodoActual($this->calcularPeriodoActual($config->getFolioCicloReinicio()));
    }

    /**
     * Mismo ajuste manual que ajustarContadorManual(), pero para el contador
     * de una serie específica (modo por_serie).
     */
    public function ajustarContadorSerieManual(SolicitudGastosFolioSerie $folioSerie, int $ultimoFolioUsado, string $ciclo): void
    {
        $folioSerie->setContadorActual($ultimoFolioUsado);
        $folioSerie->setPeriodoActual($this->calcularPeriodoActual($ciclo));
    }

    /**
     * Null para el ciclo continuo (el contador nunca reinicia).
     */
    public function calcularPeriodoActual(string $ciclo): ?string
    {
        return match ($ciclo) {
            SolicitudGastosConfiguracion::FOLIO_CICLO_ANUAL => (new \DateTimeImmutable())->format('Y'),
            SolicitudGastosConfiguracion::FOLIO_CICLO_SEMESTRAL => $this->periodoSemestral(),
            default => null,
        };
    }

    private function periodoSemestral(): string
    {
        $ahora = new \DateTimeImmutable();
        $semestre = ((int) $ahora->format('n') <= 6) ? 'S1' : 'S2';

        return $ahora->format('Y') . '-' . $semestre;
    }
}
