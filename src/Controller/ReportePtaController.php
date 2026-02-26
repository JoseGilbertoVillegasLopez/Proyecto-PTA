<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Service\Pta\ConstructorVistaReportePtaService;
use App\Service\Pta\GuardarReportePtaService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/pta/reporte')]
class ReportePtaController extends AbstractController
{
    #[Route('/{id}/{trimestre}/new', name: 'app_reporte_pta_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Encabezado $encabezado,
        int $trimestre,
        ConstructorVistaReportePtaService $constructorService,
        GuardarReportePtaService $guardarService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {

        // ===============================
        // 1) Validar trimestre
        // ===============================
        if ($trimestre < 1 || $trimestre > 4) {
            throw $this->createNotFoundException('Trimestre inválido.');
        }

        $isTurbo = $request->headers->has('Turbo-Frame');

        // ===============================
        // 2) POST → Guardar
        // ===============================
        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'reporte_pta',
                (string) $request->request->get('_token')
            )) {
                return new Response('Token CSRF inválido.', 403);
            }

            try {

                // 🔥 reconstruimos datos matemáticos
                $datosCalculados = $constructorService->build($encabezado, $trimestre);

                $guardarService->guardar(
                    $encabezado,
                    $trimestre,
                    $request->request->all(),
                    $request->files->all(),
                    $datosCalculados
                );

                $this->addFlash('success', 'Reporte guardado correctamente.');

                return $this->redirectToRoute('app_reporte_pta_new', [
                    'id' => $encabezado->getId(),
                    'trimestre' => $trimestre,
                ]);

            } catch (\DomainException $e) {

                $this->addFlash('error', $e->getMessage());

            } catch (\Throwable $e) {

                $this->addFlash('error', 'Error interno al guardar el reporte.');
            }
        }

        // ===============================
        // 3) GET → Render
        // ===============================
        $datos = $constructorService->build($encabezado, $trimestre);

        $csrfToken = $csrfTokenManager
            ->getToken('reporte_pta')
            ->getValue();

        if ($isTurbo) {
            return $this->render('reporte_pta/new.html.twig', [
                'datos' => $datos,
                'volver_path' => $this->generateUrl('app_encabezado_show', [
                    'id' => $encabezado->getId(),
                ]),
                'csrf_token' => $csrfToken,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'pta',
            'content_url' => $this->generateUrl('app_reporte_pta_new', [
                'id' => $encabezado->getId(),
                'trimestre' => $trimestre,
            ]),
        ]);
    }
}