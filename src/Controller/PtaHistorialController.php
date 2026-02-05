<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Service\Pta\PtaHistorialService;
use App\Service\Pta\PtaGraficaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PtaHistorialController extends AbstractController
{
    #[Route('/pta/historial/{id}', name: 'pta_historial', methods: ['GET'])]
    public function historial(
        Request $request,
        Encabezado $encabezado,
        PtaHistorialService $ptaHistorialService,
        PtaGraficaService $ptaGraficaService
    ): Response {

        $from = $request->query->get('from', 'show');

        // 🔥 params para volver exactamente al mismo lugar
        $anio     = $request->query->get('anio');
        $rootType = $request->query->get('root_type', 'GLOBAL');
        $rootId   = $request->query->get('root_id');

        if ($from === 'monitoreo') {
            $volverPath = $this->generateUrl('pta_monitoreo_index', array_filter([
                'anio'      => $anio,
                'root_type' => $rootType,
                'root_id'   => $rootId,
            ]));
        } else {
            $volverPath = $this->generateUrl('app_encabezado_show', [
                'id' => $encabezado->getId(),
            ]);
        }

        /* =====================================================
           HISTORIAL (UI / EVENTOS)
           ===================================================== */
        $historial = $ptaHistorialService->buildHistorial($encabezado);

        /* =====================================================
           GRÁFICAS (MISMAS QUE SHOW)
           ===================================================== */
        $graficas = $ptaGraficaService->build($encabezado);

        /* =====================================================
           UNIR GRÁFICA ↔ INDICADOR
           ===================================================== */
        foreach ($historial as &$item) {
            foreach ($graficas as $grafica) {
                // Se compara por nombre de indicador (misma fuente que show)
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

        /* =====================================================
           RENDER
           ===================================================== */
        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/historial/historial_page.html.twig', [
                'encabezado'  => $encabezado,
                'historial'   => $historial,
                'volver_path' => $volverPath,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'pta', // 🔥 CLAVE
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
