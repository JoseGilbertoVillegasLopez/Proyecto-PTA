<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Responsables;
use App\Entity\Personal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pta')]
class AdminEncabezadoResponsablesController extends AbstractController
{
   #[Route('/{id}/responsables', name: 'app_encabezado_responsables_edit', methods: ['GET', 'POST'])]
public function editResponsables(
    Request $request,
    Encabezado $encabezado,
    EntityManagerInterface $entityManager
): Response {
    // 🔒 SOLO ADMIN
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Asegurar entidad Responsables
    $responsables = $encabezado->getResponsables();
    if (!$responsables) {
        $responsables = new Responsables();
        $responsables->setEncabezado($encabezado);
        $entityManager->persist($responsables);
    }

    // ============================
    // POST — GUARDAR CAMBIOS
    // ============================
    if ($request->isMethod('POST')) {

        if (
            !$this->isCsrfTokenValid(
                'responsables_' . $encabezado->getId(),
                $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Token CSRF inválido');
        }

        $responsableId = $request->request->get('responsable_id');
        $supervisorId  = $request->request->get('supervisor_id');
        $avalId        = $request->request->get('aval_id');

        if ($responsableId) {
            $responsable = $entityManager->getRepository(Personal::class)->find($responsableId);
            if ($responsable) {
                $encabezado->setResponsable($responsable);
            }
        }

        if ($supervisorId) {
            $supervisor = $entityManager->getRepository(Personal::class)->find($supervisorId);
            if ($supervisor) {
                $responsables->setSupervisor($supervisor);
            }
        }

        if ($avalId) {
            $aval = $entityManager->getRepository(Personal::class)->find($avalId);
            if ($aval) {
                $responsables->setAval($aval);
            }
        }

        $entityManager->flush();

        // ✅ VOLVER AL SHOW (con filtros completos)
        return $this->render('pta/encabezado/show.html.twig', [
            'encabezado' => $encabezado,
            'filtros' => [
                'anio'         => $encabezado->getAnioEjecucion(),
                'departamento' => null,
                'puesto'       => null,
            ],
        ]);
    }

    // ============================
    // GET — MOSTRAR FORMULARIO
    // ============================
    return $this->render('pta/encabezado/responsables_edit.html.twig', [
        'encabezado' => $encabezado,
    ]);
}

}
