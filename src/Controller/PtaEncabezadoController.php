<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Form\EncabezadoType;
use App\Repository\DepartamentoRepository;
use App\Repository\EncabezadoRepository;
use App\Repository\PuestoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

    #[Route('/pta/encabezado')]
    final class PtaEncabezadoController extends AbstractController
    {
        

    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
public function index(
    Request $request,
    EncabezadoRepository $encabezadoRepository,
    PuestoRepository $puestoRepository,
    \App\Service\Pta\PtaAccessResolver $ptaAccessResolver
): Response {

    /* =====================================================
     * 1. AÑO ACTUAL (DEFAULT)
     * ===================================================== */
    $anioActual    = (int) date('Y');
    $anioEjecucion = $request->query->getInt('anio', $anioActual);

    /* =====================================================
     * 2. USUARIO + ACCESO
     * ===================================================== */
    /** @var \App\Entity\User $usuario */
    $usuario  = $this->getUser();
    $personal = $usuario->getPersonal();

    $access = $ptaAccessResolver->resolve($usuario);

    /* =====================================================
     * 3. FILTROS REQUEST
     * ===================================================== */
    $filters = [
        'anio'         => $anioEjecucion,
        'puesto'       => $request->query->get('puesto'),
        'departamento' => $request->query->get('departamento'),
        'personal_id'  => $personal?->getId(),
    ];

    /* =====================================================
     * 4. PTA VISIBLES
     * ===================================================== */
    $encabezados = $encabezadoRepository->findVisiblePta($access, $filters);

    /* =====================================================
     * 5. FILTROS DISPONIBLES
     * ===================================================== */

    // ---------- AÑOS ----------
    $aniosFiltro = $encabezadoRepository->findAniosDisponibles(
        $access,
        $personal?->getId() ?? 0
    );

    // ---------- PUESTOS ----------
    $puestosFiltro = [];
    if ($access['filters']['puesto']) {

        $puestos = $access['scope'] === 'GLOBAL'
            ? $puestoRepository->findBy(['activo' => true], ['nombre' => 'ASC'])
            : $puestoRepository->findBy(['id' => $access['puestos_visibles']], ['nombre' => 'ASC']);

        foreach ($puestos as $p) {
            $puestosFiltro[$p->getId()] = $p->getNombre();
        }
    }

    // ---------- DEPARTAMENTOS ----------
    $departamentosFiltro = [];
    if ($access['filters']['departamento']) {

        $departamentos = $access['scope'] === 'GLOBAL'
            ? $puestoRepository->findBy(['activo' => true], ['nombre' => 'ASC'])
            : $puestoRepository->findBy(['id' => $access['departamentos_visibles']], ['nombre' => 'ASC']);

        foreach ($departamentos as $d) {
            if ($d->getSubordinados()->count() > 0) {
                $departamentosFiltro[$d->getId()] = $d->getNombre();
            }
        }
    }

    /* =====================================================
     * 6. VISTA
     * ===================================================== */
    $view = in_array('ROLE_ADMIN', $usuario->getRoles(), true)
        ? 'pta/encabezado/index.html.twig'
        : 'pta/encabezado/indexGeneral.html.twig';

    /* =====================================================
     * 7. RENDER
     * ===================================================== */
    return $this->render($view, [
        'encabezados'         => $encabezados,
        'anioSeleccionado'    => $anioEjecucion,
        'aniosFiltro'         => $aniosFiltro,
        'access'              => $access,
        'puestosFiltro'       => $puestosFiltro,
        'departamentosFiltro' => $departamentosFiltro,
        'filtrosActivos' => [
            'puesto'       => $filters['puesto'],
            'departamento' => $filters['departamento'],
        ],
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
           /** @var User $usuario */
            $usuario = $this->getUser();

            if ($usuario instanceof User && $usuario->getPersonal()) {
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
                /** @var User $usuario */
                    $usuario = $this->getUser();

                    if ($usuario instanceof User && $usuario->getPersonal()) {
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
                return $this->redirectToRoute(
                    'app_encabezado_index',
                ['anio' => $anioEjecucion]
                );

            }

            /**
             * =========================================================
             * RENDER DE LA VISTA NEW (GET o FORM INVÁLIDO)
             * =========================================================
             */
            $isTurbo = $request->headers->get('Turbo-Frame');

if ($isTurbo) {
    // Navegación desde dashboard (Turbo)
    return $this->render('pta/encabezado/new.html.twig', [
        'encabezado' => $encabezado,
        'form' => $form,
    ]);
}

// NO Turbo → acceso directo o F5
if ($this->isGranted('ROLE_ADMIN')) {
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => 'pta',
        'content_url' => $this->generateUrl('app_encabezado_new', [
            'anio' => $anioEjecucion,
        ]),
    ]);
}

// Usuario normal → layout completo
return $this->render('pta/encabezado/new.html.twig', [
    'encabezado' => $encabezado,
    'form' => $form,
]);

        }




#[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
public function show(
    Request $request,
    Encabezado $encabezado
): Response {

    // Preservar filtros actuales
    $filtros = [
        'anio'         => $request->query->get('anio'),
        'departamento' => $request->query->get('departamento'),
        'puesto'       => $request->query->get('puesto'),
    ];

    $isTurbo = $request->headers->get('Turbo-Frame');

    // ============================
    // Turbo → solo fragmento
    // ============================
    if ($isTurbo) {
        return $this->render('pta/encabezado/show.html.twig', [
            'encabezado' => $encabezado,
            'filtros'    => $filtros,
        ]);
    }

    // ============================
    // Admin sin Turbo → dashboard
    // ============================
    if ($this->isGranted('ROLE_ADMIN')) {
        return $this->render('admin/dashboard/index.html.twig', [
            'section' => 'pta',
            'content_url' => $this->generateUrl('app_encabezado_show', [
                'id' => $encabezado->getId(),
            ] + $filtros),
        ]);
    }

    // ============================
    // Usuario normal → layout base
    // ============================
    return $this->render('pta/encabezado/showGeneral.html.twig', [
        'encabezado' => $encabezado,
        'filtros'    => $filtros,
    ]);
}




    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    Encabezado $encabezado,
    EntityManagerInterface $entityManager,
    EncabezadoRepository $encabezadoRepository
): Response
{
    /**
     * =====================================================
     * SEGURIDAD — CAPTURA DE AVANCES
     * -----------------------------------------------------
     * SOLO el responsable del PTA puede capturar avances
     * El rol (ADMIN o no) NO importa aquí
     * =====================================================
     */
    $user = $this->getUser();
    $responsable = $encabezado->getResponsable();

    if (!$responsable || $responsable->getUser() !== $user) {
        throw $this->createAccessDeniedException(
            'No puedes capturar avances de un PTA que no es tuyo.'
        );
    }

    /**
     * =====================================================
     * BLOQUE POST
     * =====================================================
     */
    if ($request->isMethod('POST')) {

        // 1. Año de ejecución
        $anioActual = (int) date('Y');
        $anioEjecucion = $request->query->getInt('anio', $anioActual);

        // 2. Preservar filtros
        $departamentoSeleccionado = $request->query->get('departamento');
        $puestoSeleccionado       = $request->query->get('puesto');

        // 3. Validación CSRF
        if (
            !$this->isCsrfTokenValid(
                'edit' . $encabezado->getId(),
                $request->request->get('_token')
            )
        ) {
            return $this->redirectToRoute('app_encabezado_index', [
                'anio'         => $anioEjecucion,
                'departamento' => $departamentoSeleccionado,
                'puesto'       => $puestoSeleccionado,
            ]);
        }

        // 4. Valores enviados
        $valoresAlcanzados = $request->request->all('valor_alcanzado');

        // 5. Guardar avances por acción
        foreach ($encabezado->getAcciones() as $accion) {

            $accionId = $accion->getId();

            if (!isset($valoresAlcanzados[$accionId])) {
                continue;
            }

            $meses = $valoresAlcanzados[$accionId];

            foreach ($meses as $mes => $valor) {
                if ($valor === '') {
                    $meses[$mes] = null;
                }
            }

            $accion->setValorAlcanzado($meses);
        }

        // 6. Persistir
        $entityManager->flush();

        // 7. Regresar al index con filtros
        return $this->redirectToRoute('app_encabezado_index', [
            'anio'         => $anioEjecucion,
            'departamento' => $departamentoSeleccionado,
            'puesto'       => $puestoSeleccionado,
        ]);
    }

    /**
     * =====================================================
     * GET — Render de la vista
     * =====================================================
     */
    $filtros = [
        'anio'         => $request->query->get('anio'),
        'departamento' => $request->query->get('departamento'),
        'puesto'       => $request->query->get('puesto'),
    ];

    $isTurbo = $request->headers->get('Turbo-Frame');

if ($isTurbo) {
    return $this->render('pta/encabezado/edit.html.twig', [
        'encabezado' => $encabezado,
        'filtros'    => $filtros,
    ]);
}

if ($this->isGranted('ROLE_ADMIN')) {
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => 'pta',
        'content_url' => $this->generateUrl('app_encabezado_edit', [
            'id' => $encabezado->getId(),
        ] + $filtros),
    ]);
}

