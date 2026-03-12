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
use App\Repository\ReportePtaTrimestreRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Pta\ConstructorVistaReportePtaIndexService;
use App\Service\Pta\ReportePtaExportDataBuilderService;
use App\Service\Pta\ReportePtaWordExportService;

#[Route('/pta/reporte')]
class ReportePtaController extends AbstractController
{
    #[Route('/{id}', name: 'app_reporte_pta_index', methods: ['GET'])]
    public function index(
        Request $request,
        Encabezado $encabezado,
        ConstructorVistaReportePtaIndexService $constructorIndex,
    ): Response {
        $isTurbo = $request->headers->has('Turbo-Frame');

        $datos = $constructorIndex->build($encabezado);

        if ($isTurbo) {
    return $this->render('reporte_pta/index.html.twig', [
        'datos' => $datos,
        'volver_path' => $this->generateUrl('app_encabezado_index', [
            'anio' => $encabezado->getAnioEjecucion(),
        ]),
    ]);
}

        return $this->render('dashboard/index.html.twig', [
            'section' => 'pta',
            'content_url' => $this->generateUrl('app_reporte_pta_index', [
                'id' => $encabezado->getId(),
            ]),
        ]);
    }

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
                $datosCalculados = $constructorService->build($encabezado, $trimestre);

                $guardarService->guardar(
                    $encabezado,
                    $trimestre,
                    $request->request->all(),
                    $request->files->all(),
                    $datosCalculados
                );

                $this->addFlash('success', 'Reporte guardado correctamente.');

                return $this->redirectToRoute('app_reporte_pta_show', [
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
                'volver_path' => $this->generateUrl('app_reporte_pta_index', [
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

                return $this->redirectToRoute('app_reporte_pta_show', [
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
                'volver_path' => $this->generateUrl('app_reporte_pta_index', [
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
            'anio'       => $encabezado->getAnioEjecucion(),
            'trimestre'  => $trimestre,
        ]);

        if (!$reporteTrimestre) {
            throw $this->createNotFoundException('No existe el reporte para este trimestre.');
        }

        // ===============================
        // 3) Datos para la vista
        // ===============================
        $datos = $constructorShow->build($encabezado, $trimestre);

        $csrfToken = $csrfTokenManager
            ->getToken('reporte_pta_entregar')
            ->getValue();

        if ($isTurbo) {
            return $this->render('reporte_pta/show.html.twig', [
                'datos' => $datos,
                'reporteTrimestre' => $reporteTrimestre,
                'volver_path' => $this->generateUrl('app_reporte_pta_index', [
                    'id' => $encabezado->getId(),
                ]),
                'csrf_token_entregar' => $csrfToken,
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

    
    #[Route('/{id}/{trimestre}/export/word', name: 'app_reporte_pta_export_word', methods: ['GET'])]
public function exportWord(
    Encabezado $encabezado,
    int $trimestre,
    ReportePtaWordExportService $wordExportService,
): Response {

    // ===============================
    // 1) Validar trimestre
    // ===============================
    if ($trimestre < 1 || $trimestre > 4) {
        throw $this->createNotFoundException('Trimestre inválido.');
    }

    // ===============================
    // 2) Exportar Word real
    // ===============================
    return $wordExportService->exportar($encabezado, $trimestre);
}


#[Route('/{id}/{trimestre}/export/pdf', name: 'app_reporte_pta_export_pdf', methods: ['GET'])]
public function exportPdf(
    Encabezado $encabezado,
    int $trimestre,
    ReportePtaExportDataBuilderService $builder,
): Response {

    // ===============================
    // 1) Validar trimestre
    // ===============================
    if ($trimestre < 1 || $trimestre > 4) {
        throw $this->createNotFoundException('Trimestre inválido.');
    }

    // ===============================
    // 2) Construir datos exportables
    // ===============================
    $data = $builder->build($encabezado, $trimestre);

    // ===============================
    // 3) RESPUESTA TEMPORAL DE PRUEBA
    // -------------------------------------------------
    // Aquí después conectaremos el servicio real PDF.
    // Por ahora sirve para validar que la ruta y data
    // ya funcionan.
    // ===============================
    return $this->json([
        'ok' => true,
        'tipo' => 'pdf',
        'mensaje' => 'Base de exportación PDF lista.',
        'reporte' => $data,
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
            'anio'       => $encabezado->getAnioEjecucion(),
            'trimestre'  => $trimestre,
        ]);

        if (!$reporteTrimestre) {
            throw $this->createNotFoundException('No existe el reporte para este trimestre.');
        }

        // ===============================
        // 4) Marcar como entregado
        // ===============================
        $reporteTrimestre->setEstado(true);

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