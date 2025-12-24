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
final class AdminEncabezadoController extends AbstractController
{
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
public function index(
    Request $request,
    EncabezadoRepository $encabezadoRepository
): Response
{
    // Año actual por defecto
    $anioActual = (int) date('Y');

    // Año de ejecución seleccionado (GET) o año actual
    $anioEjecucion = $request->query->getInt('anio', $anioActual);

    $encabezados = $encabezadoRepository->createQueryBuilder('e')
        ->andWhere('e.anioEjecucion = :anio')
        ->setParameter('anio', $anioEjecucion)
        ->orderBy('e.fechaCreacion', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('admin/encabezado/index.html.twig', [
        'encabezados' => $encabezados,
        'anioSeleccionado' => $anioEjecucion,
    ]);
}



    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
        public function new(
            Request $request,
            EntityManagerInterface $entityManager,
            EncabezadoRepository $encabezadoRepository
        ): Response
        {
            // Año actual por defecto
            $anioActual = (int) date('Y');

            // Año de ejecución seleccionado (GET) o año actual
            $anioEjecucion = $request->query->getInt('anio', $anioActual);

            $encabezados = $encabezadoRepository->createQueryBuilder('e')
                ->andWhere('e.anioEjecucion = :anio')
                ->setParameter('anio', $anioEjecucion)
                ->orderBy('e.fechaCreacion', 'DESC')
                ->getQuery()
                ->getResult();
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
                    'encabezados' => $encabezados,
                    'anioSeleccionado' => $anioEjecucion,
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
        if ($request->isMethod('POST')) {
            // Año actual por defecto
    $anioActual = (int) date('Y');

    // Año de ejecución seleccionado (GET) o año actual
    $anioEjecucion = $request->query->getInt('anio', $anioActual);

    $encabezados = $encabezadoRepository->createQueryBuilder('e')
        ->andWhere('e.anioEjecucion = :anio')
        ->setParameter('anio', $anioEjecucion)
        ->orderBy('e.fechaCreacion', 'DESC')
        ->getQuery()
        ->getResult();

    // 1. Validar CSRF
    if (!$this->isCsrfTokenValid('edit' . $encabezado->getId(), $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF inválido');
    }

    // 2. Obtener valores alcanzados
    $valoresAlcanzados = $request->request->all('valor_alcanzado');

    // 3. Recorrer acciones del encabezado
    foreach ($encabezado->getAcciones() as $accion) {

        $accionId = $accion->getId();

        if (!isset($valoresAlcanzados[$accionId])) {
            continue;
        }
        $meses = $valoresAlcanzados[$accionId];
        // 4. Limpieza opcional: convertir "" a null
        foreach ($meses as $mes => $valor) {
            if ($valor === '') {
                $meses[$mes] = null;
            }
        }
        // 5. Guardar JSON en la acción
        $accion->setValorAlcanzado($meses);
    }
    // 6. Persistir cambios
    $entityManager->flush();

    // 7. Redirigir (POST/REDIRECT/GET)
    return $this->render('admin/encabezado/index.html.twig', [
        'encabezados' => $encabezados,
        'anioSeleccionado' => $anioEjecucion,
    ]);
}


        if ($encabezado->getResponsables() === null) {
            $encabezado->setResponsables(new \App\Entity\Responsables());
        }

        return $this->render('admin/encabezado/edit.html.twig', [
            'encabezado' => $encabezado,
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

    #[Route('/graficas/{id}', name: 'app_encabezado_graficas', methods: ['GET'])]
    public function graficas(Encabezado $encabezado): Response
    {
        // Orden fijo de meses
        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        $graficas = [];
        $acciones = $encabezado->getAcciones();

        foreach ($encabezado->getIndicadores() as $indicador) {

            // ===============================
            // 1) SUMA MENSUAL REAL (BASE)
            // ===============================
            $valoresMensuales = array_fill_keys($meses, 0);

            foreach ($acciones as $accion) {
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];

                foreach ($meses as $mes) {
                    if (isset($valoresAccion[$mes])) {
                        $valoresMensuales[$mes] += (float) $valoresAccion[$mes];
                    }
                }
            }

            $meta      = (float) $indicador->getValor();
            $tendencia = $indicador->getTendencia();

            // ===============================
            // 2) SERIE FINAL PARA LA GRÁFICA
            // ===============================
            $serie = [];
            $ultimoValor = 0;

            if ($tendencia === 'POSITIVA') {

                $acumulado = 0;
                foreach ($meses as $mes) {
                    $acumulado += $valoresMensuales[$mes];
                    $serie[$mes] = $acumulado;
                }

                $avanceFinal = end($serie);
                $porcentaje = ($meta > 0)
                    ? round(($avanceFinal / $meta) * 100, 1)
                    : 0;

            } else {

                // NEGATIVA → valores reales (sin acumulado)
                foreach ($meses as $mes) {
                    if ($valoresMensuales[$mes] > 0) {
                        $ultimoValor = $valoresMensuales[$mes];
                    }
                    $serie[$mes] = $ultimoValor;
                }

                $avanceFinal = end($serie);

                // En tendencia negativa: llegar o bajar a la meta = 100%
                if ($meta > 0 && $avanceFinal > 0) {
                    $porcentaje = ($avanceFinal <= $meta)
                        ? 100
                        : round(($meta / $avanceFinal) * 100, 1);
                } else {
                    $porcentaje = 0;
                }
            }

            // Limitar porcentaje
            $porcentaje = max(0, min(100, $porcentaje));

            // ===============================
            // 3) RESUMEN POR ACCIONES
            // ===============================
            $accionesResumen = [];
            $totalIndicador = array_sum($valoresMensuales);

            foreach ($acciones as $accion) {
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];
                $totalAccion = 0;
                $mesesAccion = [];

                foreach ($meses as $mes) {
                    $valor = (float) ($valoresAccion[$mes] ?? 0);
                    $mesesAccion[$mes] = $valor;
                    $totalAccion += $valor;
                }

                $porcentajeAccion = $totalIndicador > 0
                    ? round(($totalAccion / $totalIndicador) * 100, 1)
                    : 0;

                $accionesResumen[] = [
                    'nombre'     => $accion->getAccion(),
                    'meses'      => $mesesAccion,
                    'total'      => $totalAccion,
                    'porcentaje' => $porcentajeAccion,
                ];
            }

            // ===============================
            // 4) ARMADO FINAL
            // ===============================
            $graficas[] = [
                'indicador'  => $indicador->getIndicador(),
                'meta'       => $meta,
                'tendencia'  => $tendencia,
                'meses'      => $serie,          // 👈 serie FINAL
                'porcentaje' => $porcentaje,
                'acciones'   => $accionesResumen
            ];
        }

        return $this->render('admin/encabezado/graficas.html.twig', [
            'encabezado' => $encabezado,
            'graficas'   => $graficas,
        ]);
    }


}
