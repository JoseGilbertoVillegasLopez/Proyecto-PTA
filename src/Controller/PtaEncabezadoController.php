<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\HistorialAcciones;
use App\Entity\HistorialIndicadorValor;
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
    /* =========================================================
     * INDEX — Listado de PTAs visibles para el usuario
     * ========================================================= */
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
    public function index(
        Request $request,
        EncabezadoRepository $encabezadoRepository,
        PuestoRepository $puestoRepository,
        \App\Service\Pta\PtaAccessResolver $ptaAccessResolver
    ): Response {

        $anioActual    = (int) date('Y');
        $anioEjecucion = $request->query->getInt('anio', $anioActual);

        /** @var \App\Entity\User $usuario */
        $usuario  = $this->getUser();
        $personal = $usuario->getPersonal();
        $puesto   = $personal?->getPuesto();

        $access = $ptaAccessResolver->resolve($usuario);

        $filters = [
            'anio'         => $anioEjecucion,
            'puesto'       => $request->query->get('puesto'),
            'departamento' => $request->query->get('departamento'),
            'personal_id'  => $personal?->getId(),
            'puesto_id'    => $puesto?->getId(),
        ];

        $encabezados = $encabezadoRepository->findVisiblePta($access, $filters);

        $aniosFiltro = $encabezadoRepository->findAniosDisponibles(
            $access,
            $personal?->getId() ?? 0,
            $puesto?->getId()
        );

        $puestosFiltro = [];
        if ($access['filters']['puesto']) {
            $puestos = $access['scope'] === 'GLOBAL'
                ? $puestoRepository->findBy(['activo' => true], ['nombre' => 'ASC'])
                : $puestoRepository->findBy(['id' => $access['puestos_visibles']], ['nombre' => 'ASC']);

            foreach ($puestos as $p) {
                $puestosFiltro[$p->getId()] = $p->getNombre();
            }
        }

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

    /* =========================================================
     * NEW — Crear un PTA
     * ========================================================= */
    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        EncabezadoRepository $encabezadoRepository
    ): Response {
        $anioActual    = (int) date('Y');
        $anioEjecucion = $request->query->getInt('anio', $anioActual);

        $encabezados = $encabezadoRepository->createQueryBuilder('e')
            ->andWhere('e.anioEjecucion = :anio')
            ->setParameter('anio', $anioEjecucion)
            ->orderBy('e.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();

        $encabezado = new Encabezado();

        // Responsables es OneToOne y sus campos son mapped=false;
        // se inicializa aquí para que el FormType lo tenga disponible.
        $responsables = new \App\Entity\Responsables();
        $encabezado->setResponsables($responsables);

        /** @var User $usuario */
        $usuario = $this->getUser();
        if ($usuario instanceof User && $usuario->getPersonal()) {
            $encabezado->setResponsable($usuario->getPersonal());
        }

        $form = $this->createForm(EncabezadoType::class, $encabezado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Supervisor y Aval vienen como IDs en campos hidden (mapped=false)
            $responsables = $encabezado->getResponsables();
            if ($responsables) {
                $data = $request->request->all('encabezado');

                $supervisorId = $data['responsables']['supervisor'] ?? null;
                $avalId       = $data['responsables']['aval'] ?? null;

                if ($supervisorId) {
                    $supervisor = $entityManager->getRepository(Personal::class)->find($supervisorId);
                    $responsables->setSupervisor($supervisor);
                }

                if ($avalId) {
                    $aval = $entityManager->getRepository(Personal::class)->find($avalId);
                    $responsables->setAval($aval);
                }
            }

            $encabezado->setFechaCreacion(new \DateTime());
            $encabezado->setStatus(true);

            // Doctrine necesita la referencia explícita al padre en colecciones
            foreach ($encabezado->getIndicadores() as $indicador) {
                $indicador->setEncabezado($encabezado);
            }
            foreach ($encabezado->getAcciones() as $accion) {
                $accion->setEncabezado($encabezado);
            }

            // Reafirmar responsable por seguridad (evita que el form lo sobreescriba)
            /** @var User $usuario */
            $usuario = $this->getUser();
            if ($usuario instanceof User && $usuario->getPersonal()) {
                $encabezado->setResponsable($usuario->getPersonal());
            }

            $entityManager->persist($encabezado);
            $entityManager->flush();

            return $this->redirectToRoute('app_encabezado_index', ['anio' => $anioEjecucion]);
        }

        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/encabezado/new_page.html.twig', [
                'encabezado'  => $encabezado,
                'form'        => $form->createView(),
                'volver_path' => $this->generateUrl('app_encabezado_index', ['anio' => $anioEjecucion]),
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'pta',
            'content_url' => $this->generateUrl('app_encabezado_new', ['anio' => $anioEjecucion]),
        ]);
    }

    /* =========================================================
     * SHOW — Ver detalle de un PTA (solo lectura)
     * ========================================================= */
    #[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
    public function show(Request $request, Encabezado $encabezado): Response
    {
        $filtros = [
            'anio'         => $request->query->get('anio'),
            'departamento' => $request->query->get('departamento'),
            'puesto'       => $request->query->get('puesto'),
        ];

        $from    = $request->query->get('from', 'index');
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

    /* =========================================================
     * EDIT — Captura de avances del PTA
     * ---------------------------------------------------------
     * Solo el responsable directo del PTA puede acceder.
     *
     * El formulario tiene DOS secciones editables:
     *
     * 1. ACCIONES: marca ✓/✗ por mes del periodo de cada acción.
     *    POST: meses_cumplidos[accionId][nombreMes] = "1"|"0"
     *    POST: motivos_accion[accionId][nombreMes]  = "texto motivo"
     *
     * 2. INDICADORES: valor acumulado snapshot por mes reportable.
     *    Los meses reportables = unión de periodos de las acciones.
     *    POST: valor_indicador[indicadorId][nombreMes] = "600"
     *    POST: motivos_indicador[indicadorId][nombreMes] = "texto motivo"
     *
     * REGLAS DE HISTORIAL:
     *   Acción + mes actual + ✓       → HistorialAcciones(valor=1, motivo=null)
     *   Acción + mes actual + ✗       → HistorialAcciones(valor=0, motivo=requerido)
     *   Acción + mes pasado + cualquier → HistorialAcciones(valor=0|1, motivo=requerido)
     *   Indicador + mes actual         → HistorialIndicadorValor(motivo=null)
     *   Indicador + mes pasado         → HistorialIndicadorValor(motivo=requerido)
     * ========================================================= */
    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Encabezado $encabezado,
        EntityManagerInterface $entityManager
    ): Response {

        /* =====================================================
         * SEGURIDAD: solo el responsable puede capturar avances
         * ===================================================== */
        $user        = $this->getUser();
        $responsable = $encabezado->getResponsable();

        if (!$responsable || $responsable->getUser() !== $user) {
            throw $this->createAccessDeniedException(
                'No puedes capturar avances de un PTA que no es tuyo.'
            );
        }

        /* =====================================================
         * MAPA DE MESES (número ↔ nombre en español)
         * ===================================================== */
        $mesesES = [
            1  => 'Enero',   2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',   5  => 'Mayo',       6  => 'Junio',
            7  => 'Julio',   8  => 'Agosto',     9  => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre',  12 => 'Diciembre',
        ];

        /* =====================================================
         * MES ACTUAL (fuente de verdad para bloqueos)
         * ===================================================== */
        $mesActualNumero = (int) date('n');
        $mesActualNombre = $mesesES[$mesActualNumero];

        /* =====================================================
         * GET — Pre-calcular meses reportables por indicador
         *
         * Un mes es "reportable" para un indicador si pertenece
         * al periodo de al menos una de sus acciones.
         * Esto determina qué inputs se muestran en la vista.
         * ===================================================== */
        $mesesReportablesPorIndicador = [];

        foreach ($encabezado->getIndicadores() as $indicador) {

            $mesesUnion = [];

            foreach ($encabezado->getAcciones() as $accion) {

                // Solo las acciones que pertenecen a este indicador
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $mesesUnion = array_merge($mesesUnion, $accion->getPeriodo());
            }

            // Ordenar por posición en el año (no alfabéticamente)
            $orden = array_flip(array_values($mesesES));
            $mesesUnion = array_unique($mesesUnion);
            usort($mesesUnion, fn($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));

            $mesesReportablesPorIndicador[$indicador->getIndice()] = $mesesUnion;
        }

        /* =====================================================
         * POST — Guardar avances
         * ===================================================== */
        if ($request->isMethod('POST')) {

            // Año para preservar filtros al redirigir
            $anioEjecucion            = $request->query->getInt('anio', (int) date('Y'));
            $departamentoSeleccionado = $request->query->get('departamento');
            $puestoSeleccionado       = $request->query->get('puesto');

            // Validación CSRF
            if (!$this->isCsrfTokenValid('edit' . $encabezado->getId(), $request->request->get('_token'))) {
                return $this->redirectToRoute('app_encabezado_index', [
                    'anio'         => $anioEjecucion,
                    'departamento' => $departamentoSeleccionado,
                    'puesto'       => $puestoSeleccionado,
                ]);
            }

            // Año del PTA debe coincidir con el año actual para permitir edición
            if ((int) date('Y') !== (int) $encabezado->getAnioEjecucion()) {
                return $this->redirectToRoute('app_encabezado_index', [
                    'anio'         => $anioEjecucion,
                    'departamento' => $departamentoSeleccionado,
                    'puesto'       => $puestoSeleccionado,
                ]);
            }

            $fechaEvento = new \DateTimeImmutable();

            // Datos POST de los dos flujos
            $mesesCumplidosPost  = $request->request->all('meses_cumplidos');  // [accionId][mes] = "1"|"0"
            $motivosAccionPost   = $request->request->all('motivos_accion');    // [accionId][mes] = "texto"
            $valoresIndicadorPost = $request->request->all('valor_indicador'); // [indicadorId][mes] = "valor"
            $motivosIndicadorPost = $request->request->all('motivos_indicador'); // [indicadorId][mes] = "texto"

            /* -------------------------------------------------
             * FLUJO 1 — ACCIONES: guardar estado ✓/✗ por mes
             * ------------------------------------------------- */
            foreach ($encabezado->getAcciones() as $accion) {

                $accionId = $accion->getId();

                // Si no vienen datos para esta acción, saltarla
                if (!isset($mesesCumplidosPost[$accionId])) {
                    continue;
                }

                $mesesActuales = $accion->getMesesCumplidos() ?? [];

                foreach ($mesesCumplidosPost[$accionId] as $mes => $valorStr) {

                    // Convertir nombre de mes a número para comparar con el mes actual
                    $mesNumero = array_search($mes, $mesesES, true);

                    // Ignorar nombres de mes inválidos o meses futuros
                    if ($mesNumero === false || $mesNumero > $mesActualNumero) {
                        continue;
                    }

                    // Ignorar meses que no están en el periodo de la acción
                    if (!in_array($mes, $accion->getPeriodo(), true)) {
                        continue;
                    }

                    // Convertir a bool: "1" = cumplida, "0" = no cumplida
                    $cumplida = ($valorStr === '1');
                    $motivo   = $motivosAccionPost[$accionId][$mes] ?? null;

                    // Limpiar motivo vacío
                    if ($motivo !== null && trim($motivo) === '') {
                        $motivo = null;
                    }

                    // Guardar estado actual en el JSON de la acción
                    $mesesActuales[$mes] = $cumplida;

                    /* =========================================
                     * HISTORIAL DE ACCIÓN
                     * -----------------------------------------
                     * Se genera un registro cada vez que se
                     * guarda el formulario para ese mes.
                     * El motivo es obligatorio cuando:
                     *   - Es mes pasado (cualquier valor)
                     *   - Es mes actual y NO fue cumplida
                     * ========================================= */
                    $hist = new HistorialAcciones();
                    $hist->setAccion($accion);
                    $hist->setMes($mesNumero);
                    $hist->setValor($cumplida ? 1 : 0);
                    $hist->setMotivo($motivo);
                    $hist->setFecha($fechaEvento);

                    $entityManager->persist($hist);
                }

                $accion->setMesesCumplidos($mesesActuales);
            }

            /* -------------------------------------------------
             * FLUJO 2 — INDICADORES: guardar valor snapshot
             * ------------------------------------------------- */
            foreach ($encabezado->getIndicadores() as $indicador) {

                $indicadorId = $indicador->getId();

                if (!isset($valoresIndicadorPost[$indicadorId])) {
                    continue;
                }

                // Meses reportables de este indicador (ya calculados arriba)
                $mesesReportables = $mesesReportablesPorIndicador[$indicador->getIndice()] ?? [];

                $valoresMensualesActuales = $indicador->getValorMensual() ?? [];

                foreach ($valoresIndicadorPost[$indicadorId] as $mes => $valorStr) {

                    $mesNumero = array_search($mes, $mesesES, true);

                    // Ignorar meses inválidos, futuros o no reportables
                    if ($mesNumero === false || $mesNumero > $mesActualNumero) {
                        continue;
                    }

                    if (!in_array($mes, $mesesReportables, true)) {
                        continue;
                    }

                    // Ignorar envíos vacíos
                    if (trim($valorStr) === '') {
                        continue;
                    }

                    $valor  = (string) round((float) $valorStr, 2);
                    $motivo = $motivosIndicadorPost[$indicadorId][$mes] ?? null;

                    if ($motivo !== null && trim($motivo) === '') {
                        $motivo = null;
                    }

                    // Actualizar snapshot actual del indicador
                    $valoresMensualesActuales[$mes] = $valor;

                    /* =========================================
                     * HISTORIAL DE INDICADOR
                     * -----------------------------------------
                     * Se registra cada captura para trazabilidad.
                     * El motivo es obligatorio para meses pasados
                     * o correcciones de valores ya guardados.
                     * ========================================= */
                    $histInd = new HistorialIndicadorValor();
                    $histInd->setIndicador($indicador);
                    $histInd->setMes($mesNumero);
                    $histInd->setValor($valor);
                    $histInd->setMotivo($motivo);
                    $histInd->setFecha($fechaEvento);

                    $entityManager->persist($histInd);
                }

                $indicador->setValorMensual($valoresMensualesActuales);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_encabezado_index', [
                'anio'         => $anioEjecucion,
                'departamento' => $departamentoSeleccionado,
                'puesto'       => $puestoSeleccionado,
            ]);
        }

        /* =====================================================
         * GET — Renderizar vista de captura
         * ===================================================== */
        $filtros = [
            'anio'         => $request->query->get('anio'),
            'departamento' => $request->query->get('departamento'),
            'puesto'       => $request->query->get('puesto'),
        ];

        $isTurbo = $request->headers->has('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('pta/encabezado/edit_page.html.twig', [
                'encabezado'                    => $encabezado,
                'filtros'                       => $filtros,
                'mesActual'                     => $mesActualNombre,
                'mesActualNumero'               => $mesActualNumero,
                'mesesReportablesPorIndicador'  => $mesesReportablesPorIndicador,
                'volver_path'                   => $this->generateUrl('app_encabezado_show', [
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

    /* =========================================================
     * DELETE — Eliminar un PTA
     * ========================================================= */
    #[Route('/{id}/delete', name: 'app_encabezado_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Encabezado $encabezado,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $encabezado->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($encabezado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
    }

    /* =========================================================
     * GRÁFICAS — Ver gráficas de avance del PTA
     * ========================================================= */
    #[Route('/graficas/{id}', name: 'app_encabezado_graficas', methods: ['GET'])]
    public function graficas(
        Request $request,
        Encabezado $encabezado,
        \App\Service\Pta\PtaGraficaService $ptaGraficaService
    ): Response {

        $filtros = [
            'anio'         => $request->query->get('anio'),
            'departamento' => $request->query->get('departamento'),
            'puesto'       => $request->query->get('puesto'),
        ];

        $from    = $request->query->get('from', 'show');
        $graficas = $ptaGraficaService->build($encabezado);
        $isTurbo  = $request->headers->has('Turbo-Frame');

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
