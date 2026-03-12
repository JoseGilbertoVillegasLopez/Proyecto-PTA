<?php

namespace App\Controller;

use App\Entity\ProcesoEstrategico;
use App\Form\ProcesoEstrategico\ProcesoEstrategicoType;
use App\Form\ProcesoEstrategico\ProcesoEstrategicoEditType;
use App\Repository\ProcesoEstrategicoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('proceso_estrategico')]
final class ProcesoEstrategicoController extends AbstractController
{
    #[Route(name: 'app_proceso_estrategico_index', methods: ['GET'])]
    public function index(ProcesoEstrategicoRepository $repository): Response
    {
        return $this->render('proceso_estrategico/index.html.twig', [
            'proceso_estrategicos' => $repository->findAllOrderByNombre(),
        ]);
    }

    #[Route('/new', name: 'app_proceso_estrategico_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProcesoEstrategicoRepository $repository
    ): Response {

        $proceso = new ProcesoEstrategico();

        $form = $this->createForm(ProcesoEstrategicoType::class, $proceso);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $proceso->setActivo(true);

            $entityManager->persist($proceso);

            $entityManager->flush();

            return $this->redirectToRoute('app_proceso_estrategico_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_estrategico/new.html.twig', [
                'proceso' => $proceso,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_estrategico',
            'content_url' => $this->generateUrl('app_proceso_estrategico_new'),
        ]);
    }

    #[Route('/{id}', name: 'app_proceso_estrategico_show', methods: ['GET'])]
    public function show(Request $request, ProcesoEstrategico $proceso): Response
    {
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_estrategico/show.html.twig', [
                'proceso' => $proceso,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_estrategico',
            'content_url' => $this->generateUrl('app_proceso_estrategico_show', [
                'id' => $proceso->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proceso_estrategico_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ProcesoEstrategico $proceso,
        EntityManagerInterface $entityManager,
        ProcesoEstrategicoRepository $repository
    ): Response {

        $form = $this->createForm(ProcesoEstrategicoEditType::class, $proceso);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('app_proceso_estrategico_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('proceso_estrategico/edit.html.twig', [
                'proceso' => $proceso,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'proceso_estrategico',
            'content_url' => $this->generateUrl('app_proceso_estrategico_edit', [
                'id' => $proceso->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_proceso_estrategico_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ProcesoEstrategico $proceso,
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
            'app_proceso_estrategico_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}