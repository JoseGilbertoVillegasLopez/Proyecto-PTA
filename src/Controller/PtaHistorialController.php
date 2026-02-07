<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Repository\EncabezadoRepository;
use App\Service\Pta\PtaAccessResolver;
use App\Service\Pta\PtaHistorialService;
use App\Service\Pta\PtaGraficaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PtaHistorialController extends AbstractController
{
    /* =====================================================
     * INDEX — HISTORIAL PTA
     * ===================================================== */
    #[Route('/pta/historial', name: 'pta_historial_index', methods: ['GET'])]
    public function index(
        Request $request,
        EncabezadoRepository $encabezadoRepository,
        PtaAccessResolver $ptaAccessResolver
    ): Response {

        /* =============================================
         * 1. AÑO (DEFAULT = ACTUAL)
         * ============================================= */
        $anioActual    = (int) date('Y');
        $anioEjecucion = $request->query->getInt('anio', $anioActual);

        /* =============================================
         * 2. USUARIO + ACCESO
         * ============================================= */
        /** @var \App\Entity\User $usuario */
        $usuario  = $this->getUser();
        $personal = $usuario->getPersonal();

        $access = $ptaAccessResolver->resolve($usuario);

        /* =============================================
         * 3. CONSULTA REPOSITORY
         * ============================================= */
        $data = $encabezadoRepository->findForHistorialIndex(
            $access,
            $anioEjecucion,
            $personal?->getId(),
            $personal?->getPuesto()?->getId()
        );

        /* =============================================
         * 4. AÑOS DISPONIBLES
         * ============================================= */
        $aniosFiltro = $encabezadoRepository->findAniosDisponibles(
            $access,
            $personal?->getId() ?? 0
        );

        /* =============================================
         * 5. RENDER
         * ============================================= */
        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/historial/index_page.html.twig', [
                'ptaPropio'        => $data['propio'],
                'ptaPuesto'        => $data['puesto'],
                'anioSeleccionado' => $anioEjecucion,
                'aniosFiltro'      => $aniosFiltro,
                'access'           => $access,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'pta',
            'content_url' => $this->generateUrl('pta_historial_index', [
                'anio' => $anioEjecucion,
            ]),
        ]);
    }

    /* =====================================================
     * SHOW — HISTORIAL PTA (YA EXISTENTE)
     * ===================================================== */
    #[Route('/pta/historial/{id}', name: 'pta_historial', methods: ['GET'])]
    public function historial(
        Request $request,
        Encabezado $encabezado,
        PtaHistorialService $ptaHistorialService,
        PtaGraficaService $ptaGraficaService
    ): Response {

        $from = $request->query->get('from', 'show');

        $anio     = $request->query->get('anio');
        $rootType = $request->query->get('root_type', 'GLOBAL');
        $rootId   = $request->query->get('root_id');

        if ($from === 'monitoreo') {
            $volverPath = $this->generateUrl('pta_monitoreo_index', array_filter([
                'anio'      => $anio,
                'root_type' => $rootType,
                'root_id'   => $rootId,
            ]));
        } elseif ($from === 'historial_index') {
            $volverPath = $this->generateUrl('pta_historial_index', array_filter([
                'anio' => $anio,
            ]));
        } else {
            $volverPath = $this->generateUrl('app_encabezado_show', [
                'id' => $encabezado->getId(),
            ]);
        }

        $historial = $ptaHistorialService->buildHistorial($encabezado);
        $graficas  = $ptaGraficaService->build($encabezado);

        foreach ($historial as &$item) {
            foreach ($graficas as $grafica) {
                if (
                    isset($item['info']['indicador']) &&
                    $grafica['indicador'] === $item['info']['indicador']
                ) {
                    $item['grafica'] = $grafica;
                    break;
                }
            }
        }
        unset($item);

        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/historial/historial_page.html.twig', [
                'encabezado'  => $encabezado,
                'historial'   => $historial,
                'volver_path' => $volverPath,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'pta',
            'content_url' => $this->generateUrl('pta_historial', array_filter([
                'id'        => $encabezado->getId(),
                'anio'      => $anio,
                'from'      => $from,
                'root_type' => $rootType,
                'root_id'   => $rootId,
            ])),
        ]);
    }
}
