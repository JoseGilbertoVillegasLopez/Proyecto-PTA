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

        // Sección segura de fallback: siempre accesible para cualquier usuario autenticado
        $fallback = 'indicadores_basicos';

        // 1. Secciones solo admin
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            in_array($section, ['departamentos', 'puestos'], true)
        ) {
            $section = $fallback;
        }

        // 1b. Personal — admin o con acceso asignado
        if (
            $section === 'personal' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'personal'))
        ) {
            $section = $fallback;
        }

        // 2. PTA e Historial PTA — acceso o encargado del módulo reportes_pta
        if (
            in_array($section, ['pta', 'historial_pta'], true) &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || (!$moduloAccesoResolver->tieneAcceso($user, 'reportes_pta') && !$moduloAccesoResolver->esEncargado($user, 'reportes_pta')))
        ) {
            $section = $fallback;
        }

        // 2b. Vista encargado de reportes PTA
        if (
            $section === 'reporte_pta_encargado' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->esEncargado($user, 'reportes_pta'))
        ) {
            $section = $fallback;
        }

        // 3. Monitoreo — acceso controlado por la UI de módulos
        if (
            $section === 'monitoreo' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'monitoreo'))
        ) {
            $section = $fallback;
        }

        // 4. Solicitud de gastos (usuario)
        if (
            $section === 'solicitud_gastos' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'solicitud_gastos'))
        ) {
            $section = $fallback;
        }

        // 4b. Solicitud de gastos (encargado)
        if (
            $section === 'solicitud_gastos_encargado' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->esEncargado($user, 'solicitud_gastos'))
        ) {
            $section = $fallback;
        }

        // 5. Reporte indicadores (usuario)
        if (
            $section === 'reporte_indicadores' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores'))
        ) {
            $section = $fallback;
        }

        // 5b. Reporte indicadores (encargado)
        if (
            $section === 'reporte_indicadores_encargado' &&
            !$this->isGranted('ROLE_ADMIN') &&
            (!$user instanceof User || !$moduloAccesoResolver->esEncargado($user, 'reporte_indicadores'))
        ) {
            $section = $fallback;
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => $section,
        ]);
    }
}
