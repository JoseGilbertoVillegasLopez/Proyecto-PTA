<?php

namespace App\Controller;

use App\Entity\Departamento;
use App\Form\DepartamentoType;
use App\Repository\DepartamentoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/departamento')]
final class DepartamentoController extends AbstractController
{
    #[Route(name: 'app_departamento_index', methods: ['GET'])]
    public function index(DepartamentoRepository $departamentoRepository): Response
    {
        return $this->render('admin/departamento/index.html.twig', [
            'departamentos' => $departamentoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_departamento_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departamento = new Departamento();
        $form = $this->createForm(DepartamentoType::class, $departamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($departamento);
            $entityManager->flush();

            return $this->redirectToRoute('app_departamento_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/departamento/new.html.twig', [
            'departamento' => $departamento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_departamento_show', methods: ['GET'])]
    public function show(Departamento $departamento): Response
    {
        return $this->render('admin/departamento/show.html.twig', [
            'departamento' => $departamento,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_departamento_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departamento $departamento, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartamentoType::class, $departamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_departamento_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/departamento/edit.html.twig', [
            'departamento' => $departamento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_departamento_delete', methods: ['POST'])]
    public function delete(Request $request, Departamento $departamento, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$departamento->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($departamento);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_departamento_index', [], Response::HTTP_SEE_OTHER);
    }
}
