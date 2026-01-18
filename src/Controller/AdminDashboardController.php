<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard/{section}', name: 'app_admin_dashboard', defaults: ['section' => 'personal'])]
public function index(string $section): Response
{
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => $section,
    ]);
}
    
    
}
