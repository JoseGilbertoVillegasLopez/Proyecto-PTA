<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard/{section}', name: 'app_admin_dashboard', defaults: ['section' => 'personal'])]
public function index(string $section): Response
{
    // Si NO es admin y pide secciones prohibidas
    if (
        !$this->isGranted('ROLE_ADMIN')
        && in_array($section, ['personal', 'departamentos', 'puestos'], true)
    ) {
        // Fuerza PTA
        $section = 'pta';
    }

    // Si viene vacío o null
    if ($section === null) {
        $section = $this->isGranted('ROLE_ADMIN') ? 'personal' : 'pta';
    }

    return $this->render('dashboard/index.html.twig', [
        'section' => $section,
    ]);
}
    
    
}
