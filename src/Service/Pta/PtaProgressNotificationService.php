<?php

declare(strict_types=1);

namespace App\Service\Pta;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\User;
use App\Repository\PersonalRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;

/**
 * =========================================================
 * PTA — Progress Notification Service
 * =========================================================
 */
final class PtaProgressNotificationService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly PersonalRepository $personalRepository,
        private readonly UserRepository $userRepository,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $appName,
    ) {}

    /**
     * @param string $tipoAviso
     * @param array<int, array{encabezado: Encabezado, acciones: Acciones[]}> $resultados
     * @param array{anio?: int, mes?: int} $contextoPeriodo
     */
    public function notify(string $tipoAviso, array $resultados, array $contextoPeriodo = []): void
    {
        if (empty($resultados)) {
            return;
        }

        [$anioEvaluar, $mesEvaluar] = $this->resolvePeriodoEvaluado($tipoAviso, $contextoPeriodo);

        // -----------------------------------------
        // PRIMER / SEGUNDO AVISO (15 / 25)
        // -----------------------------------------
        if ($tipoAviso !== 'AVISO_ADMINISTRATIVO') {
            foreach ($resultados as $item) {
                $this->enviarCorreoIndividual(
                    $tipoAviso,
                    $item['encabezado'],
                    $item['acciones'],
                    $anioEvaluar,
                    $mesEvaluar
                );

                if (($_ENV['APP_ENV'] ?? null) === 'dev') {
                    usleep(2_000_000);
                }
            }
            return;
        }

        // -----------------------------------------
        // AVISO ADMINISTRATIVO (día 1)
        // -----------------------------------------
        $this->enviarCorreosAdministrativosConsolidados(
            $resultados,
            $anioEvaluar,
            $mesEvaluar
        );
    }

    // =====================================================
    // INDIVIDUAL (PRIMER / SEGUNDO AVISO)
    // =====================================================

    private function enviarCorreoIndividual(
        string $tipoAviso,
        Encabezado $pta,
        array $acciones,
        int $anio,
        int $mes
    ): void {
        $responsable = $pta->getResponsable();
        if (!$responsable instanceof Personal) {
            return;
        }

        $to = $this->asAddress($responsable);
        if ($to === null) {
            return;
        }

        $cc = [];
        if ($pta->getResponsables()) {
            $sup = $pta->getResponsables()->getSupervisor();
            $aval = $pta->getResponsables()->getAval();

            foreach ([$sup, $aval] as $p) {
                if ($p instanceof Personal && ($addr = $this->asAddress($p))) {
                    $cc[] = $addr;
                }
            }
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->cc(...$cc)
            ->subject($this->buildSubject($tipoAviso, $anio, $mes))
            ->htmlTemplate('admin/emails/pta_acciones_sin_avance.html.twig')
            ->textTemplate('admin/emails/pta_acciones_sin_avance.txt.twig')
            ->context([
                'tipoAviso' => $tipoAviso,
                'pta' => $pta,
                'acciones' => $this->mapAccionesForEmail($pta, $acciones),
                'appName' => $this->appName,
                'fecha' => new \DateTimeImmutable(),
                'anio' => $anio,
                'mes' => $mes,
                'mesNombre' => $this->nombreMes($mes),
            ]);

        $this->bus->dispatch(new SendEmailMessage($email));
    }

    // =====================================================
    // ADMINISTRATIVO CONSOLIDADO
    // =====================================================

    private function enviarCorreosAdministrativosConsolidados(
        array $resultados,
        int $anio,
        int $mes
    ): void {
        $porResponsable = [];
        $porSupervisorProyecto = [];
        $porSupervisorDirecto = [];
        $globalDG = [];

        foreach ($resultados as $item) {
            $pta = $item['encabezado'];
            $acciones = $item['acciones'];
            $resp = $pta->getResponsable();

            if (!$resp instanceof Personal) {
                continue;
            }

            $payload = [
                'pta' => [
                    'id' => $pta->getId(),
                    'nombre' => $pta->getNombre(),
                    'objetivo' => $pta->getObjetivo(),
                ],
                'responsable' => [
                    'id' => $resp->getId(),
                    'nombre' => (string) $resp,
                ],
                'acciones' => $this->mapAccionesForEmail($pta, $acciones),
            ];

            $globalDG[] = $payload;

            // Responsable
            $porResponsable[$resp->getId()]['responsable'] = $resp;
            $porResponsable[$resp->getId()]['items'][] = $payload;

            // Supervisor de proyecto
            $supProyecto = $pta->getResponsables()?->getSupervisor();
            if ($supProyecto instanceof Personal) {
                $porSupervisorProyecto[$supProyecto->getId()]['supervisor'] = $supProyecto;
                $porSupervisorProyecto[$supProyecto->getId()]['items'][] = $payload;
            }

            // Supervisor directo
            $supDirecto = $this->findSupervisorDirectoPersonal($resp);
            if ($supDirecto instanceof Personal) {
                $porSupervisorDirecto[$supDirecto->getId()]['supervisor'] = $supDirecto;
                $porSupervisorDirecto[$supDirecto->getId()]['items'][] = $payload;
            }
        }

        // -----------------------------------------
        // Correos a responsables
        // -----------------------------------------
        foreach ($porResponsable as $data) {
            $this->sendTemplate(
                $data['responsable'],
                'admin/emails/pta_admin_responsable.html.twig',
                [
                    'responsable' => $data['responsable'],
                    'items' => $data['items'],
                ],
                $anio,
                $mes,
                ' — Responsable'
            );
        }

        // -----------------------------------------
        // Correos a supervisores (unificado)
        // -----------------------------------------
        $todosSupervisores = array_unique(
            array_merge(
                array_keys($porSupervisorProyecto),
                array_keys($porSupervisorDirecto)
            )
        );

        foreach ($todosSupervisores as $sid) {
            $sup =
                $porSupervisorProyecto[$sid]['supervisor']
                ?? $porSupervisorDirecto[$sid]['supervisor']
                ?? null;

            if (!$sup instanceof Personal) {
                continue;
            }

            $items = array_merge(
                $porSupervisorProyecto[$sid]['items'] ?? [],
                $porSupervisorDirecto[$sid]['items'] ?? []
            );

            $tipoSupervision =
                isset($porSupervisorProyecto[$sid], $porSupervisorDirecto[$sid])
                    ? 'mixto'
                    : (isset($porSupervisorProyecto[$sid]) ? 'proyecto' : 'directo');

            $this->sendTemplate(
                $sup,
                'admin/emails/pta_admin_supervisor.html.twig',
                [
                    'supervisor' => $sup,
                    'items' => $items,
                    'tipoSupervision' => $tipoSupervision,
                ],
                $anio,
                $mes,
                ' — Supervisor'
            );
        }

        // -----------------------------------------
        // Dirección General (uno solo)
        // -----------------------------------------
        $dg = $this->findDirectorGeneralPersonal();
        if ($dg instanceof Personal && !empty($globalDG)) {
            $this->sendTemplate(
                $dg,
                'admin/emails/pta_admin_direccion_general.html.twig',
                [
                    'direccionGeneral' => $dg,
                    'items' => $globalDG,
                ],
                $anio,
                $mes,
                ' — Dirección General'
            );
        }
    }

    // =====================================================
    // HELPERS
    // =====================================================

    private function sendTemplate(
        Personal $toPerson,
        string $template,
        array $extraContext,
        int $anio,
        int $mes,
        string $suffix
    ): void {
        $to = $this->asAddress($toPerson);
        if ($to === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->subject($this->buildSubject('AVISO_ADMINISTRATIVO', $anio, $mes) . $suffix)
            ->htmlTemplate($template)
            ->context(array_merge([
                'appName' => $this->appName,
                'fecha' => new \DateTimeImmutable(),
                'anio' => $anio,
                'mes' => $mes,
                'mesNombre' => $this->nombreMes($mes),
            ], $extraContext));

        $this->bus->dispatch(new SendEmailMessage($email));

        if (($_ENV['APP_ENV'] ?? null) === 'dev') {
            usleep(2_000_000);
        }
    }

    private function asAddress(?Personal $p): ?Address
    {
        if (!$p || trim((string) $p->getCorreo()) === '') {
            return null;
        }
        return new Address($p->getCorreo(), (string) $p);
    }

    private function nombreMes(int $mes): string
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ][$mes] ?? "Mes {$mes}";
    }

    private function buildSubject(string $tipoAviso, int $anio, int $mes): string
    {
        $m = $this->nombreMes($mes);

        return match ($tipoAviso) {
            'PRIMER_AVISO' =>
                "[{$this->appName}] Aviso preventivo — {$m} {$anio}",
            'SEGUNDO_AVISO' =>
                "[{$this->appName}] Advertencia — {$m} {$anio}",
            default =>
                "[{$this->appName}] Aviso administrativo — {$m} {$anio}",
        };
    }

    private function resolvePeriodoEvaluado(string $tipoAviso, array $ctx): array
    {
        if (isset($ctx['anio'], $ctx['mes'])) {
            return [(int) $ctx['anio'], (int) $ctx['mes']];
        }

        $today = new \DateTimeImmutable();
        $anio = (int) $today->format('Y');
        $mes = (int) $today->format('m');

        if ($tipoAviso === 'AVISO_ADMINISTRATIVO' && (int) $today->format('d') === 1) {
            $mes--;
            if ($mes === 0) {
                $mes = 12;
                $anio--;
            }
        }

        return [$anio, $mes];
    }

    private function mapAccionesForEmail(Encabezado $pta, array $acciones): array
    {
        return array_map(function (Acciones $a) use ($pta) {
            $indicador = null;
            foreach ($pta->getIndicadores() as $ind) {
                if ($ind->getIndice() === $a->getIndicador()) {
                    $indicador = $ind;
                    break;
                }
            }

            return [
                'texto' => $a->getAccion(),
                'indiceIndicador' => $a->getIndicador(),
                'indicadorNombre' => $indicador?->getIndicador() ?? 'N/D',
            ];
        }, $acciones);
    }

    private function findDirectorGeneralPersonal(): ?Personal
    {
        foreach ($this->userRepository->findAll() as $u) {
            if (
                $u instanceof User &&
                $u->isActivo() &&
                in_array('ROLE_DIRECCION_GENERAL', $u->getRoles(), true)
            ) {
                return $u->getPersonal();
            }
        }
        return null;
    }

    private function findSupervisorDirectoPersonal(Personal $p): ?Personal
    {
        $puestoSup = $p->getPuesto()?->getSupervisorDirecto();
        if ($puestoSup === null) {
            return null;
        }

        foreach ($this->personalRepository->findAll() as $cand) {
            if (
                $cand instanceof Personal &&
                $cand->isActivo() &&
                $cand->getPuesto()?->getId() === $puestoSup->getId()
            ) {
                return $cand;
            }
        }
        return null;
    }
}
