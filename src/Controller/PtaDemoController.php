<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

final class PtaDemoController extends AbstractController
{
    #[Route('/admin/pta/demo/notificaciones/{dia}', name: 'admin_pta_demo_notificaciones')]
    public function ejecutarDemo(int $dia): Response
    {
        if (!in_array($dia, [1, 15, 25], true)) {
            throw $this->createNotFoundException('Día no válido para demostración');
        }

        $process = new Process([
            'php',
            'bin/console',
            'pta:notificar-acciones-sin-avance',
            '--demo=' . $dia,
        ]);

        $process->run();

        return $this->render('admin/pta/demo_resultado.html.twig', [
            'dia' => $dia,
            'output' => $process->getOutput(),
        ]);
    }
}
