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
use App\Entity\HistorialAcciones;
use App\Entity\HistorialAccionesAtrasos;


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
    $puesto   = $personal?->getPuesto();

    $access = $ptaAccessResolver->resolve($usuario);

    /* =====================================================
     * 3. FILTROS REQUEST
     * ===================================================== */
    $filters = [
        'anio'         => $anioEjecucion,
        'puesto'       => $request->query->get('puesto'),
        'departamento' => $request->query->get('departamento'),
        'personal_id'  => $personal?->getId(),
        'puesto_id'    => $puesto?->getId(),
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
        $personal?->getId() ?? 0,
        $puesto?->getId()
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

    /* =====================================================
     * 7. RENDER
     * ===================================================== */
    return $this->render('pta/encabezado/index.html.twig', [
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
            

$isTurbo = $request->headers->has('Turbo-Frame');

if ($isTurbo) {
    return $this->render('pta/encabezado/new_page.html.twig', [
        'encabezado'  => $encabezado,
        'form'        => $form->createView(),
        'volver_path' => $this->generateUrl('app_encabezado_index', [
            'anio' => $anioEjecucion,
        ]),
    ]);
}

return $this->render('dashboard/index.html.twig', [
    'section'     => 'pta',
    'content_url' => $this->generateUrl('app_encabezado_new', [
        'anio' => $anioEjecucion,
    ]),
]);



        }




#[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
public function show(
    Request $request,
    Encabezado $encabezado
): Response {
    // Filtros (solo relevantes para index)
    $filtros = [
        'anio'         => $request->query->get('anio'),
        'departamento' => $request->query->get('departamento'),
        'puesto'       => $request->query->get('puesto'),
    ];

    // Origen de navegación
    $from = $request->query->get('from', 'index');

    $isTurbo = $request->headers->has('Turbo-Frame');

if ($isTurbo) {
    return $this->render('pta/encabezado/show_page.html.twig', [
        'encabezado' => $encabezado,
        'filtros'    => $filtros,
        'from'       => $from,
    ]);
}


return $this->render('dashboard/index.html.twig', [
    'section'     => 'pta',
    'content_url' => $this->generateUrl('app_encabezado_show', [
        'id' => $encabezado->getId(),
    ] + array_filter($filtros)),
]);

}





    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    Encabezado $encabezado,
    EntityManagerInterface $entityManager,
    EncabezadoRepository $encabezadoRepository
): Response {

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
     * MAPEO MESES (ES)
     * =====================================================
     */
    $mesesES = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * =====================================================
     * POST — Guardado de avances
     * =====================================================
     */
    if ($request->isMethod('POST')) {

        // 1) Año de ejecución (para regresar con filtros)
        $anioSistema   = (int) date('Y');
        $anioEjecucion = $request->query->getInt('anio', $anioSistema);

        // 2) Preservar filtros
        $departamentoSeleccionado = $request->query->get('departamento');
        $puestoSeleccionado       = $request->query->get('puesto');

        // 3) Validación CSRF
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

        // 4) Valores enviados
        $valoresAlcanzados = $request->request->all('valor_alcanzado');
        $atrasos = $request->request->all('atrasos'); // puede venir null

        // 5) Fecha y mes actuales
        $fechaActual     = new \DateTimeImmutable();
        $anioActual      = (int) $fechaActual->format('Y');
        //$mesActualNumero = (int) date('n');
        $mesActualNumero = 5; // SIMULAMOS MARZO// 1-12

        // ⚠️ Seguridad extra: solo guardar si coincide el año del encabezado
        if ($anioActual !== (int) $encabezado->getAnioEjecucion()) {
            return $this->redirectToRoute('app_encabezado_index', [
                'anio'         => $anioEjecucion,
                'departamento' => $departamentoSeleccionado,
                'puesto'       => $puestoSeleccionado,
            ]);
        }

        /**
         * =====================================================
         * 6) Guardar avances por acción
         * -----------------------------------------------------
         * PASO 1:
         * - Permitir MES ACTUAL y MESES PASADOS
         * - Bloquear MESES FUTUROS
         * - Ignorar meses fuera del periodo de la acción
         * =====================================================
         */
        foreach ($encabezado->getAcciones() as $accion) {

            $accionId = $accion->getId();

            if (!isset($valoresAlcanzados[$accionId])) {
                continue;
            }

            // Lo que viene del form (solo meses con inputs)
            $mesesForm = $valoresAlcanzados[$accionId];

            // Tomar lo ya guardado para no perder meses no enviados
            $valoresActuales = $accion->getValorAlcanzado() ?? [];

            foreach ($mesesForm as $mes => $valor) {

                // Convertir mes string → número (1-12)
                $mesNumero = array_search($mes, $mesesES, true);

                // ❌ Mes inválido
                if ($mesNumero === false) {
                    continue;
                }

                // ❌ Mes FUTURO → bloquear
                if ($mesNumero > $mesActualNumero) {
                    continue;
                }

                // ❌ Mes NO contemplado en el periodo de la acción → ignorar
                if (!in_array($mes, $accion->getPeriodo(), true)) {
                    continue;
                }

                // Normalización de valor vacío
                if ($valor === '') {
                    $valor = null;
                }

                // ✅ Mes actual o pasado → permitido
                $valoresActuales[$mes] = $valor;
                $fechaEvento = new \DateTimeImmutable();

// ===============================
// HISTORIAL
// ===============================

// 👉 MES PASADO = ATRASO
if ($mesNumero < $mesActualNumero) {

    $motivo = $atrasos[$accionId][$mes]['motivo'] ?? null;

    if ($motivo !== null) {
        $histAtraso = new HistorialAccionesAtrasos();
        $histAtraso->setAccion($accion);
        $histAtraso->setMes($mesNumero);
        $histAtraso->setValor((int) $valor);
        $histAtraso->setMotivo($motivo);
        $histAtraso->setFecha($fechaEvento);

        $entityManager->persist($histAtraso);
    }

}
// 👉 MES ACTUAL = HISTORIAL NORMAL
elseif ($mesNumero === $mesActualNumero) {

    // ❗ SOLO guardar historial si el usuario capturó algo
    if ($valor !== null && $valor !== '') {

        $hist = new HistorialAcciones();
        $hist->setAccion($accion);
        $hist->setMes($mesNumero);
        $hist->setValor((int) $valor);
        $hist->setFecha($fechaEvento);

        $entityManager->persist($hist);
    }
}


            }

            $accion->setValorAlcanzado($valoresActuales);
        }

        // 7) Persistir
        $entityManager->flush();

        // 8) Regresar al index con filtros
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

$fechaActual = new \DateTimeImmutable();
$mesActual   = $mesesES[5]; // simulación

$isTurbo = $request->headers->has('Turbo-Frame');

if ($isTurbo) {
    return $this->render('pta/encabezado/edit_page.html.twig', [
        'encabezado'  => $encabezado,
        'filtros'     => $filtros,
        'mesActual'   => $mesActual,
        'volver_path' => $this->generateUrl('app_encabezado_show', [
            'id' => $encabezado->getId(),
        ] + array_filter($filtros)),
    ]);
}

return $this->render('dashboard/index.html.twig', [
    'section'     => 'pta',
    'content_url' => $this->generateUrl('app_encabezado_edit', [
        'id' => $encabezado->getId(),
    ] + array_filter($filtros)),
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
public function graficas(
    Request $request,
    Encabezado $encabezado,
    \App\Service\Pta\PtaGraficaService $ptaGraficaService
): Response {

    /* =====================================================
     * CONTEXTO (MISMO PATRÓN QUE SHOW)
     * ===================================================== */
    $filtros = [
        'anio'         => $request->query->get('anio'),
        'departamento' => $request->query->get('departamento'),
        'puesto'       => $request->query->get('puesto'),
    ];

    // Origen de navegación (igual que show)
    $from = $request->query->get('from', 'show');

    /* =====================================================
     * GRÁFICAS
     * ===================================================== */
    $graficas = $ptaGraficaService->build($encabezado);

    /* =====================================================
     * RENDER
     * ===================================================== */
    $isTurbo = $request->headers->has('Turbo-Frame');

    if ($isTurbo) {
        return $this->render('pta/encabezado/graficas_page.html.twig', [
            'encabezado'  => $encabezado,
            'graficas'    => $graficas,
            'filtros'     => $filtros,
            'from'        => $from,
            'volver_path' => $this->generateUrl('app_encabezado_show', [
                'id'   => $encabezado->getId(),
                'from' => $from,
            ] + array_filter($filtros)),
        ]);
    }

    return $this->render('dashboard/index.html.twig', [
        'section'     => 'pta',
        'content_url' => $this->generateUrl('app_encabezado_graficas', [
            'id'   => $encabezado->getId(),
            'from' => $from,
        ] + array_filter($filtros)),
    ]);
}


}
