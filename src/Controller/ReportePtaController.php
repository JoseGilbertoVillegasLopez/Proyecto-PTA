<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Service\Pta\ConstructorVistaReportePtaService;
use App\Service\Pta\ConstructorVistaReportePtaShowService;
use App\Service\Pta\GuardarReportePtaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Service\Pta\ConstructorVistaReportePtaEditService;
use App\Service\Pta\PtaTrimestreCalculoService;
use App\Repository\ReportePtaTrimestreRepository;
use Doctrine\ORM\EntityManagerInterface;

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
                // DEBUG TEMPORAL
// dd([
//     'request' => $request->request->all(),
//     'files' => $request->files->all(),
// ]);
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
                throw $e;
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


    #[Route('/{id}/{trimestre}/edit', name: 'app_reporte_pta_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    Encabezado $encabezado,
    int $trimestre,
    ConstructorVistaReportePtaEditService $constructorEdit,
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
    // 2) POST → Actualizar
    // ===============================
    if ($request->isMethod('POST')) {

        if (!$this->isCsrfTokenValid(
            'reporte_pta',
            (string) $request->request->get('_token')
        )) {
            return new Response('Token CSRF inválido.', 403);
        }

        try {

            $datosCalculados = $constructorEdit->build($encabezado, $trimestre);

            $guardarService->actualizar(
                $encabezado,
                $trimestre,
                $request->request->all(),
                $request->files->all(),
                $datosCalculados
            );

            $this->addFlash('success', 'Reporte actualizado correctamente.');

            return $this->redirectToRoute('app_reporte_pta_edit', [
                'id' => $encabezado->getId(),
                'trimestre' => $trimestre,
            ]);

        } catch (\DomainException $e) {

            $this->addFlash('error', $e->getMessage());

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    // ===============================
    // 3) GET → Render
    // ===============================
    $datos = $constructorEdit->build($encabezado, $trimestre);

    $csrfToken = $csrfTokenManager
        ->getToken('reporte_pta')
        ->getValue();

    if ($isTurbo) {
        return $this->render('reporte_pta/edit.html.twig', [
            'datos' => $datos,
            'volver_path' => $this->generateUrl('app_encabezado_show', [
                'id' => $encabezado->getId(),
            ]),
            'csrf_token' => $csrfToken,
        ]);
    }

    return $this->render('dashboard/index.html.twig', [
        'section' => 'pta',
        'content_url' => $this->generateUrl('app_reporte_pta_edit', [
            'id' => $encabezado->getId(),
            'trimestre' => $trimestre,
        ]),
    ]);
}



#[Route('/{id}/{trimestre}/show', name: 'app_reporte_pta_show', methods: ['GET'])]
public function show(
    Request $request,
    Encabezado $encabezado,
    int $trimestre,
    ConstructorVistaReportePtaShowService $constructorShow,
    ReportePtaTrimestreRepository $trimestreRepo,
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
    // 2) Verificar que exista el reporte
    // ===============================
    $reporteTrimestre = $trimestreRepo->findOneBy([
        'encabezado' => $encabezado,
        'trimestre'  => $trimestre
    ]);

    if (!$reporteTrimestre) {
        throw $this->createNotFoundException('No existe el reporte para este trimestre.');
    }

    // ===============================
    // 3) Datos para la vista (reutilizamos el constructor EDIT)
    // ===============================
    $datos = $constructorShow->build($encabezado, $trimestre);

    $csrfToken = $csrfTokenManager
        ->getToken('reporte_pta_entregar')
        ->getValue();

    if ($isTurbo) {
        return $this->render('reporte_pta/show.html.twig', [
            'datos' => $datos,
            'reporteTrimestre' => $reporteTrimestre,
            'volver_path' => $this->generateUrl('app_encabezado_show', [
                'id' => $encabezado->getId(),
            ]),
            'csrf_token_entregar' => $csrfToken
        ]);
    }

    return $this->render('dashboard/index.html.twig', [
        'section' => 'pta',
        'reporteTrimestre' => $reporteTrimestre,
        'content_url' => $this->generateUrl('app_reporte_pta_show', [
            'id' => $encabezado->getId(),
            'trimestre' => $trimestre,
        ]),
    ]);
}


#[Route('/{id}/{trimestre}/entregar', name: 'app_reporte_pta_entregar', methods: ['POST'])]
public function entregar(
    Request $request,
    Encabezado $encabezado,
    int $trimestre,
    ReportePtaTrimestreRepository $trimestreRepo,
    EntityManagerInterface $em,
): Response {

    // ===============================
    // 1) Validar trimestre
    // ===============================
    if ($trimestre < 1 || $trimestre > 4) {
        throw $this->createNotFoundException('Trimestre inválido.');
    }

    // ===============================
    // 2) CSRF
    // ===============================
    if (!$this->isCsrfTokenValid(
        'reporte_pta_entregar',
        (string) $request->request->get('_token')
    )) {
        return new Response('Token CSRF inválido.', 403);
    }

    // ===============================
    // 3) Buscar reporte trimestre
    // ===============================
    $reporteTrimestre = $trimestreRepo->findOneBy([
        'encabezado' => $encabezado,
        'trimestre'  => $trimestre
    ]);

    if (!$reporteTrimestre) {
        throw $this->createNotFoundException('No existe el reporte para este trimestre.');
    }

    // ===============================
    // 4) Marcar como entregado
    // ===============================
    $reporteTrimestre->setEstado(true);

    /**
     * 👇 IMPORTANTÍSIMO MI LOCO:
     * Aquí pon el setter REAL de tu entidad para guardar fecha/hora entrega.
     * Ejemplos comunes:
     * - setEntregadoFecha(new \DateTime())
     * - setFechaEntrega(new \DateTime())
     * - setEntregadoEn(new \DateTime())
     *
     * Si NO existe todavía, hay que agregar el campo en la entidad + migración.
     */
    if (method_exists($reporteTrimestre, 'setFechaEntrega')) {
        $reporteTrimestre->setFechaEntrega(new \DateTime());
    } elseif (method_exists($reporteTrimestre, 'setEntregadoFecha')) {
        $reporteTrimestre->setEntregadoFecha(new \DateTime());
    } elseif (method_exists($reporteTrimestre, 'setEntregadoEn')) {
        $reporteTrimestre->setEntregadoEn(new \DateTime());
    }

    $em->flush();

    $this->addFlash('success', 'Reporte entregado correctamente.');

    return $this->redirectToRoute('app_reporte_pta_show', [
        'id' => $encabezado->getId(),
        'trimestre' => $trimestre,
    ]);
}


}