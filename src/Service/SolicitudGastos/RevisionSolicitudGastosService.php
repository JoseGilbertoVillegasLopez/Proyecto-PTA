<?php

namespace App\Service\SolicitudGastos;

use App\Entity\Personal;
use App\Entity\SolicitudGastos;
use Doctrine\ORM\EntityManagerInterface;

class RevisionSolicitudGastosService
{
    private const ESTADOS_RESUELTOS = ['aceptada', 'rechazada'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SolicitudGastosResolucionMailer $resolucionMailer,
    ) {}

    /**
     * Efecto lateral de "se abrió la solicitud": la primera vez que el titular de un cargo
     * entra a una solicitud aún pendiente para él, queda marcada como 'revisando' y el
     * estado global pasa de 'pendiente' a 'en_revision'.
     */
    public function marcarEnRevision(SolicitudGastos $solicitud, Personal $personal, string $cargo): void
    {
        $revision = $solicitud->getRevisionPorCargo($cargo);
        if ($revision === null || $revision->getEstado() !== 'pendiente') {
            return;
        }

        $revision->setPersonal($personal);
        $revision->setFechaApertura(new \DateTime());
        $revision->setEstado('revisando');

        if ($solicitud->getEstado() === 'pendiente') {
            $solicitud->setEstado('en_revision');
        }

        $this->em->flush();
    }

    /**
     * Registra el voto del cargo indicado y recalcula el estado global de la solicitud.
     *
     * Regla de rechazo interina: cualquier voto de rechazo es definitivo. Queda aislada en
     * este método para poder ajustarla cuando finanzas confirme el criterio real (unánime,
     * mayoría, etc.).
     */
    public function votar(SolicitudGastos $solicitud, Personal $personal, string $cargo, bool $aceptar, ?string $comentario): void
    {
        $revision = $solicitud->getRevisionPorCargo($cargo);
        if ($revision === null) {
            throw new \InvalidArgumentException('No existe una revisión para ese cargo en esta solicitud.');
        }

        if (in_array($revision->getEstado(), ['aceptada', 'rechazada'], true)) {
            throw new \InvalidArgumentException('Ya emitiste tu voto en esta solicitud.');
        }

        $revision->setPersonal($personal);
        $revision->setEstado($aceptar ? 'aceptada' : 'rechazada');
        $revision->setComentario($comentario);
        $revision->setFechaResolucion(new \DateTime());

        $estabaResuelta = in_array($solicitud->getEstado(), self::ESTADOS_RESUELTOS, true);

        $this->recalcularEstadoGlobal($solicitud);

        $this->em->flush();

        if (!$estabaResuelta && in_array($solicitud->getEstado(), self::ESTADOS_RESUELTOS, true)) {
            try {
                $this->resolucionMailer->notificarResolucion($solicitud);
            } catch (\Throwable) {
                // El voto ya quedó persistido; un fallo al preparar/despachar el correo
                // (PDF, plantilla, etc.) no debe revertir la acción del encargado.
            }
        }
    }

    /**
     * Si el cargo puede votar en esta solicitud: su revisión sigue abierta y la solicitud
     * todavía no quedó resuelta (aceptada/rechazada/resuelto).
     */
    public function puedeVotar(SolicitudGastos $solicitud, string $cargo): bool
    {
        if (!in_array($solicitud->getEstado(), ['pendiente', 'en_revision'], true)) {
            return false;
        }

        $revision = $solicitud->getRevisionPorCargo($cargo);

        return $revision !== null && in_array($revision->getEstado(), ['pendiente', 'revisando'], true);
    }

    private function recalcularEstadoGlobal(SolicitudGastos $solicitud): void
    {
        $huboRechazo = false;
        $todasAceptadas = true;

        foreach ($solicitud->getRevisiones() as $revision) {
            if ($revision->getEstado() === 'rechazada') {
                $huboRechazo = true;
            }
            if ($revision->getEstado() !== 'aceptada') {
                $todasAceptadas = false;
            }
        }

        if ($huboRechazo) {
            $solicitud->setEstado('rechazada');
        } elseif ($todasAceptadas) {
            $solicitud->setEstado('aceptada');
        } else {
            $solicitud->setEstado('en_revision');
        }
    }
}
