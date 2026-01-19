<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mime\Address;

/**
 * =========================================================
 * PTA — Progress Notification Service
 * ---------------------------------------------------------
 * Responsabilidad:
 *  - Recibir el resultado del resolver
 *  - Construir correos por PTA
 *  - Enviar notificaciones de forma asíncrona
 *
 * IMPORTANTE:
 *  - NO decide fechas
 *  - NO decide qué acciones están pendientes
 *  - SOLO notifica
 * =========================================================
 */
final class PtaProgressNotificationService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $appName,
    ) {}

    /**
     * @param string $tipoAviso  PRIMER_AVISO | SEGUNDO_AVISO | AVISO_ADMINISTRATIVO
     * @param array<int, array{encabezado: Encabezado, acciones: array}> $resultados
     */
    public function notify(string $tipoAviso, array $resultados): void
    {
        foreach ($resultados as $item) {
            $pta = $item['encabezado'];
            $acciones = $item['acciones'];

            $this->enviarCorreo($tipoAviso, $pta, $acciones);

            // --------------------------------------------------
            // ⚠️ LIMITADOR SOLO PARA ENTORNOS DE DEMO / MAILTRAP
            // Evita el error "Too many emails per second"
            // --------------------------------------------------
            if ($_ENV['APP_ENV'] === 'dev') {
                usleep(2_000_000); // 2 segundos
            }
        }
    }


    private function enviarCorreo(string $tipoAviso, Encabezado $pta, array $acciones): void
    {
        $responsable = $pta->getResponsable();
        $responsablesExtra = $pta->getResponsables();

        $to = new Address(
            $responsable->getCorreo(),
            (string) $responsable
        );

        $cc = [];
        if ($responsablesExtra) {
            $cc[] = new Address($responsablesExtra->getSupervisor()->getCorreo());
            $cc[] = new Address($responsablesExtra->getAval()->getCorreo());
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->cc(...$cc)
            ->subject($this->buildSubject($tipoAviso))
            ->htmlTemplate('admin/emails/pta_acciones_sin_avance.html.twig')
            ->textTemplate('admin/emails/pta_acciones_sin_avance.txt.twig')
            ->context([
                'tipoAviso' => $tipoAviso,
                'pta' => $pta,
                'acciones' => array_map(function ($accion) use ($pta) {
                    // Acciones::$indicador guarda el INDICE (int)
                    // Buscamos el Indicador real dentro del Encabezado por su "indice"
                    $indicadorObj = null;
                    foreach ($pta->getIndicadores() as $ind) {
                        if ((int) $ind->getIndice() === (int) $accion->getIndicador()) {
                            $indicadorObj = $ind;
                            break;
                        }
                    }

                    return [
                        'texto' => $accion->getAccion(),
                        'indiceIndicador' => $accion->getIndicador(),
                        'indicadorNombre' => $indicadorObj?->getIndicador() ?? 'N/D',
                        'indicadorPeriodo' => $indicadorObj?->getPeriodo() ?? 'N/D',
                        'indicadorFormula' => $indicadorObj?->getFormula() ?? 'N/D',
                    ];
                }, $acciones),
                'appName' => $this->appName,
                'fecha' => new \DateTimeImmutable(),
            ]);


        $this->bus->dispatch(new SendEmailMessage($email));
    }

    private function buildSubject(string $tipoAviso): string
    {
        return match ($tipoAviso) {
            'PRIMER_AVISO' =>
                "[{$this->appName}] Aviso preventivo — Acciones sin avance",
            'SEGUNDO_AVISO' =>
                "[{$this->appName}] Advertencia — Acciones sin avance",
            'AVISO_ADMINISTRATIVO' =>
                "[{$this->appName}] Aviso administrativo — Acciones sin avance",
            default =>
                "[{$this->appName}] Notificación PTA",
        };
    }
}
