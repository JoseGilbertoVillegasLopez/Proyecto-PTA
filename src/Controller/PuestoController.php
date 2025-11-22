<?php

namespace App\Controller;

use App\Entity\Puesto;
use App\Form\PuestoType;
use App\Repository\PuestoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/puesto')]
final class PuestoController extends AbstractController
{
    #[Route(name: 'app_puesto_index', methods: ['GET'])]
    public function index(PuestoRepository $puestoRepository): Response
    {
        return $this->render('admin/puesto/index.html.twig', [
            'puestos' => $puestoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_puesto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $puesto = new Puesto();
        $form = $this->createForm(PuestoType::class, $puesto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($puesto);
            $entityManager->flush();

            return $this->redirectToRoute('app_puesto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/puesto/new.html.twig', [
            'puesto' => $puesto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_puesto_show', methods: ['GET'])]
    public function show(Puesto $puesto): Response
    {
        return $this->render('admin/puesto/show.html.twig', [
            'puesto' => $puesto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_puesto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Puesto $puesto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PuestoType::class, $puesto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_puesto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/puesto/edit.html.twig', [
            'puesto' => $puesto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_puesto_delete', methods: ['POST'])]
    public function delete(Request $request, Puesto $puesto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$puesto->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($puesto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_puesto_index', [], Response::HTTP_SEE_OTHER);
    }
}
