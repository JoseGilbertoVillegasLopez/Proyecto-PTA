<?php

namespace App\Controller; 
// Define el namespace del controlador dentro de la aplicación Symfony

use App\Entity\Personal;
// Importa la entidad Personal (representa la tabla personal en la BD)

use App\Form\personal\PersonalType;
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

use App\Entity\Nombramiento;
use App\Entity\User;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
    public function index(PersonalRepository $personalRepository, ModuloAccesoResolver $resolver): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof User || !$resolver->tieneAcceso($user, 'personal'))) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/personal/index.html.twig', [
            'personals' => $personalRepository->findAllOrderByNombre(),
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

        if ($form->isSubmitted()) {
            // Verifica que el formulario fue enviado y pasó validaciones


            $pdf = $form->get('nombramiento_pdf')->getData();

            $tipoNombramiento = $form->get('nombramiento_tipo')->getData();

            // =====================================================
            // VALIDAR PDF ↔ TIPO
            // =====================================================

            // Si subió PDF pero NO seleccionó tipo
            if ($pdf && !$tipoNombramiento) {

                $form->get('nombramiento_tipo')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Debes elegir un tipo de nombramiento.'
                    )
                );
            }

            // Si seleccionó tipo pero NO subió PDF
            if (!$pdf && $tipoNombramiento) {

                $form->get('nombramiento_pdf')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Debes agregar un archivo PDF.'
                    )
                );
            }

            // Si hubo errores, volver a renderizar
            if (!$form->isValid()) {

                $isTurbo = $request->headers->get('Turbo-Frame');

                if ($isTurbo) {

                    return $this->render('admin/personal/new.html.twig', [
                        'personal' => $personal,
                        'form' => $form,
                    ]);
                }

                return $this->render('dashboard/index.html.twig', [
                    'section' => 'personal',
                    'content_url' => $this->generateUrl('app_personal_new'),
                ]);
            }





            // ✅ FORZAMOS ACTIVO AL CREAR
            $personal->setActivo(true);
            // Garantiza que el personal nuevo siempre se cree como activo

                        // =========================================
            // PREPARAR PERSONAL
            // =========================================

            $entityManager->persist($personal);

            // =========================================
            // MANEJO PDF NOMBRAMIENTO
            // =========================================

            if ($pdf) {

                // FORZAR EXTENSIÓN PDF
                $nombreArchivo = uniqid() . '.pdf';

                try {

                    $pdf->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/nombramientos',
                        $nombreArchivo
                    );

                } catch (FileException $e) {

                    $this->addFlash('error', 'Error al subir el PDF.');

                    return $this->redirectToRoute('app_personal_new');
                }

                $nombramiento = new Nombramiento();

                $nombramiento->setArchivo($nombreArchivo);

                $nombramiento->setNombreOriginal(
                    $pdf->getClientOriginalName()
                );

                $nombramiento->setTipo($tipoNombramiento);

                $nombramiento->setActivo(true);

                $nombramiento->setFechaSubida(new \DateTimeImmutable('today'));

                $nombramiento->setFechaDesactivacion(null);

                $nombramiento->setPersonal($personal);

                $entityManager->persist($nombramiento);
            }

            // =========================================
            // GUARDAR TODO
            // =========================================

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
        return $this->render('dashboard/index.html.twig', [
            'section' => 'personal',
            'content_url' => $this->generateUrl('app_personal_new',[            
            ]),
        ]);

        // Si no se envió el formulario o tiene errores
        
    }

    #[Route('/{id}', name: 'app_personal_show', methods: ['GET'])]
    public function show(Request $request, Personal $personal, ModuloAccesoResolver $resolver): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof User || !$resolver->tieneAcceso($user, 'personal'))) {
            throw $this->createAccessDeniedException();
        }

        $isTurbo = $request ->headers->get('Turbo-Frame');
        if ($isTurbo) {
            return $this->render('admin/personal/show.html.twig', [
                'personal' => $personal,
                // Envía la entidad a la vista
            ]);
        }
        // Acceso directo / F5 → renderizar dashboard completo
    return $this->render('dashboard/index.html.twig', [
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

        if ($form->isSubmitted()) {

            // =====================================================
            // OBTENER CAMPOS NOMBRAMIENTO
            // =====================================================

            $pdf = $form->get('nombramiento_pdf')->getData();

            $tipoNombramiento = $form->get('nombramiento_tipo')->getData();

            // =====================================================
            // VALIDAR PDF ↔ TIPO
            // =====================================================

            // PDF SIN TIPO
            if ($pdf && !$tipoNombramiento) {

                $form->get('nombramiento_tipo')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Debes elegir un tipo de nombramiento.'
                    )
                );
            }

            // TIPO SIN PDF
            if (!$pdf && $tipoNombramiento) {

                $form->get('nombramiento_pdf')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Debes agregar un archivo PDF.'
                    )
                );
            }

            // =====================================================
            // VALIDAR FORMULARIO COMPLETO
            // =====================================================

            if ($form->isValid()) {

                // =================================================
                // SUBIR NOMBRAMIENTO SOLO SI EXISTE
                // =================================================

                if ($pdf) {

                    $nombreArchivo = uniqid() . '.pdf';

                    try {

                        $pdf->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/nombramientos',
                            $nombreArchivo
                        );

                    } catch (FileException $e) {

                        $this->addFlash(
                            'error',
                            'Error al subir el PDF.'
                        );

                        return $this->redirectToRoute(
                            'app_personal_edit',
                            [
                                'id' => $personal->getId()
                            ]
                        );
                    }

                    // =============================================
                    // CREAR NOMBRAMIENTO
                    // =============================================

                    $nombramiento = new Nombramiento();

                    $nombramiento->setArchivo($nombreArchivo);

                    $nombramiento->setNombreOriginal(
                        $pdf->getClientOriginalName()
                    );

                    $nombramiento->setTipo($tipoNombramiento);

                    $nombramiento->setActivo(true);

                    $nombramiento->setFechaSubida(
                        new \DateTimeImmutable('today')
                    );

                    $nombramiento->setFechaDesactivacion(null);

                    $nombramiento->setPersonal($personal);

                    $entityManager->persist($nombramiento);
                }

                // =================================================
                // GUARDAR CAMBIOS
                // =================================================

                $entityManager->flush();

                // =================================================
                // ACTUALIZAR USER
                // =================================================

                $this->userUpdater->updateFromPersonal(
                    $personal
                );

                // =================================================
                // REDIRECCIONAR AL INDEX
                // =================================================

                return $this->redirectToRoute(
                    'app_personal_index'
                );
            }
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
    return $this->render('dashboard/index.html.twig', [
        'section' => 'personal',
        'content_url' => $this->generateUrl('app_personal_edit', [
            'id' => $personal->getId(),
        ]),
    ]);
    }

    #[Route(
        '/{id}/nombramientos/historial',
        name: 'app_nombramiento_historial',
        methods: ['GET']
    )]
    public function historialNombramientos(
        Request $request,
        Personal $personal
    ): Response {

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {

            return $this->render(
                'admin/nombramiento/historial_nombramiento.html.twig',
                [
                    'personal' => $personal,
                ]
            );
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'personal',
            'content_url' => $this->generateUrl(
                'app_nombramiento_historial',
                [
                    'id' => $personal->getId(),
                ]
            ),
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
