<?php

namespace App\Controller;

use App\Entity\SolicitudGastos;
use App\Entity\User;
use App\Repository\DepartamentoRepository;
use App\Repository\PartidasPresupuestalesRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\PuestoRepository;
use App\Repository\SolicitudGastosBancoRepository;
use App\Repository\SolicitudGastosRepository;
use App\Repository\TipoSolicitudRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use App\Service\SolicitudGastos\GuardarSolicitudGastosService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/solicitud-gastos')]
final class SolicitudGastosController extends AbstractController
{
    public function __construct(
        private ModuloAccesoResolver $moduloAccesoResolver,
    ) {}

    private function tieneAcceso(): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->moduloAccesoResolver->tieneAcceso($user, 'solicitud_gastos');
    }

    private function esEncargado(): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->moduloAccesoResolver->esEncargado($user, 'solicitud_gastos');
    }

    /* =====================================================
       INDEX — VISTA DEL USUARIO
       ===================================================== */
    #[Route('', name: 'app_solicitud_gastos_index', methods: ['GET'])]
    public function index(
        Request $request,
        SolicitudGastosRepository $repo,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->tieneAcceso()) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de solicitud de gastos.');
        }

        $personal = $user->getPersonal();

        if (!$personal) {
            throw $this->createAccessDeniedException('El usuario no tiene un perfil de personal asociado.');
        }

        $desde = $request->query->get('desde');
        $hasta = $request->query->get('hasta');
        $solicitudes = $repo->findByPersonal($personal, $desde, $hasta);

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('solicitud_gastos/index.html.twig', [
                'solicitudes' => $solicitudes,
                'desde' => $desde,
                'hasta' => $hasta,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'solicitud_gastos',
            'content_url' => $this->generateUrl('app_solicitud_gastos_index'),
        ]);
    }

    /* =====================================================
       NEW
       ===================================================== */
    #[Route('/nueva', name: 'app_solicitud_gastos_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TipoSolicitudRepository $tipoRepo,
        ProcesoEstrategicoRepository $peRepo,
        ProcesoClaveRepository $pcRepo,
        PartidasPresupuestalesRepository $partidasRepo,
        SolicitudGastosBancoRepository $bancoRepo,
        GuardarSolicitudGastosService $guardarService,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->tieneAcceso()) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de solicitud de gastos.');
        }

        $personal = $user->getPersonal();

        if (!$personal) {
            throw $this->createAccessDeniedException('El usuario no tiene un perfil de personal asociado.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('nueva_solicitud_gastos', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $data = $request->request->all('solicitud');
            $archivosEvidencia = $request->files->all('solicitud')['evidencias'] ?? [];

            try {
                $guardarService->guardar($data, $personal, $archivosEvidencia);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_solicitud_gastos_new');
            }

            $this->addFlash('success', 'Solicitud creada correctamente.');

            return $this->redirectToRoute('app_solicitud_gastos_index');
        }

        $procesosClave    = $pcRepo->findAll();
        $partidasPresup   = $partidasRepo->findAll();

        $catalogos = [
            'tipos_solicitud'          => $tipoRepo->findAllOrdenados(),
            'procesos_estrategicos'    => $peRepo->findAllOrderByNombre(),
            'procesos_clave'           => $procesosClave,
            'partidas_presupuestales'  => $partidasPresup,
            'bancos'                   => $bancoRepo->findActivos(),
            'documentos_verificacion'  => SolicitudGastos::DOCUMENTOS_VERIFICACION,
        ];

        $procesoClaveData = array_map(static fn($pc) => [
            'id'     => $pc->getId(),
            'nombre' => $pc->getNombre() ?? '',
            'pei'    => $pc->getPei()        ?? '',
            'paig'   => $pc->getPaig()       ?? '',
            'meta'   => $pc->getMetaPdiPta() ?? '',
        ], $procesosClave);

        $partidasData = array_map(static fn($p) => [
            'id'          => $p->getId(),
            'capitulo'    => $p->getCapitulo()   ?? '',
            'partida'     => $p->getPartida()    ?? '',
            'descripcion' => $p->getDescripcion() ?? '',
        ], $partidasPresup);

        $isTurbo = $request->headers->get('Turbo-Frame');

        $templateVars = [
            'personal'           => $personal,
            'catalogos'          => $catalogos,
            'procesos_clave_json' => $procesoClaveData,
            'partidas_json'       => $partidasData,
        ];

        if ($isTurbo) {
            return $this->render('solicitud_gastos/new.html.twig', $templateVars);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos',
            'content_url' => $this->generateUrl('app_solicitud_gastos_new'),
        ]);
    }

    /* =====================================================
       SHOW
       ===================================================== */
    #[Route('/{id}', name: 'app_solicitud_gastos_show', methods: ['GET'])]
    public function show(Request $request, SolicitudGastos $solicitud): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $personal = $user->getPersonal();
        $esEncargado = $this->esEncargado();

        // Un usuario normal solo puede ver sus propias solicitudes
        if (!$esEncargado && $solicitud->getSolicitante() !== $personal) {
            throw $this->createAccessDeniedException('No tienes permiso para ver esta solicitud.');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('solicitud_gastos/show.html.twig', [
                'solicitud'   => $solicitud,
                'esEncargado' => $esEncargado,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos',
            'content_url' => $this->generateUrl('app_solicitud_gastos_show', ['id' => $solicitud->getId()]),
        ]);
    }

    /* =====================================================
       ENCARGADO INDEX
       ===================================================== */
    #[Route('/encargado/listado', name: 'app_solicitud_gastos_encargado_index', methods: ['GET'])]
    public function encargadoIndex(
        Request $request,
        SolicitudGastosRepository $repo,
        DepartamentoRepository $deptoRepo,
        PuestoRepository $puestoRepo,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException('Solo el encargado del módulo puede ver esta vista.');
        }

        $dv = $request->query->get('departamento', '');
        $departamentoId = ($dv !== '' && $dv !== null) ? ((int) $dv ?: null) : null;

        $pv = $request->query->get('puesto', '');
        $puestoId = ($pv !== '' && $pv !== null) ? ((int) $pv ?: null) : null;

        $estadoSel   = $request->query->getString('estado')      ?: null;
        $fechaDesde  = $request->query->getString('fecha_desde') ?: null;
        $fechaHasta  = $request->query->getString('fecha_hasta') ?: null;

        $solicitudes = $repo->findAllFiltradas($departamentoId, $puestoId, $estadoSel, $fechaDesde, $fechaHasta);

        // Petición async: devuelve solo los <tr> de la tabla
        if ($request->headers->get('X-SG-Partial') === 'tabla') {
            return $this->render('solicitud_gastos/_tabla_encargado.html.twig', [
                'solicitudes' => $solicitudes,
            ]);
        }

        $departamentos = $deptoRepo->findAll();
        $puestos       = $puestoRepo->findAll();

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('solicitud_gastos/encargado_index.html.twig', [
                'solicitudes'      => $solicitudes,
                'departamentos'    => $departamentos,
                'puestos'          => $puestos,
                'departamento_sel' => $departamentoId,
                'puesto_sel'       => $puestoId,
                'estado_sel'       => $estadoSel,
                'fecha_desde'      => $fechaDesde,
                'fecha_hasta'      => $fechaHasta,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos_encargado',
            'content_url' => $this->generateUrl('app_solicitud_gastos_encargado_index', $request->query->all()),
        ]);
    }

    /* =====================================================
       MARCAR REVISADA
       ===================================================== */
    #[Route('/{id}/revisar', name: 'app_solicitud_gastos_revisar', methods: ['POST'])]
    public function revisar(
        Request $request,
        SolicitudGastos $solicitud,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('revisar_' . $solicitud->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $solicitud->setEstado('revisada');
        $em->flush();

        $this->addFlash('success', 'Solicitud marcada como revisada.');

        return $this->redirectToRoute('app_solicitud_gastos_show', ['id' => $solicitud->getId()]);
    }
}
