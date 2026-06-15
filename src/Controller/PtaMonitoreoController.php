<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EncabezadoRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use App\Service\Pta\PtaMonitoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pta/monitoreo')]
class PtaMonitoreoController extends AbstractController
{
    #[Route('', name: 'pta_monitoreo_index', methods: ['GET'])]
    public function index(
        Request $request,
        EncabezadoRepository $encabezadoRepository,
        PtaMonitoringService $ptaMonitoringService,
        ModuloAccesoResolver $moduloAccesoResolver,
    ): Response {
        /** @var User $usuario */
        $usuario = $this->getUser();

        if (!$usuario instanceof User || !$moduloAccesoResolver->tieneAcceso($usuario, 'monitoreo')) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de monitoreo.');
        }

        $anioActual = (int) date('Y');
        $mesActual  = (int) date('n');
        $anio       = $request->query->getInt('anio', $anioActual);

        $access = [
            'scope'                  => 'GLOBAL',
            'puestos_visibles'       => [],
            'departamentos_visibles' => [],
            'filters'                => ['anio' => true, 'puesto' => true, 'departamento' => true],
        ];

        $filters = ['anio' => $anio];

        if ($request->query->has('puesto')) {
            $filters['puesto'] = $request->query->get('puesto');
        }

        if ($request->query->has('departamento')) {
            $filters['departamento'] = $request->query->get('departamento');
        }

        $ptas = $encabezadoRepository->findPtasForMonitoring($access, $anio);

        $contexto = [
            'root_type' => $request->query->get('root_type', 'GLOBAL'),
            'root_id'   => $request->query->get('root_id'),
        ];

        $resultado = $ptaMonitoringService->monitor($ptas, $anio, $mesActual, $contexto);

        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/encabezado/monitoreo_page.html.twig', [
                'resultado' => $resultado,
                'anio'      => $anio,
                'access'    => $access,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'monitoreo',
            'content_url' => $this->generateUrl('pta_monitoreo_index', $request->query->all()),
        ]);
    }
}
