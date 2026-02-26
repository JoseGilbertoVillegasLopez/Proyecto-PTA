<?php

namespace App\Controller;

use App\Entity\ProcesoClave;
use App\Form\ProcesoClave\ProcesoClaveType;
use App\Form\ProcesoClave\ProcesoClaveEditType;
use App\Repository\ProcesoClaveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('proceso_clave')]
final class ProcesoClaveController extends AbstractController
{
    #[Route(name: 'app_proceso_clave_index', methods: ['GET'])]
    public function index(ProcesoClaveRepository $repository): Response
    {
        return $this->render('proceso_clave/index.html.twig', [
            'proceso_claves' => $repository->findAllOrderByNombre(),
        ]);
    }

    #[Route('/new', name: 'app_proceso_clave_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProcesoClaveRepository $repository
    ): Response {

        $proceso = new ProcesoClave();

        $form = $this->createForm(ProcesoClaveType::class, $proceso);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $proceso->setActivo(true);

            $entityManager->persist($proceso);

            $entityManager->flush();

            return $this->redirectToRoute('app_proceso_clave_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_clave/new.html.twig', [
                'proceso' => $proceso,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_clave',
            'content_url' => $this->generateUrl('app_proceso_clave_new'),
        ]);
    }

    #[Route('/{id}', name: 'app_proceso_clave_show', methods: ['GET'])]
    public function show(Request $request, ProcesoClave $proceso): Response
    {
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_clave/show.html.twig', [
                'proceso' => $proceso,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_clave',
            'content_url' => $this->generateUrl('app_proceso_clave_show', [
                'id' => $proceso->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proceso_clave_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ProcesoClave $proceso,
        EntityManagerInterface $entityManager,
        ProcesoClaveRepository $repository
    ): Response {

        $form = $this->createForm(ProcesoClaveEditType::class, $proceso);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('app_proceso_clave_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_clave/edit.html.twig', [
                'proceso' => $proceso,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_clave',
            'content_url' => $this->generateUrl('app_proceso_clave_edit', [
                'id' => $proceso->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_proceso_clave_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ProcesoClave $proceso,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->isCsrfTokenValid(
            'delete' . $proceso->getId(),
            $request->getPayload()->getString('_token')
        )) {

            $proceso->setActivo(false);

            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_proceso_clave_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}