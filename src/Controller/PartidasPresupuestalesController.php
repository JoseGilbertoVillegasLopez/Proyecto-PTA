<?php

namespace App\Controller;

use App\Entity\PartidasPresupuestales;
use App\Form\PartidasPresupuestales\PartidasPresupuestalesType;
use App\Form\PartidasPresupuestales\PartidasPresupuestalesEditType;
use App\Repository\PartidasPresupuestalesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/partidas_presupuestales')]
final class PartidasPresupuestalesController extends AbstractController
{
    #[Route(name: 'app_partidas_presupuestales_index', methods: ['GET'])]
    public function index(PartidasPresupuestalesRepository $repository): Response
    {
        return $this->render('partidas_presupuestales/index.html.twig', [
            'partidas_presupuestales' => $repository->findAllOrderByCapituloPartida(),
        ]);
    }

    #[Route('/new', name: 'app_partidas_presupuestales_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $partida = new PartidasPresupuestales();
        $form = $this->createForm(PartidasPresupuestalesType::class, $partida);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $partida->setActivo(true);
            $em->persist($partida);
            $em->flush();

            return $this->redirectToRoute('app_partidas_presupuestales_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('partidas_presupuestales/new.html.twig', [
                'partida' => $partida,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'partidas_presupuestales',
            'content_url' => $this->generateUrl('app_partidas_presupuestales_new'),
        ]);
    }

    #[Route('/{id}', name: 'app_partidas_presupuestales_show', methods: ['GET'])]
    public function show(Request $request, PartidasPresupuestales $partida): Response
    {
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('partidas_presupuestales/show.html.twig', [
                'partida' => $partida,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'partidas_presupuestales',
            'content_url' => $this->generateUrl('app_partidas_presupuestales_show', [
                'id' => $partida->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_partidas_presupuestales_edit', methods: ['GET','POST'])]
    public function edit(Request $request, PartidasPresupuestales $partida, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PartidasPresupuestalesEditType::class, $partida);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('app_partidas_presupuestales_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('partidas_presupuestales/edit.html.twig', [
                'partida' => $partida,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'partidas_presupuestales',
            'content_url' => $this->generateUrl('app_partidas_presupuestales_edit', [
                'id' => $partida->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_partidas_presupuestales_delete', methods: ['POST'])]
    public function delete(Request $request, PartidasPresupuestales $partida, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid(
            'delete' . $partida->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $partida->setActivo(false);
            $em->flush();
        }

        return $this->redirectToRoute('app_partidas_presupuestales_index');
    }
}