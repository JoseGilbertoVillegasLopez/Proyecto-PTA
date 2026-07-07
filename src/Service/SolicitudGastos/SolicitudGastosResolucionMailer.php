<?php

namespace App\Service\SolicitudGastos;

use App\Entity\SolicitudGastos;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;

/**
 * Notifica al solicitante cuando su solicitud de gastos queda aceptada o rechazada,
 * adjuntando el PDF de la solicitud. Versión inicial (folio y motivo de rechazo
 * pendientes de confirmar con finanzas, ver Notas técnicas del módulo en la vault).
 */
class SolicitudGastosResolucionMailer
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly SolicitudGastosPdfExportService $pdfExportService,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $appName,
        private readonly string $soporteEmail,
    ) {
    }

    public function notificarResolucion(SolicitudGastos $solicitud): void
    {
        $solicitante = $solicitud->getSolicitante();
        $correo = $solicitante?->getCorreo();

        if ($solicitante === null || $correo === null || trim($correo) === '') {
            return;
        }

        $aceptada = $solicitud->getEstado() === 'aceptada';
        $folio = $this->folioAproximado($solicitud);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($correo, (string) $solicitante))
            ->subject(sprintf(
                '[%s] Solicitud de gastos folio %s — %s',
                $this->appName,
                $folio,
                $aceptada ? 'Aprobada' : 'Rechazada'
            ))
            ->htmlTemplate('admin/emails/solicitud_gastos_resolucion.html.twig')
            ->textTemplate('admin/emails/solicitud_gastos_resolucion.txt.twig')
            ->context([
                'appName' => $this->appName,
                'fecha' => new \DateTimeImmutable(),
                'nombreSolicitante' => (string) $solicitante,
                'folio' => $folio,
                'aceptada' => $aceptada,
                'fechaSolicitud' => $solicitud->getFechaSolicitud(),
                'fechaResolucion' => new \DateTimeImmutable(),
                'soporteEmail' => $this->soporteEmail,
            ])
            ->attach(
                $this->pdfExportService->generarBinario($solicitud),
                sprintf('solicitud_gastos_%s.pdf', $folio),
                'application/pdf'
            );

        $this->bus->dispatch(new SendEmailMessage($email));
    }

    /**
     * El folio real de finanzas todavía no existe (ver Notas técnicas del módulo);
     * se aproxima con Serie del puesto del solicitante + ID de la solicitud, mismo
     * criterio de aproximación que ya usa pdf.html.twig para la Serie.
     */
    private function folioAproximado(SolicitudGastos $solicitud): string
    {
        $serie = $solicitud->getSolicitante()?->getPuesto()?->getSerie();

        return $serie ? sprintf('%s-%d', $serie, $solicitud->getId()) : (string) $solicitud->getId();
    }
}
