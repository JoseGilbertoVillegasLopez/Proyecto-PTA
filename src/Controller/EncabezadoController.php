<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Form\EncabezadoType;
use App\Repository\EncabezadoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/encabezado')]
final class EncabezadoController extends AbstractController
{
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
    public function index(EncabezadoRepository $encabezadoRepository): Response
    {
        return $this->render('admin/encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EncabezadoRepository $encabezadoRepository): Response
    {
        $encabezado = new Encabezado();

        // Inicializar el subobjeto responsables (OneToOne)
        $responsables = new \App\Entity\Responsables();
        $encabezado->setResponsables($responsables);

        $usuario = $this->getUser();
        if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
            $encabezado->setResponsable($usuario->getPersonal());
        }




        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $responsables = $encabezado->getResponsables();

$responsables = $encabezado->getResponsables();

if ($responsables) {
    $data = $request->request->all('encabezado');

    $supervisorId = $data['responsables']['supervisor'] ?? null;
    $avalId       = $data['responsables']['aval'] ?? null;

    if ($supervisorId) {
        $supervisor = $entityManager->getRepository(Personal::class)->find($supervisorId);
        $responsables->setSupervisor($supervisor);
    }

    if ($avalId) {
        $aval = $entityManager->getRepository(Personal::class)->find($avalId);
        $responsables->setAval($aval);
    }
}



            $encabezado->setFechaCreacion(new \DateTime());
            $encabezado->setStatus(true);

            // 🔥 asegurar relación padre → hijos
            foreach ($encabezado->getIndicadores() as $indicador) {
                $indicador->setEncabezado($encabezado);
            }

            foreach ($encabezado->getAcciones() as $accion) {
                $accion->setEncabezado($encabezado);
            }

            $usuario = $this->getUser();

            if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
                $encabezado->setResponsable($usuario->getPersonal());
            }


            $entityManager->persist($encabezado);
            $entityManager->flush();

            return $this->render('admin/encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
            ]);

        }
        

        return $this->render('admin/encabezado/new.html.twig', [
            'encabezado' => $encabezado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
    public function show(Encabezado $encabezado): Response
    {
        return $this->render('admin/encabezado/show.html.twig', [
            'encabezado' => $encabezado,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager, EncabezadoRepository $encabezadoRepository): Response
    {
        if ($encabezado->getResponsables() === null) {
            $encabezado->setResponsables(new \App\Entity\Responsables());
        }

        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔥 asegurar relación padre → hijos
            foreach ($encabezado->getIndicadores() as $indicador) {
                $indicador->setEncabezado($encabezado);
            }

            foreach ($encabezado->getAcciones() as $accion) {
                $accion->setEncabezado($encabezado);
            }
            $entityManager->persist($encabezado);
            $entityManager->flush();


            return $this->render('admin/encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
        ]);
        }

        return $this->render('admin/encabezado/edit.html.twig', [
            'encabezado' => $encabezado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_encabezado_delete', methods: ['POST'])]
    public function delete(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$encabezado->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($encabezado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
    }
}
