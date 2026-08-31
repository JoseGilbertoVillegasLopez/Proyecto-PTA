<?php

namespace App\Controller;

use App\Service\Manual\ManualCatalogo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manual')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ManualController extends AbstractController
{
    #[Route('', name: 'app_manual_index', methods: ['GET'])]
    public function index(ManualCatalogo $catalogo): Response
    {
        return $this->render('manual/index.html.twig', [
            'modulos' => $catalogo->getModulosVisibles(),
        ]);
    }

    #[Route('/{slug}', name: 'app_manual_modulo', methods: ['GET'])]
    public function modulo(string $slug, ManualCatalogo $catalogo): Response
    {
        $modulo = $catalogo->getModulo($slug);
        if ($modulo === null) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('manual/modulo.html.twig', [
            'modulo' => $modulo,
        ]);
    }
}
