<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Form\EncabezadoType;
use App\Repository\EncabezadoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/encabezado')]
final class EncabezadoController extends AbstractController
{
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
    public function index(EncabezadoRepository $encabezadoRepository): Response
    {
        return $this->render('admin/encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
        public function new(
            Request $request,
            EntityManagerInterface $entityManager,
            EncabezadoRepository $encabezadoRepository
        ): Response
        {
            /**
             * =========================================================
             * CREACIÓN DE LA ENTIDAD PRINCIPAL
             * ---------------------------------------------------------
             * Encabezado es la entidad raíz del PTA.
             * Todas las demás entidades (Responsables, Indicadores,
             * Acciones) dependen de esta.
             * =========================================================
             */
            $encabezado = new Encabezado();

            /**
             * =========================================================
             * INICIALIZACIÓN DE RESPONSABLES (OneToOne)
             * ---------------------------------------------------------
             * - Responsables es una relación OneToOne con Encabezado
             * - Se inicializa manualmente porque:
             *   - El FormType usa campos mapped=false
             *   - Symfony NO lo crea automáticamente
             * =========================================================
             */
            $responsables = new \App\Entity\Responsables();
            $encabezado->setResponsables($responsables);

            /**
             * =========================================================
             * RESPONSABLE PRINCIPAL (USUARIO LOGUEADO)
             * ---------------------------------------------------------
             * - El responsable NO es el supervisor ni el aval
             * - Es el Personal asociado al usuario autenticado
             * - Se asigna automáticamente al crear el PTA
             * =========================================================
             */
            $usuario = $this->getUser();
            if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
                $encabezado->setResponsable($usuario->getPersonal());
            }

            /**
             * =========================================================
             * CREACIÓN Y MANEJO DEL FORMULARIO
             * ---------------------------------------------------------
             * - EncabezadoType incluye:
             *   - ResponsablesType
             *   - CollectionType de Indicadores
             *   - CollectionType de Acciones
             * =========================================================
             */
            $form = $this->createForm(EncabezadoType::class, $encabezado);
            $form->handleRequest($request);

            /**
             * =========================================================
             * PROCESAMIENTO DEL SUBMIT
             * ---------------------------------------------------------
             * - El JS ya validó la lógica de negocio
             * - Aquí solo se persiste lo recibido
             * =========================================================
             */
            if ($form->isSubmitted() && $form->isValid()) {

                /**
                 * =====================================================
                 * PROCESAMIENTO MANUAL DE SUPERVISOR Y AVAL
                 * -----------------------------------------------------
                 * - Estos campos son mapped=false en el FormType
                 * - Se reciben como IDs dentro del request
                 * - Se asignan manualmente a la entidad Responsables
                 * =====================================================
                 */
                $responsables = $encabezado->getResponsables();

                $responsables = $encabezado->getResponsables();

                if ($responsables) {

                    // Obtener todos los datos del formulario Encabezado
                    $data = $request->request->all('encabezado');

                    // IDs enviados por los inputs hidden
                    $supervisorId = $data['responsables']['supervisor'] ?? null;
                    $avalId       = $data['responsables']['aval'] ?? null;

                    // Asignación del Supervisor (Personal)
                    if ($supervisorId) {
                        $supervisor = $entityManager
                            ->getRepository(Personal::class)
                            ->find($supervisorId);

                        $responsables->setSupervisor($supervisor);
                    }

                    // Asignación del Aval (Personal)
                    if ($avalId) {
                        $aval = $entityManager
                            ->getRepository(Personal::class)
                            ->find($avalId);

                        $responsables->setAval($aval);
                    }
                }

                /**
                 * =====================================================
                 * METADATOS DEL PTA
                 * -----------------------------------------------------
                 * - Fecha de creación
                 * - Estatus inicial activo
                 * =====================================================
                 */
                $encabezado->setFechaCreacion(new \DateTime());
                $encabezado->setStatus(true);

                /**
                 * =====================================================
                 * ASEGURAR RELACIÓN PADRE → HIJOS
                 * -----------------------------------------------------
                 * - Doctrine NO asigna automáticamente la relación
                 * - Se hace manual para:
                 *   - Indicadores
                 *   - Acciones
                 * =====================================================
                 */
                foreach ($encabezado->getIndicadores() as $indicador) {
                    $indicador->setEncabezado($encabezado);
                }

                foreach ($encabezado->getAcciones() as $accion) {
                    $accion->setEncabezado($encabezado);
                }

                /**
                 * =====================================================
                 * REAFIRMAR RESPONSABLE PRINCIPAL
                 * -----------------------------------------------------
                 * - Se vuelve a asignar por seguridad
                 * - Garantiza que el PTA quede ligado al creador
                 * =====================================================
                 */
                $usuario = $this->getUser();
                if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
                    $encabezado->setResponsable($usuario->getPersonal());
                }

                /**
                 * =====================================================
                 * PERSISTENCIA FINAL
                 * -----------------------------------------------------
                 * - Persistimos solo el Encabezado
                 * - Las relaciones se guardan por cascade
                 * =====================================================
                 */
                $entityManager->persist($encabezado);
                $entityManager->flush();

                /**
                 * =====================================================
                 * REDIRECCIÓN POST-GUARDADO
                 * -----------------------------------------------------
                 * - Se regresa al index con todos los PTAs
                 * =====================================================
                 */
                return $this->render('admin/encabezado/index.html.twig', [
                    'encabezados' => $encabezadoRepository->findAll(),
                ]);
            }

            /**
             * =========================================================
             * RENDER DE LA VISTA NEW (GET o FORM INVÁLIDO)
             * =========================================================
             */
            return $this->render('admin/encabezado/new.html.twig', [
                'encabezado' => $encabezado,
                'form' => $form,
            ]);
        }


    #[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
    public function show(Encabezado $encabezado): Response
    {
        return $this->render('admin/encabezado/show.html.twig', [
            'encabezado' => $encabezado,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager, EncabezadoRepository $encabezadoRepository): Response
    {
        if ($encabezado->getResponsables() === null) {
            $encabezado->setResponsables(new \App\Entity\Responsables());
        }

        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔥 asegurar relación padre → hijos
            foreach ($encabezado->getIndicadores() as $indicador) {
                $indicador->setEncabezado($encabezado);
            }

            foreach ($encabezado->getAcciones() as $accion) {
                $accion->setEncabezado($encabezado);
            }
            $entityManager->persist($encabezado);
            $entityManager->flush();


            return $this->render('admin/encabezado/index.html.twig', [
            'encabezados' => $encabezadoRepository->findAll(),
        ]);
        }

        return $this->render('admin/encabezado/edit.html.twig', [
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
