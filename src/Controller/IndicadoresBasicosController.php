<?php

namespace App\Controller;

use App\Entity\IndicadoresBasicos;
use App\Form\IndicadoresBasicos\IndicadoresBasicosType;
use App\Form\IndicadoresBasicos\IndicadoresBasicosEditType;
use App\Repository\IndicadoresBasicosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('indicadores_basicos')]
final class IndicadoresBasicosController extends AbstractController
{
    #[Route(name: 'app_indicadores_basicos_index', methods: ['GET'])]
    public function index(IndicadoresBasicosRepository $repository): Response
    {
        return $this->render('indicadores_basicos/index.html.twig', [
            'indicadores_basicos' => $repository->findAllOrderByNombre(),
        ]);
    }

    #[Route('/new', name: 'app_indicadores_basicos_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $indicador = new IndicadoresBasicos();
        $form = $this->createForm(IndicadoresBasicosType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $indicador->setActivo(true);
            $em->persist($indicador);
            $em->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/new.html.twig', [
                'indicador' => $indicador,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_new'),
        ]);
    }

    #[Route('/{id}', name: 'app_indicadores_basicos_show', methods: ['GET'])]
    public function show(Request $request, IndicadoresBasicos $indicador): Response
    {
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/show.html.twig', [
                'indicador' => $indicador,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_show', [
                'id' => $indicador->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_indicadores_basicos_edit', methods: ['GET','POST'])]
    public function edit(Request $request, IndicadoresBasicos $indicador, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(IndicadoresBasicosEditType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/edit.html.twig', [
                'indicador' => $indicador,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_edit', [
                'id' => $indicador->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_indicadores_basicos_delete', methods: ['POST'])]
    public function delete(Request $request, IndicadoresBasicos $indicador, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid(
            'delete' . $indicador->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $indicador->setActivo(false);
            $em->flush();
        }

        return $this->redirectToRoute('app_indicadores_basicos_index');
    }
}