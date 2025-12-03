<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Form\EncabezadoType;
use App\Repository\EncabezadoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/encabezado')]
final class EncabezadoController extends AbstractController
{
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
    public function index(EncabezadoRepository $encabezadoRepository): Response
    {
        return $this->render('encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $encabezado = new Encabezado();
        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($encabezado);
            $entityManager->flush();

            return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('encabezado/new.html.twig', [
            'encabezado' => $encabezado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
    public function show(Encabezado $encabezado): Response
    {
        return $this->render('encabezado/show.html.twig', [
            'encabezado' => $encabezado,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('encabezado/edit.html.twig', [
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
