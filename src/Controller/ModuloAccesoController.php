<?php

namespace App\Controller;

use App\Entity\ModuloAcceso;
use App\Repository\ModuloSistemaRepository;
use App\Repository\PuestoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/modulos-acceso')]
#[IsGranted('ROLE_ADMIN')]
final class ModuloAccesoController extends AbstractController
{
    #[Route('', name: 'app_admin_modulo_acceso_index', methods: ['GET'])]
    public function index(Request $request, ModuloSistemaRepository $moduloRepo): Response
    {
        $modulos = $moduloRepo->findAll();

        $resumen = [];
        foreach ($modulos as $modulo) {
            $encargados = 0;
            $conAcceso  = 0;
            foreach ($modulo->getAccesos() as $acceso) {
                if ($acceso->getTipo() === 'encargado') {
                    $encargados++;
                } else {
                    $conAcceso++;
                }
            }
            $resumen[] = [
                'modulo'     => $modulo,
                'encargados' => $encargados,
                'con_acceso' => $conAcceso,
            ];
        }

        if ($request->headers->get('Turbo-Frame')) {
            return $this->render('modulo_acceso/index.html.twig', [
                'resumen' => $resumen,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'modulos_acceso',
            'content_url' => $this->generateUrl('app_admin_modulo_acceso_index'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_modulo_acceso_edit', methods: ['GET'])]
    public function edit(
        int $id,
        Request $request,
        ModuloSistemaRepository $moduloRepo,
        PuestoRepository $puestoRepo,
    ): Response {
        $modulo = $moduloRepo->find($id);
        if (!$modulo) {
            throw $this->createNotFoundException();
        }

        $todosLosPuestos = $puestoRepo->findBy(['activo' => true], ['nombre' => 'ASC']);

        $encargadosIds  = [];
        $conAccesoIds   = [];

        foreach ($modulo->getAccesos() as $acceso) {
            if ($acceso->getTipo() === 'encargado') {
                $encargadosIds[] = $acceso->getPuesto()->getId();
            } else {
                $conAccesoIds[] = $acceso->getPuesto()->getId();
            }
        }

        $encargados = [];
        $conAcceso  = [];

        foreach ($todosLosPuestos as $puesto) {
            $pid = $puesto->getId();
            if (in_array($pid, $encargadosIds, true)) { $encargados[] = $puesto; }
            if (in_array($pid, $conAccesoIds,  true)) { $conAcceso[]  = $puesto; }
        }

        $vars = [
            'modulo'          => $modulo,
            'encargados'      => $encargados,
            'con_acceso'      => $conAcceso,
            'todos_puestos'   => $todosLosPuestos,
            'encargados_ids'  => $encargadosIds,
            'con_acceso_ids'  => $conAccesoIds,
        ];

        if ($request->headers->get('Turbo-Frame')) {
            return $this->render('modulo_acceso/edit.html.twig', $vars);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'modulos_acceso',
            'content_url' => $this->generateUrl('app_admin_modulo_acceso_edit', ['id' => $id]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_modulo_acceso_save', methods: ['POST'])]
    public function save(
        int $id,
        Request $request,
        ModuloSistemaRepository $moduloRepo,
        PuestoRepository $puestoRepo,
        EntityManagerInterface $em,
    ): Response {
        $modulo = $moduloRepo->find($id);
        if (!$modulo) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('modulo_acceso_' . $id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        // Eliminar todas las asignaciones actuales
        foreach ($modulo->getAccesos() as $acceso) {
            $em->remove($acceso);
        }
        $em->flush();

        // Reinsertar encargados
        $encargadosIds = array_filter(array_map('intval', $request->request->all('encargados') ?: []));
        foreach ($encargadosIds as $puestoId) {
            $puesto = $puestoRepo->find($puestoId);
            if (!$puesto) continue;

            $acceso = new ModuloAcceso();
            $acceso->setModulo($modulo);
            $acceso->setPuesto($puesto);
            $acceso->setTipo('encargado');
            $em->persist($acceso);
        }

        // Reinsertar con-acceso
        $conAccesoIds = array_filter(array_map('intval', $request->request->all('con_acceso') ?: []));
        foreach ($conAccesoIds as $puestoId) {
            $puesto = $puestoRepo->find($puestoId);
            if (!$puesto) continue;

            $acceso = new ModuloAcceso();
            $acceso->setModulo($modulo);
            $acceso->setPuesto($puesto);
            $acceso->setTipo('acceso');
            $em->persist($acceso);
        }

        $em->flush();

        $this->addFlash('success', 'Accesos del módulo "' . $modulo->getLabel() . '" actualizados.');

        return $this->redirectToRoute('app_admin_modulo_acceso_index');
    }
}
