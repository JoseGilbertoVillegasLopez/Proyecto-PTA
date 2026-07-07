<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DepartamentoRepository;
use App\Repository\PuestoRepository;
use App\Repository\ReportePtaTrimestreRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pta/reporte-encargado')]
final class ReportePtaEncargadoController extends AbstractController
{
    #[Route('', name: 'app_reporte_pta_encargado_index', methods: ['GET'])]
    public function index(
        Request $request,
        ReportePtaTrimestreRepository $trimestreRepo,
        PuestoRepository $puestoRepo,
        DepartamentoRepository $departamentoRepo,
        ModuloAccesoResolver $resolver,
    ): Response {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof User || !$resolver->esEncargado($user, 'reportes_pta'))) {
            throw $this->createAccessDeniedException();
        }

        $anio          = $request->query->get('anio')          ? (int) $request->query->get('anio')          : null;
        $trimestre     = $request->query->get('trimestre')     ? (int) $request->query->get('trimestre')     : null;
        $puestoId      = $request->query->get('puesto')        ? (int) $request->query->get('puesto')        : null;
        $departamentoId = $request->query->get('departamento') ? (int) $request->query->get('departamento')  : null;

        $reportes   = $trimestreRepo->findEntregadosConFiltros($anio, $trimestre, $puestoId, $departamentoId);
        $anios      = $trimestreRepo->findAniosEntregados();
        $puestos    = $puestoRepo->findBy(['activo' => true], ['nombre' => 'ASC']);
        $departamentos = $departamentoRepo->findBy(['activo' => true], ['nombre' => 'ASC']);

        $vars = [
            'reportes'       => $reportes,
            'anios'          => $anios,
            'puestos'        => $puestos,
            'departamentos'  => $departamentos,
            'filtros'        => [
                'anio'          => $anio,
                'trimestre'     => $trimestre,
                'puesto'        => $puestoId,
                'departamento'  => $departamentoId,
            ],
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('reporte_pta/encargado_index.html.twig', $vars);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'reporte_pta_encargado',
            'content_url' => $this->generateUrl('app_reporte_pta_encargado_index', array_filter([
                'anio'         => $anio,
                'trimestre'    => $trimestre,
                'puesto'       => $puestoId,
                'departamento' => $departamentoId,
            ])),
        ]);
    }
}
