<?php

namespace App\Controller; 
// Define el namespace del controlador dentro de la aplicación Symfony

use App\Entity\Personal;
// Importa la entidad Personal (representa la tabla personal en la BD)

use App\Form\PersonalType;
// Importa el formulario usado para crear Personal

use App\Form\personal\PersonalEditType;
// Importa el formulario específico para editar Personal (hereda de PersonalType)

use App\Repository\PersonalRepository;
// Importa el repositorio para consultas a la BD de Personal

use App\Service\UserCreator;
// Servicio encargado de crear un usuario a partir de Personal

use App\Service\UserUpdater;
// Servicio encargado de sincronizar/actualizar el usuario cuando cambia Personal

use Doctrine\ORM\EntityManagerInterface;
// Permite interactuar con Doctrine (persistir, flush, remove, etc.)

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Controlador base de Symfony con helpers (render, redirect, csrf, etc.)

use Symfony\Component\HttpFoundation\Request;
// Representa la petición HTTP entrante

use Symfony\Component\HttpFoundation\Response;
// Representa la respuesta HTTP

use Symfony\Component\Routing\Attribute\Route;
// Permite definir rutas usando atributos (PHP 8+)

#[Route('admin/personal')]
// Prefijo de ruta: todas las rutas de este controlador comienzan con /admin/personal
final class PersonalController extends AbstractController
{
    private UserCreator $userCreator;
    // Propiedad privada para el servicio que crea usuarios

    private UserUpdater $userUpdater;
    // Propiedad privada para el servicio que actualiza usuarios

    public function __construct(UserCreator $userCreator, UserUpdater $userUpdater)
    {
        // Inyección de dependencias automática de Symfony
        $this->userCreator = $userCreator;
        // Guarda el servicio UserCreator para usarlo en los métodos

        $this->userUpdater = $userUpdater;
        // Guarda el servicio UserUpdater para usarlo en los métodos
    }

    #[Route(name: 'app_personal_index', methods: ['GET'])]
    // Ruta GET /admin/personal
    // Muestra el listado de personal
    public function index(PersonalRepository $personalRepository): Response
    {
        // Renderiza la vista index con todos los registros de Personal
        return $this->render('admin/personal/index.html.twig', [
            'personals' => $personalRepository->findAll(),
            // Obtiene todos los registros desde la BD
        ]);
    }

    #[Route('/new', name: 'app_personal_new', methods: ['GET', 'POST'])]
    // Ruta para crear nuevo personal (mostrar formulario y procesarlo)
    public function new(
        Request $request,
        // Contiene los datos HTTP (POST, GET, etc.)

        EntityManagerInterface $entityManager,
        // Permite guardar datos en la BD

        PersonalRepository $personalRepository
        // Se usa para volver a cargar el listado tras guardar
    ): Response {
        $personal = new Personal();
        // Crea una nueva instancia vacía de Personal

        $form = $this->createForm(PersonalType::class, $personal);
        // Crea el formulario y lo vincula a la entidad Personal

        $form->handleRequest($request);
        // Procesa la petición HTTP y llena el formulario con los datos enviados

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica que el formulario fue enviado y pasó validaciones

            // ✅ FORZAMOS ACTIVO AL CREAR
            $personal->setActivo(true);
            // Garantiza que el personal nuevo siempre se cree como activo

            $entityManager->persist($personal);
            // Marca la entidad para ser guardada en la BD

            $entityManager->flush();
            // Ejecuta el INSERT en la base de datos

            // Crear usuario asociado
            $this->userCreator->createFromPersonal($personal);
            // Llama al servicio que crea automáticamente un usuario
            // asociado a este registro de Personal

            return $this->redirectToRoute('app_personal_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');
        if ($isTurbo){
            return $this->render('admin/personal/new.html.twig', [
            'personal' => $personal,
            // Se envía la entidad (útil para la vista)

            'form' => $form,
            // Se envía el formulario para renderizarlo
        ]);
        }
        return $this->render('admin/dashboard/index.html.twig', [
            'section' => 'personal',
            'content_url' => $this->generateUrl('app_personal_new',[            
            ]),
        ]);

        // Si no se envió el formulario o tiene errores
        
    }

    #[Route('/{id}', name: 'app_personal_show', methods: ['GET'])]
    // Ruta para ver el detalle de un Personal
    public function show(Request $request, Personal $personal): Response
    {
        $isTurbo = $request ->headers->get('Turbo-Frame');
        if ($isTurbo) {
            return $this->render('admin/personal/show.html.twig', [
                'personal' => $personal,
                // Envía la entidad a la vista
            ]);
        }
        // Acceso directo / F5 → renderizar dashboard completo
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => 'personal',
        'content_url' => $this->generateUrl('app_personal_show', [
            'id' => $personal->getId(),
        ]),
    ]);
    }

    #[Route('/{id}/edit', name: 'app_personal_edit', methods: ['GET', 'POST'])]
    // Ruta para editar un Personal existente
    public function edit(
        Request $request,
        // Petición HTTP

        Personal $personal,
        // Entidad cargada automáticamente por Symfony usando el ID

        EntityManagerInterface $entityManager,
        // Manejo de persistencia

        PersonalRepository $personalRepository
        // Para recargar listado después de editar
    ): Response {
        $form = $this->createForm(PersonalEditType::class, $personal);
        // Crea el formulario de edición vinculado al Personal existente

        $form->handleRequest($request);
        // Procesa los datos enviados

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica envío y validez del formulario

            $entityManager->flush();
            // Guarda los cambios en la BD (UPDATE)

            // 🔥 PRIMERO sincronizar el usuario (incluye activo/inactivo)
            $this->userUpdater->updateFromPersonal($personal);
            // Sincroniza el usuario asociado (correo, activo, etc.)
            // usando los datos actuales de Personal

            return $this->redirectToRoute('app_personal_index');

        }
        $isTurbo = $request->headers->get('Turbo-Frame');
        if ($isTurbo) {
        // Navegación interna (dashboard ya existe)
        return $this->render('admin/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form,
        ]);
    }

    // Acceso directo / F5
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => 'personal',
        'content_url' => $this->generateUrl('app_personal_edit', [
            'id' => $personal->getId(),
        ]),
    ]);
    }

    #[Route('/{id}', name: 'app_personal_delete', methods: ['POST'])]
    // Ruta para eliminar un Personal (solo POST por seguridad)
    public function delete(
        Request $request,
        // Petición HTTP

        Personal $personal,
        // Entidad a eliminar

        EntityManagerInterface $entityManager
        // Manejo de BD
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $personal->getId(),
            // Token único por entidad

            $request->getPayload()->getString('_token')
            // Token enviado desde el formulario
        )) {
            // Verifica que el token CSRF sea válido

            $entityManager->remove($personal);
            // Marca la entidad para eliminación

            $entityManager->flush();
            // Ejecuta el DELETE en la BD
        }

        return $this->redirectToRoute(
            'app_personal_index',
            [],
            Response::HTTP_SEE_OTHER
        );
        // Redirige al listado después de eliminar
    }
}
