<?php

namespace App\Service\SolicitudGastos;

use App\Entity\SolicitudGastos;
use App\Repository\SolicitudGastosConfiguracionRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;

/**
 * Notifica al solicitante cuando su solicitud de gastos queda aceptada o rechazada.
 * Usa una plantilla distinta por estado: solo la aceptada lleva el PDF adjunto (es
 * la única que se imprime para firmar); una rechazada puede además no tener folio
 * real (ver SolicitudGastosConfiguracion::folioAplicaA).
 */
class SolicitudGastosResolucionMailer
{
    private const CARGO_LABELS = ['revisor' => 'Revisor', 'supervisor' => 'Supervisor', 'autoriza' => 'Autoriza'];

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly SolicitudGastosPdfExportService $pdfExportService,
        private readonly SolicitudGastosConfiguracionRepository $configRepo,
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

        // 'resuelto' es una aceptada con comprobante ya subido — sigue siendo un
        // desenlace de aprobación (relevante para reenvíos manuales del correo).
        $aceptada = in_array($solicitud->getEstado(), ['aceptada', 'resuelto'], true);
        $folio = $solicitud->getFolioFormateado();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($correo, (string) $solicitante))
            ->subject($this->armarAsunto($aceptada, $folio))
            ->htmlTemplate(sprintf('admin/emails/solicitud_gastos_resolucion_%s.html.twig', $aceptada ? 'aceptada' : 'rechazada'))
            ->textTemplate(sprintf('admin/emails/solicitud_gastos_resolucion_%s.txt.twig', $aceptada ? 'aceptada' : 'rechazada'))
            ->context([
                'appName' => $this->appName,
                'fecha' => new \DateTimeImmutable(),
                'nombreSolicitante' => (string) $solicitante,
                'folio' => $folio,
                'aceptada' => $aceptada,
                'fechaSolicitud' => $solicitud->getFechaSolicitud(),
                'fechaResolucion' => new \DateTimeImmutable(),
                'soporteEmail' => $this->soporteEmail,
                'comentarios' => $this->comentariosRevision($solicitud),
            ]);

        // Solo la aceptada se imprime para firmar; la rechazada no necesita el PDF.
        if ($aceptada) {
            $email->attach(
                $this->pdfExportService->generarBinario($solicitud),
                sprintf('solicitud_gastos_%s.pdf', $folio ?? $solicitud->getId()),
                'application/pdf'
            );
        }

        $this->bus->dispatch(new SendEmailMessage($email));
    }

    private function armarAsunto(bool $aceptada, ?string $folio): string
    {
        $estadoTexto = $aceptada ? 'Aprobada' : 'Rechazada';

        if ($folio === null) {
            return sprintf('[%s] Solicitud de gastos — %s', $this->appName, $estadoTexto);
        }

        return sprintf('[%s] Solicitud de gastos folio %s — %s', $this->appName, $folio, $estadoTexto);
    }

    /**
     * Comentario de cada uno de los 3 cargos (revisor/supervisor/autoriza) que
     * dejó uno real, con quién lo dejó (nombre + puesto), para aceptada y
     * rechazada por igual — no solo el motivo de quien rechazó. Solo si la
     * configuración lo permite (SolicitudGastosConfiguracion::mostrarMotivoRechazo,
     * mismo interruptor que controla el modal interno en show.html.twig). Los
     * cargos sin comentario se omiten por completo, no se listan con un espacio
     * vacío.
     *
     * @return array<int, array{cargo: string, nombre: ?string, puesto: ?string, comentario: string}>
     */
    private function comentariosRevision(SolicitudGastos $solicitud): array
    {
        if (!$this->configRepo->obtener()->isMostrarMotivoRechazo()) {
            return [];
        }

        $comentarios = [];

        foreach ($solicitud->getRevisiones() as $revision) {
            $comentario = trim((string) $revision->getComentario());
            if ($comentario === '') {
                continue;
            }

            $personal = $revision->getPersonal();

            $comentarios[] = [
                'cargo' => self::CARGO_LABELS[$revision->getCargo()] ?? $revision->getCargo(),
                'nombre' => $personal ? (string) $personal : null,
                'puesto' => $personal?->getPuesto()?->getNombre(),
                'comentario' => $comentario,
            ];
        }

        return $comentarios;
    }
}
