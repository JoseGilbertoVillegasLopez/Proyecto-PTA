<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Form\PersonalType;
use App\Form\personal\PersonalEditType;
use App\Repository\PersonalRepository;
use App\Service\UserCreator;
use App\Service\UserUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/personal')]
final class PersonalController extends AbstractController
{
    private UserCreator $userCreator;
    private UserUpdater $userUpdater;

    public function __construct(UserCreator $userCreator, UserUpdater $userUpdater)
    {
        $this->userCreator = $userCreator;
        $this->userUpdater = $userUpdater;
    }

    #[Route(name: 'app_personal_index', methods: ['GET'])]
    public function index(PersonalRepository $personalRepository): Response
    {
        return $this->render('admin/personal/index.html.twig', [
            'personals' => $personalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_personal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $personal = new Personal();
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($personal);
            $entityManager->flush();

            $this->userCreator->createFromPersonal($personal);

            return $this->redirectToRoute(
                'app_personal_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('admin/personal/new.html.twig', [
            'form' => $form->createView(), // 🔥 CLAVE
        ]);
    }

    #[Route('/{id}', name: 'app_personal_show', methods: ['GET'])]
    public function show(Personal $personal): Response
    {
        return $this->render('admin/personal/show.html.twig', [
            'personal' => $personal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_personal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personal $personal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PersonalEditType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->userUpdater->updateFromPersonal($personal);

            return $this->redirectToRoute(
                'app_personal_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('admin/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form->createView(), // 🔥 CLAVE
        ]);
    }

    #[Route('/{id}', name: 'app_personal_delete', methods: ['POST'])]
    public function delete(Request $request, Personal $personal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$personal->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($personal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_personal_index', [], Response::HTTP_SEE_OTHER);
    }
}
