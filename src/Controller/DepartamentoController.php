<?php

namespace App\Controller;
// Define el namespace del controlador dentro de la carpeta Controller

use App\Entity\Departamento;
// Importa la entidad Departamento (representa la tabla departamento en la BD)

use App\Form\DepartamentoType;
// Importa el formulario usado para CREAR un Departamento

use App\Repository\DepartamentoRepository;
// Importa el repositorio para consultas a la BD de Departamento

use Doctrine\ORM\EntityManagerInterface;
// Permite interactuar con Doctrine (persistir, actualizar, eliminar entidades)

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Controlador base de Symfony con métodos helper (render, redirect, csrf, etc.)

use Symfony\Component\HttpFoundation\Request;
// Representa la petición HTTP entrante (GET, POST, payload, etc.)

use Symfony\Component\HttpFoundation\Response;
// Representa la respuesta HTTP que devuelve el controlador

use Symfony\Component\Routing\Attribute\Route;
// Permite definir rutas mediante atributos (PHP 8+)

use App\Form\departamento\DepartamentoEditType;
// Importa el formulario específico para EDITAR Departamento


#[Route('admin/departamento')]
// Prefijo de ruta: todas las rutas de este controlador comienzan con /admin/departamento
final class DepartamentoController extends AbstractController
{
    #[Route(name: 'app_departamento_index', methods: ['GET'])]
public function index(
    Request $request,
    DepartamentoRepository $departamentoRepository
): Response {

    $isTurbo = $request->headers->has('Turbo-Frame');

    // ============================
    // TURBO → SOLO CONTENIDO
    // ============================
    if ($isTurbo) {
        return $this->render('admin/departamento/index.html.twig', [
            'departamentos' => $departamentoRepository->findAll(),
        ]);
    }

    // ============================
    // NO TURBO (F5 / URL DIRECTA)
    // ============================
    return $this->render('dashboard/index.html.twig', [
        'section'     => 'departamentos',
        'content_url' => $this->generateUrl('app_departamento_index'),
    ]);
}


    #[Route('/new', name: 'app_departamento_new', methods: ['GET', 'POST'])]
    // Ruta para crear un nuevo departamento
    // GET  -> muestra el formulario
    // POST -> procesa el formulario
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departamento = new Departamento();
        // Crea una nueva instancia vacía de la entidad Departamento

        $form = $this->createForm(DepartamentoType::class, $departamento);
        // Crea el formulario de creación y lo vincula a la entidad

        $form->handleRequest($request);
        // Procesa la petición HTTP y carga los datos enviados al formulario

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica que el formulario fue enviado y pasó todas las validaciones

            $entityManager->persist($departamento);
            // Marca la entidad para ser insertada en la BD

            $entityManager->flush();
            // Ejecuta el INSERT real en la base de datos

            return $this->redirectToRoute(
                'app_departamento_index',
                [],
                Response::HTTP_SEE_OTHER
            );
            // Redirige al listado usando el patrón Post/Redirect/Get
        }
        $isTurbo = $request->headers->get('Turbo-Frame');
        if($isTurbo){
            // Si el formulario no fue enviado o tiene errores
        return $this->render('admin/departamento/new.html.twig', [
            'departamento' => $departamento,
            // Se envía la entidad (útil para la vista)

            'form' => $form,
            // Se envía el formulario para renderizarlo
        ]);
        }
        return $this->render('dashboard/index.html.twig',[
            'section' => 'departamentos',
            'content_url' => $this->generateUrl('app_departamento_new',[
            ]),
        ]);
        
    }

    #[Route('/{id}', name: 'app_departamento_show', methods: ['GET'])]
    // Ruta GET /admin/departamento/{id}
    // Muestra el detalle de un departamento
    public function show(Request $request, Departamento $departamento): Response
    {

        $isTurbo = $request->headers->get('Turbo-Frame');
        if($isTurbo){
            // Symfony convierte automáticamente {id} en una entidad Departamento
        return $this->render('admin/departamento/show.html.twig', [
            'departamento' => $departamento,
            // Pasa la entidad a la vista de detalle
        ]);
        }
        return $this->render('dashboard/index.html.twig',[
            'section' => 'departamentos',
            'content_url' => $this->generateUrl('app_departamento_show',[
                'id' => $departamento->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_departamento_edit', methods: ['GET', 'POST'])]
    // Ruta para editar un departamento existente
    // GET  -> muestra el formulario con datos actuales
    // POST -> guarda los cambios
    public function edit(
        Request $request,
        Departamento $departamento,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(DepartamentoEditType::class, $departamento);
        // Crea el formulario de edición vinculado a la entidad existente

        $form->handleRequest($request);
        // Procesa los datos enviados desde el formulario

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica envío y validez del formulario

            $entityManager->flush();
            // Ejecuta el UPDATE en la base de datos
            // No se usa persist porque la entidad ya está gestionada

            return $this->redirectToRoute(
                'app_departamento_index',
                [],
                Response::HTTP_SEE_OTHER
            );
            // Redirige al listado después de guardar
        }
        $isTurbo = $request->headers->get('Turbo-Frame');
        if($isTurbo){
            // Si no se envió el formulario o tiene errores
        return $this->render('admin/departamento/edit.html.twig', [
            'departamento' => $departamento,
            // Entidad actual

            'form' => $form,
            // Formulario de edición
        ]);
        }
        return $this->render('dashboard/index.html.twig',[
            'section' => 'departamentos',
            'content_url' => $this->generateUrl('app_departamento_edit',[
                'id' => $departamento->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_departamento_delete', methods: ['POST'])]
    // Ruta POST para eliminar un departamento
    // Se protege con CSRF
    public function delete(
        Request $request,
        Departamento $departamento,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete'.$departamento->getId(),
            // Token CSRF único por entidad

            $request->getPayload()->getString('_token')
            // Token enviado desde el formulario
        )) {
            // Verifica que el token CSRF sea válido

            $entityManager->remove($departamento);
            // Marca la entidad para eliminación

            $entityManager->flush();
            // Ejecuta el DELETE en la base de datos
        }

        return $this->redirectToRoute(
            'app_departamento_index',
            [],
            Response::HTTP_SEE_OTHER
        );
        // Redirige al listado después de eliminar
    }
}
