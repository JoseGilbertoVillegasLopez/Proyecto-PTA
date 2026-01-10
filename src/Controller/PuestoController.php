<?php

namespace App\Controller;
// Define el namespace del controlador dentro de la capa Controller

use App\Entity\Puesto;
// Importa la entidad Puesto (representa la tabla puesto en la base de datos)

use App\Form\PuestoType;
// Importa el formulario usado para CREAR un Puesto

use App\Repository\PuestoRepository;
// Importa el repositorio para consultar datos de Puesto en la BD

use Doctrine\ORM\EntityManagerInterface;
// Permite interactuar con Doctrine (persistir, actualizar y eliminar entidades)

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Controlador base de Symfony con métodos helper como render(), redirect(), CSRF, etc.

use Symfony\Component\HttpFoundation\Request;
// Representa la petición HTTP entrante (GET, POST, payload, etc.)

use Symfony\Component\HttpFoundation\Response;
// Representa la respuesta HTTP devuelta al navegador

use Symfony\Component\Routing\Attribute\Route;
// Permite definir rutas mediante atributos (PHP 8+)

use App\Form\puesto\PuestoEditType;
// Importa el formulario específico para EDITAR un Puesto


#[Route('admin/puesto')]
// Prefijo de ruta: todas las rutas de este controlador comienzan con /admin/puesto
final class PuestoController extends AbstractController
{
    #[Route(name: 'app_puesto_index', methods: ['GET'])]
    // Ruta GET /admin/puesto
    // Muestra el listado de puestos
    public function index(PuestoRepository $puestoRepository): Response
    {
        return $this->render('admin/puesto/index.html.twig', [
            // Renderiza la vista del listado

            'puestos' => $puestoRepository->findAll(),
            // Obtiene todos los registros de la tabla puesto y los pasa a la vista
        ]);
    }

    #[Route('/new', name: 'app_puesto_new', methods: ['GET', 'POST'])]
    // Ruta para crear un nuevo puesto
    // GET  -> muestra el formulario
    // POST -> procesa el formulario
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $puesto = new Puesto();
        // Crea una nueva instancia vacía de la entidad Puesto

        $form = $this->createForm(PuestoType::class, $puesto);
        // Crea el formulario de creación y lo vincula a la entidad

        $form->handleRequest($request);
        // Procesa la petición HTTP y carga los datos enviados al formulario

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica que el formulario fue enviado y pasó todas las validaciones

            $entityManager->persist($puesto);
            // Marca la entidad para ser insertada en la base de datos

            $entityManager->flush();
            // Ejecuta el INSERT real en la base de datos

            return $this->redirectToRoute(
                'app_puesto_index',
                [],
                Response::HTTP_SEE_OTHER
            );
            // Redirige al listado aplicando el patrón Post/Redirect/Get
        }

        // Si el formulario no fue enviado o tiene errores
        return $this->render('admin/puesto/new.html.twig', [
            'puesto' => $puesto,
            // Se envía la entidad a la vista (útil para valores por defecto)

            'form' => $form,
            // Se envía el formulario para renderizarlo
        ]);
    }

    #[Route('/{id}', name: 'app_puesto_show', methods: ['GET'])]
    // Ruta GET /admin/puesto/{id}
    // Muestra el detalle de un puesto
    public function show(Puesto $puesto): Response
    {
        // Symfony convierte automáticamente {id} en una entidad Puesto
        return $this->render('admin/puesto/show.html.twig', [
            'puesto' => $puesto,
            // Pasa la entidad completa a la vista
        ]);
    }

    #[Route('/{id}/edit', name: 'app_puesto_edit', methods: ['GET', 'POST'])]
    // Ruta para editar un puesto existente
    // GET  -> muestra el formulario con los datos actuales
    // POST -> guarda los cambios
    public function edit(
        Request $request,
        Puesto $puesto,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(PuestoEditType::class, $puesto);
        // Crea el formulario de edición vinculado a la entidad existente

        $form->handleRequest($request);
        // Procesa los datos enviados desde el formulario

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica envío y validez del formulario

            $entityManager->flush();
            // Ejecuta el UPDATE en la base de datos
            // No se usa persist porque la entidad ya está gestionada por Doctrine

            return $this->redirectToRoute(
                'app_puesto_index',
                [],
                Response::HTTP_SEE_OTHER
            );
            // Redirige al listado después de guardar los cambios
        }

        // Si no se envió el formulario o tiene errores
        return $this->render('admin/puesto/edit.html.twig', [
            'puesto' => $puesto,
            // Entidad actual a editar

            'form' => $form,
            // Formulario de edición
        ]);
    }

    #[Route('/{id}', name: 'app_puesto_delete', methods: ['POST'])]
    // Ruta POST para eliminar un puesto
    // Protegida con token CSRF
    public function delete(
        Request $request,
        Puesto $puesto,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete'.$puesto->getId(),
            // Token CSRF único generado por entidad

            $request->getPayload()->getString('_token')
            // Token enviado desde el formulario
        )) {
            // Verifica que el token CSRF sea válido

            $entityManager->remove($puesto);
            // Marca la entidad para eliminación

            $entityManager->flush();
            // Ejecuta el DELETE real en la base de datos
        }

        return $this->redirectToRoute(
            'app_puesto_index',
            [],
            Response::HTTP_SEE_OTHER
        );
        // Redirige al listado después de eliminar
    }
}
