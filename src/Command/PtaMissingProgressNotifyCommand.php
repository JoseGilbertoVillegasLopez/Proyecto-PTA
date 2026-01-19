<?php

namespace App\Command;

use App\Service\Pta\PtaMissingProgressResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\Service\Pta\PtaProgressNotificationService;


/**
 * =========================================================
 * PTA — Notificación de Acciones sin Avance
 * ---------------------------------------------------------
 * Este comando se ejecuta automáticamente (cron) una vez
 * al día y decide si debe notificar acciones sin avance.
 *
 * Días válidos:
 *  - Día 15  → Aviso preventivo
 *  - Día 25  → Advertencia
 *  - Día 1   → Aviso administrativo del mes anterior
 *
 * IMPORTANTE:
 *  - Si hoy NO es uno de esos días → el comando termina
 *  - NO envía correos directamente (solo orquesta)
 * =========================================================
 */
#[AsCommand(
    name: 'pta:notificar-acciones-sin-avance',
    description: 'Detecta acciones PTA sin avances y prepara notificaciones según la fecha.'
)]
final class PtaMissingProgressNotifyCommand extends Command
{
    public function __construct(
        private readonly PtaMissingProgressResolver $resolver,
        private readonly PtaProgressNotificationService $notifier
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // --------------------------------------------------
        // 1️⃣ Obtener fecha actual
        // --------------------------------------------------
        $demoDay = $input->getOption('demo');

        if ($demoDay !== null) {
            // En modo demo, simulamos una fecha coherente
            // Tomamos el día indicado dentro del mes actual
            $today = new \DateTimeImmutable(sprintf(
                '%s-%s-%02d',
                date('Y'),
                date('m'),
                (int) $demoDay
            ));

            $output->writeln(
                sprintf('<comment>[MODO DEMO] Simulando ejecución como si fuera día %d</comment>', $demoDay)
            );
        } else {
            $today = new \DateTimeImmutable('today');
        }

        $day = (int) $today->format('d');
        $month = (int) $today->format('m');
        $year = (int) $today->format('Y');



        // --------------------------------------------------
        // 2️⃣ Determinar si hoy es un día válido
        // --------------------------------------------------
        $tipoAviso = null;
$anioEvaluar = $year;
$mesEvaluar = $month;

// -------------------------------
// MODO DEMO: SIEMPRE MES ACTUAL
// -------------------------------
if ($demoDay !== null) {

    if ($day === 15) {
        $tipoAviso = 'PRIMER_AVISO';
    } elseif ($day === 25) {
        $tipoAviso = 'SEGUNDO_AVISO';
    } elseif ($day === 1) {
        $tipoAviso = 'AVISO_ADMINISTRATIVO';
    } else {
        $output->writeln('<info>[DEMO] Día no válido.</info>');
        return Command::SUCCESS;
    }

} else {

    // -------------------------------
    // MODO REAL (producción)
    // -------------------------------
    if ($day === 15) {
        $tipoAviso = 'PRIMER_AVISO';
    } elseif ($day === 25) {
        $tipoAviso = 'SEGUNDO_AVISO';
    } elseif ($day === 1) {
        $tipoAviso = 'AVISO_ADMINISTRATIVO';

        // Día 1 REAL → mes anterior
        $mesEvaluar--;
        if ($mesEvaluar === 0) {
            $mesEvaluar = 12;
            $anioEvaluar--;
        }
    } else {
        $output->writeln('<info>Hoy no es día de notificación.</info>');
        return Command::SUCCESS;
    }
}


        $output->writeln(sprintf(
            '<comment>Ejecutando %s para %02d/%d</comment>',
            $tipoAviso,
            $mesEvaluar,
            $anioEvaluar
        ));

        // --------------------------------------------------
        // 3️⃣ Resolver acciones sin avance
        // --------------------------------------------------
        $resultados = $this->resolver->resolve($anioEvaluar, $mesEvaluar);

        // --------------------------------------------------
        // 4️⃣ Resultado (por ahora solo informativo)
        // --------------------------------------------------
        if (empty($resultados)) {
            $output->writeln('<info>No se encontraron acciones sin avance.</info>');
            return Command::SUCCESS;
        }

        $totalPtas = count($resultados);
        $output->writeln("<info>PTAs con acciones pendientes: {$totalPtas}</info>");

        foreach ($resultados as $item) {
            $pta = $item['encabezado'];
            $acciones = $item['acciones'];

            $output->writeln(sprintf(
                ' - PTA #%d (%s) → %d acción(es) sin avance',
                $pta->getId(),
                $pta->getNombre(),
                count($acciones)
            ));
        }

        // --------------------------------------------------
        // 5️⃣ (Siguiente paso)
        // Aquí después llamaremos al Mailer
        // --------------------------------------------------
        $this->notifier->notify($tipoAviso, $resultados);
        $output->writeln('<info>Correos de notificación enviados (asíncronos).</info>');


        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'demo',
                null,
                InputOption::VALUE_OPTIONAL,
                'Ejecuta el comando en modo demostración simulando el día (15, 25 o 1)'
            );
    }
}