return $this->render('pta/encabezado/editGeneral.html.twig', [
    'encabezado' => $encabezado,
    'filtros'    => $filtros,
]);

}


    #[Route('/{id}/delete', name: 'app_encabezado_delete', methods: ['POST'])]
    public function delete(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$encabezado->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($encabezado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/graficas/{id}', name: 'app_encabezado_graficas', methods: ['GET'])]
    public function graficas(Request $request,Encabezado $encabezado): Response
    {
        /**
         * =====================================================
         * MÓDULO GRÁFICAS PTA
         * -----------------------------------------------------
         * Este método construye TODA la información necesaria
         * para la visualización gráfica de un PTA.
         *
         * Reglas clave:
         * - TODOS los cálculos se hacen en PHP
         * - El frontend (JS) solo dibuja
         * - La vista recibe datos ya procesados
         * =====================================================
         */

        /**
         * -----------------------------------------------------
         * ORDEN FIJO DE MESES
         * -----------------------------------------------------
         * Se define explícitamente el orden de los meses
         * para:
         * - Mantener consistencia visual
         * - Evitar dependencias del orden en base de datos
         * - Garantizar coherencia entre series
         */
        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        /**
         * Arreglo final que contendrá TODAS las gráficas
         * (una por indicador)
         */
        $graficas = [];

        /**
         * Acciones asociadas al encabezado
         * Se reutilizan varias veces para:
         * - Sumar valores
         * - Generar resúmenes
         */
        $acciones = $encabezado->getAcciones();

        /**
         * =====================================================
         * RECORRIDO PRINCIPAL POR INDICADORES
         * =====================================================
         * Cada indicador genera:
         * - Una serie mensual
         * - Un porcentaje de avance
         * - Un resumen por acciones
         */
        foreach ($encabezado->getIndicadores() as $indicador) {

            /**
             * =================================================
             * 1) SUMA MENSUAL REAL (BASE)
             * =================================================
             * Se calcula la suma REAL mensual considerando
             * todas las acciones que pertenecen al indicador.
             *
             * Resultado:
             * [
             *   'Enero' => total,
             *   'Febrero' => total,
             *   ...
             * ]
             */
            $valoresMensuales = array_fill_keys($meses, 0);

            foreach ($acciones as $accion) {

                // Ignorar acciones que no pertenecen al indicador
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                // Valores alcanzados por mes (JSON)
                $valoresAccion = $accion->getValorAlcanzado() ?? [];

                // Acumulación mensual
                foreach ($meses as $mes) {
                    if (isset($valoresAccion[$mes])) {
                        $valoresMensuales[$mes] += (float) $valoresAccion[$mes];
                    }
                }
            }

            /**
             * Meta y tipo de tendencia del indicador
             */
            $meta      = (float) $indicador->getValor();
            $tendencia = $indicador->getTendencia();

            /**
             * =================================================
             * 2) CONSTRUCCIÓN DE LA SERIE FINAL
             * =================================================
             * La serie depende del tipo de tendencia:
             *
             * POSITIVA:
             * - Acumulado progresivo
             *
             * NEGATIVA:
             * - Valor real más reciente
             * - Mientras no haya valor, se conserva el último
             */
            $serie = [];
            $ultimoValor = 0;

            if ($tendencia === 'POSITIVA') {

    $acumulado = 0;
    $serieTemp = [];

    foreach ($meses as $mes) {
        $acumulado += $valoresMensuales[$mes];
        $serieTemp[$mes] = $acumulado;
    }

    /**
     * 🎯 AJUSTE VISUAL:
     * Si Enero tiene valor > 0, forzamos un arranque en 0
     * para que la gráfica "nazca" desde cero.
     */
    $primerMes = $meses[0];
    if ($serieTemp[$primerMes] > 0) {
        $serie = [
            $primerMes . ' (inicio)' => 0,
        ] + $serieTemp;
    } else {
        $serie = $serieTemp;
    }

    $avanceFinal = end($serieTemp);
    $porcentaje = ($meta > 0)
        ? round(($avanceFinal / $meta) * 100, 1)
        : 0;
}
else {

                // Tendencia negativa → valor real (no acumulado)
                foreach ($meses as $mes) {
                    if ($valoresMensuales[$mes] > 0) {
                        $ultimoValor = $valoresMensuales[$mes];
                    }
                    $serie[$mes] = $ultimoValor;
                }

                $avanceFinal = end($serie);

                /**
                 * En tendencia negativa:
                 * - Llegar o bajar a la meta = 100%
                 * - Si se excede, el porcentaje disminuye
                 */
                if ($meta > 0 && $avanceFinal > 0) {
                    $porcentaje = ($avanceFinal <= $meta)
                        ? 100
                        : round(($meta / $avanceFinal) * 100, 1);
                } else {
                    $porcentaje = 0;
                }
            }

            /**
             * Limitar porcentaje a rango válido [0, 100]
             */
            $porcentaje = max(0, min(100, $porcentaje));

            /**
             * =================================================
             * 3) RESUMEN POR ACCIONES
             * =================================================
             * Se calcula:
             * - Total por acción
             * - Distribución porcentual dentro del indicador
             * - Detalle mensual por acción
             */
            $accionesResumen = [];
            $totalIndicador = array_sum($valoresMensuales);

            foreach ($acciones as $accion) {

                // Filtrar solo acciones del indicador
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

                // Porcentaje de contribución de la acción
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

            /**
             * =================================================
             * 4) ARMADO FINAL DE LA GRÁFICA
             * =================================================
             * Este arreglo es consumido directamente
             * por la vista y el JS de Chart.js
             */
            $graficas[] = [
                'indicador'  => $indicador->getIndicador(),
                'meta'       => $meta,
                'tendencia'  => $tendencia,
                'meses'      => $serie,        // Serie FINAL
                'porcentaje' => $porcentaje,   // Avance global
                'acciones'   => $accionesResumen
            ];
        }

        /**
         * Render de la vista de gráficas
         */
        $filtros = [
    'anio'         => $request->query->get('anio'),
    'departamento' => $request->query->get('departamento'),
    'puesto'       => $request->query->get('puesto'),
];

$isTurbo = $request->headers->get('Turbo-Frame');

if ($isTurbo) {
    return $this->render('pta/encabezado/graficas.html.twig', [
        'encabezado' => $encabezado,
        'graficas'   => $graficas,
        'filtros'    => $filtros,
    ]);
}

if ($this->isGranted('ROLE_ADMIN')) {
    return $this->render('admin/dashboard/index.html.twig', [
        'section' => 'pta',
        'content_url' => $this->generateUrl('app_encabezado_graficas', [
            'id' => $encabezado->getId(),
        ] + $filtros),
    ]);
}

return $this->render('pta/encabezado/graficasGeneral.html.twig', [
    'encabezado' => $encabezado,
    'graficas'   => $graficas,
    'filtros'    => $filtros,
]);

    }

}
