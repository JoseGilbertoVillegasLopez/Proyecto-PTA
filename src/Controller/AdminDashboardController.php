<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard/{section}', name: 'app_admin_dashboard', defaults: ['section' => 'personal'])]
    public function index(
        string $section,
        ModuloAccesoResolver $moduloAccesoResolver,
    ): Response {
        $user = $this->getUser();

        // 1. Secciones solo admin
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            in_array($section, ['personal', 'departamentos', 'puestos'], true)
        ) {
            $section = 'pta';
        }

        // 2. Monitoreo — acceso controlado por la UI de módulos
        if (
            $section === 'monitoreo' &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'monitoreo'))
        ) {
            $section = 'pta';
        }

        // 3. Secciones solicitud_gastos — controladas por UI de módulos
        if (
            in_array($section, ['solicitud_gastos', 'solicitud_gastos_encargado'], true) &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'solicitud_gastos'))
        ) {
            $section = 'pta';
        }

        // 4. Fallback
        if ($section === null) {
            $section = $this->isGranted('ROLE_ADMIN') ? 'personal' : 'pta';
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => $section,
        ]);
    }
}
