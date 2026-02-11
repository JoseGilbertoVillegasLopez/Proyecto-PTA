<?php

namespace App\Controller;

use App\Service\Pta\PtaAccessResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard/{section}', name: 'app_admin_dashboard', defaults: ['section' => 'personal'])]
    public function index(
        string $section,
        PtaAccessResolver $ptaAccessResolver
    ): Response {
        $user = $this->getUser();

        // Resolver acceso PTA
        $ptaAccess = $ptaAccessResolver->resolve($user);

        /* =====================================================
         * REGLAS DE SECCIONES
         * ===================================================== */

        // 1. Secciones SOLO ADMIN
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            in_array($section, ['personal', 'departamentos', 'puestos'], true)
        ) {
            $section = 'pta';
        }

        // 2. Monitoreo PTA → SOLO GLOBAL o JERARQUICO
        if (
            $section === 'monitoreo' &&
            !in_array($ptaAccess['scope'], ['GLOBAL', 'JERARQUICO'], true)
        ) {
            // fallback seguro
            $section = 'pta';
        }

        // 3. Fallback por rol
        if ($section === null) {
            $section = $this->isGranted('ROLE_ADMIN') ? 'personal' : 'pta';
        }

        return $this->render('dashboard/index.html.twig', [
            'section'   => $section,
            'ptaAccess' => $ptaAccess,
        ]);
    }
}
