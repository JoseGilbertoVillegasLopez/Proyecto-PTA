<?php

namespace App\Controller;

use App\Entity\Nombramiento;
use App\Entity\Personal;
use App\Repository\NombramientoRepository;
use App\Repository\PuestoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/nombramiento')]
final class NombramientoController extends AbstractController
{
    #[Route('/historial', name: 'app_nombramiento_historial_index', methods: ['GET'])]
    public function historial(
        Request $request,
        NombramientoRepository $nombramientoRepository,
        PuestoRepository $puestoRepository,
    ): Response {
        $filtros = [
            'departamento' => $this->getOptionalPositiveInt($request, 'departamento'),
            'puesto' => $this->getOptionalPositiveInt($request, 'puesto'),
            'estado' => $this->getOptionalEstado($request),
        ];

        $departamentos = $puestoRepository->findDepartamentosJerarquicos();
        $todosLosPuestos = $puestoRepository->findAllOrdenados();
        $puestosPorDepartamento = [];

        foreach ($departamentos as $departamento) {
            $puestosPorDepartamento[$departamento->getId()] =
                $puestoRepository->findSubordinadosRecursivosOrdenados($departamento);
        }

        $puestosDepartamento = $filtros['departamento']
            ? ($puestosPorDepartamento[$filtros['departamento']] ?? [])
            : $todosLosPuestos;

        $puestosDepartamentoIds = array_map(
            static fn ($puesto): int => $puesto->getId(),
            $puestosDepartamento
        );

        $puestosFiltroDepartamentoIds = $puestosDepartamentoIds;

        if ($filtros['departamento']) {
            $puestosFiltroDepartamentoIds[] = $filtros['departamento'];
        }

        if (
            $filtros['puesto']
            && $filtros['departamento']
            && !in_array($filtros['puesto'], $puestosDepartamentoIds, true)
        ) {
            $filtros['puesto'] = null;
        }

        $nombramientos = $nombramientoRepository->findForHistorial(
            $puestosFiltroDepartamentoIds,
            $filtros['puesto'],
            $filtros['estado']
        );

        $activos = count(array_filter(
            $nombramientos,
            static fn (Nombramiento $nombramiento): bool => $nombramiento->isActivo()
        ));

        $viewData = [
            'nombramientos' => $nombramientos,
            'departamentos' => $departamentos,
            'puestos' => $puestosDepartamento,
            'puestos_por_departamento' => $this->buildPuestosPorDepartamentoData(
                ['' => $todosLosPuestos] + $puestosPorDepartamento
            ),
            'filtros' => $filtros,
            'resumen' => [
                'total' => count($nombramientos),
                'activos' => $activos,
                'inactivos' => count($nombramientos) - $activos,
            ],
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('admin/nombramiento/index.html.twig', $viewData);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'personal',
            'content_url' => $this->generateUrl(
                'app_nombramiento_historial_index',
                $request->query->all()
            ),
        ]);
    }

    #[Route('/historial/personal/{id}', name: 'app_nombramiento_historial_personal', methods: ['GET'])]
    public function historialPersonal(Request $request, Personal $personal): Response
    {
        $origen = $request->query->get('origen', 'historial');
        $filtrosHistorial = array_filter([
            'departamento' => $request->query->get('departamento'),
            'puesto' => $request->query->get('puesto'),
            'estado' => $request->query->get('estado'),
        ], static fn ($value): bool => $value !== null && $value !== '');

        $volverPath = match ($origen) {
            'show' => $this->generateUrl('app_personal_show', [
                'id' => $personal->getId(),
            ]),
            'edit' => $this->generateUrl('app_personal_edit', [
                'id' => $personal->getId(),
            ]),
            default => $this->generateUrl(
                'app_nombramiento_historial_index',
                $filtrosHistorial
            ),
        };

        $viewData = [
            'personal' => $personal,
            'volver_path' => $volverPath,
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render(
                'admin/nombramiento/historial_nombramiento.html.twig',
                $viewData
            );
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'personal',
            'content_url' => $this->generateUrl(
                'app_nombramiento_historial_personal',
                array_merge(['id' => $personal->getId()], $request->query->all())
            ),
        ]);
    }

    private function getOptionalPositiveInt(Request $request, string $key): ?int
    {
        $value = $request->query->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);

        return $value !== false && $value > 0 ? $value : null;
    }

    private function getOptionalEstado(Request $request): ?bool
    {
        return match ($request->query->get('estado')) {
            'activo' => true,
            'inactivo' => false,
            default => null,
        };
    }

    private function buildPuestosPorDepartamentoData(array $puestosPorDepartamento): array
    {
        $data = [];

        foreach ($puestosPorDepartamento as $departamentoId => $puestos) {
            $data[$departamentoId] = array_map(
                static fn ($puesto): array => [
                    'id' => $puesto->getId(),
                    'nombre' => $puesto->getNombre(),
                ],
                $puestos
            );
        }

        return $data;
    }

    #[Route('/subir/{id}', name: 'app_nombramiento_subir', methods: ['POST'])]
    public function subir(
        Request $request,
        Personal $personal,
        EntityManagerInterface $entityManager
    ): Response {

        if (!$this->isCsrfTokenValid(
            'subir_nombramiento',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $pdf = $request->files->get('pdf');

        if (!$pdf) {

            $this->addFlash('error', 'Debes seleccionar un PDF.');

            return $this->redirectToRoute('app_personal_edit', [
                'id' => $personal->getId()
            ]);
        }

        $nombreArchivo = uniqid() . '.pdf';

        try {

            $pdf->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/nombramientos',
                $nombreArchivo
            );

        } catch (FileException $e) {

            $this->addFlash('error', 'Error al subir PDF.');

            return $this->redirectToRoute('app_personal_edit', [
                'id' => $personal->getId()
            ]);
        }

        $nombramiento = new Nombramiento();

        $nombramiento->setArchivo($nombreArchivo);

        $nombramiento->setNombreOriginal(
            $pdf->getClientOriginalName()
        );

        $tipoNombramiento = $request->request->get('tipo');

        $nombramiento->setTipo($tipoNombramiento);

        $nombramiento->setActivo(true);

        $nombramiento->setFechaSubida(new \DateTimeImmutable('today'));

        $nombramiento->setFechaDesactivacion(null);

        $nombramiento->setPersonal($personal);

        $entityManager->persist($nombramiento);

        $entityManager->flush();

        return $this->redirectToRoute('app_personal_edit', [
            'id' => $personal->getId()
        ]);
    }

    #[Route('/desactivar/{id}', name: 'app_nombramiento_desactivar', methods: ['POST'])]
    public function desactivar(
        Request $request,
        Nombramiento $nombramiento,
        EntityManagerInterface $entityManager
    ): Response {

        if (!$this->isCsrfTokenValid(
            'desactivar' . $nombramiento->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $nombramiento->setActivo(false);

        $nombramiento->setFechaDesactivacion(new \DateTimeImmutable('today'));

        $entityManager->flush();

        return $this->redirectToRoute('app_personal_edit', [
            'id' => $nombramiento->getPersonal()->getId()
        ]);
    }


}
