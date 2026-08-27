<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Responsables;
use App\Entity\Personal;
use App\Entity\PtaResponsableAdicional;
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

            // ➕ RESPONSABLES ADICIONALES NUEVOS (0 a N filas agregadas en el frontend, máximo 5 en total)
            $nuevosResponsablesIds = $request->request->all('nuevos_responsables_adicionales');

            foreach ($nuevosResponsablesIds as $nuevoId) {
                if (!$nuevoId || count($encabezado->getResponsablesAdicionales()) >= 5) {
                    continue;
                }

                $personal = $entityManager->getRepository(Personal::class)->find($nuevoId);
                if ($personal) {
                    $nuevo = new PtaResponsableAdicional();
                    $nuevo->setPersonal($personal);
                    $encabezado->addResponsablesAdicionale($nuevo);
                    $entityManager->persist($nuevo);
                }
            }

            $entityManager->flush();

            // 🔁 REDIRECCIÓN LIMPIA AL SHOW (NO render directo)
            return $this->redirectToRoute('app_encabezado_show', [
                'id'   => $encabezado->getId(),
                'anio' => $encabezado->getAnioEjecucion(),
            ]);
        }

        // ============================
        // GET — RENDER CON TURBO
        // ============================
        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/encabezado/responsables_edit.html.twig', [
                'encabezado'  => $encabezado,
                'volver_path' => $this->generateUrl('app_encabezado_show', [
                    'id' => $encabezado->getId(),
                ]),
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'pta',
            'content_url' => $this->generateUrl('app_encabezado_responsables_edit', [
                'id' => $encabezado->getId(),
            ]),
        ]);
    }

    // ============================
    // ELIMINAR RESPONSABLE ADICIONAL
    // ============================
    #[Route('/{id}/responsables/adicional/{responsableAdicionalId}/eliminar', name: 'app_encabezado_responsable_adicional_eliminar', methods: ['POST'])]
    public function eliminarResponsableAdicional(
        Request $request,
        Encabezado $encabezado,
        int $responsableAdicionalId,
        EntityManagerInterface $entityManager
    ): Response {

        // 🔒 SOLO ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (
            !$this->isCsrfTokenValid(
                'responsables_' . $encabezado->getId(),
                $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Token CSRF inválido');
        }

        $adicional = $entityManager->getRepository(PtaResponsableAdicional::class)->find($responsableAdicionalId);

        // ✅ Solo se borra si pertenece al encabezado de la URL (nunca responsable/supervisor/aval)
        if ($adicional && $adicional->getEncabezado()?->getId() === $encabezado->getId()) {
            $entityManager->remove($adicional);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_encabezado_responsables_edit', [
            'id' => $encabezado->getId(),
        ]);
    }
}
