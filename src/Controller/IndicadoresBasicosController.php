<?php

namespace App\Controller;

use App\Entity\IndicadoresBasicos;
use App\Form\IndicadoresBasicosType;
use App\Form\IndicadoresBasicos\IndicadoresBasicosEditType;
use App\Repository\IndicadoresBasicosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/indicadores_basicos')]
/**
 * =========================================================
 * CONTROLADOR ADMIN — INDICADORES BÁSICOS
 * ---------------------------------------------------------
 * Gestiona el CRUD completo del catálogo de indicadores
 * básicos dentro del panel administrativo.
 *
 * - Soporta navegación con Turbo Frame
 * - Soporta acceso directo (dashboard completo)
 * - Sigue el mismo patrón que PersonalController
 * =========================================================
 */
final class IndicadoresBasicosController extends AbstractController
{
    /**
     * =====================================================
     * INDEX
     * -----------------------------------------------------
     * Muestra el listado de indicadores básicos ordenados
     * alfabéticamente.
     * =====================================================
     */
    #[Route(name: 'app_indicadores_basicos_index', methods: ['GET'])]
    public function index(IndicadoresBasicosRepository $repository): Response
    {
        return $this->render('admin/indicadores_basicos/index.html.twig', [
            'indicadores_basicos' => $repository->findAllOrderByNombre(),
        ]);
    }

    /**
     * =====================================================
     * NEW
     * -----------------------------------------------------
     * Permite crear un nuevo indicador básico.
     *
     * - GET  → muestra formulario
     * - POST → guarda el indicador
     * - Maneja Turbo vs acceso directo
     * =====================================================
     */
    #[Route('/new', name: 'app_indicadores_basicos_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $indicador = new IndicadoresBasicos();
        $form = $this->createForm(IndicadoresBasicosType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($indicador);
            $entityManager->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        // Navegación interna con Turbo
        if ($request->headers->get('Turbo-Frame')) {
            return $this->render('admin/indicadores_basicos/new.html.twig', [
                'indicador' => $indicador,
                'form' => $form,
            ]);
        }

        // Acceso directo → dashboard completo
        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_new'),
        ]);
    }

    /**
     * =====================================================
     * SHOW
     * -----------------------------------------------------
     * Muestra el detalle de un indicador básico.
     *
     * - Usa ParamConverter para cargar la entidad
     * - Soporta Turbo y acceso directo
     * =====================================================
     */
    #[Route('/{id}', name: 'app_indicadores_basicos_show', methods: ['GET'])]
    public function show(Request $request, IndicadoresBasicos $indicador): Response
    {
        if ($request->headers->get('Turbo-Frame')) {
            return $this->render('admin/indicadores_basicos/show.html.twig', [
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

    /**
     * =====================================================
     * EDIT
     * -----------------------------------------------------
     * Permite editar un indicador básico existente.
     *
     * - Usa un EditType independiente
     * - Guarda cambios con flush()
     * - Maneja Turbo y dashboard
     * =====================================================
     */
    #[Route('/{id}/edit', name: 'app_indicadores_basicos_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        IndicadoresBasicos $indicador,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(IndicadoresBasicosEditType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        if ($request->headers->get('Turbo-Frame')) {
            return $this->render('admin/indicadores_basicos/edit.html.twig', [
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

    /**
     * =====================================================
     * DELETE
     * -----------------------------------------------------
     * Elimina un indicador básico.
     *
     * - Protegido por token CSRF
     * - Solo acepta POST
     * =====================================================
     */
    #[Route('/{id}', name: 'app_indicadores_basicos_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        IndicadoresBasicos $indicador,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $indicador->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($indicador);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_indicadores_basicos_index');
    }
}
