<?php

namespace App\Controller;

use App\Entity\ReporteIndicadorActividad;
use App\Entity\ReporteIndicadorEvidencia;
use App\Entity\ReporteIndicadorTrimestre;
use App\Entity\User;
use App\Repository\IndicadoresBasicosRepository;
use App\Repository\ReporteIndicadorActividadRepository;
use App\Repository\ReporteIndicadorTrimestreRepository;
use App\Service\Indicadores\ConstructorVistaReporteIndicadoresService;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use App\Service\Pta\PtaAccessResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reporte_indicadores')]
final class ReporteIndicadoresController extends AbstractController
{
    #[Route(name: 'app_reporte_indicadores_index', methods: ['GET'])]
    #[Route('/lista_reporte', name: 'lista_reporte', methods: ['GET'])]
    public function index(
        Request $request,
        ReporteIndicadorTrimestreRepository $repository,
        ConstructorVistaReporteIndicadoresService $constructor,
        PtaAccessResolver $ptaAccessResolver,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesion para ver tus reportes.');
        }

        if (!$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores')) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de reportes de indicadores.');
        }

        $personal = $user->getPersonal();

        if (!$personal) {
            throw $this->createAccessDeniedException('Tu usuario no tiene personal asignado.');
        }

        $anioActual = (int) (new \DateTimeImmutable('today'))->format('Y');
        $aniosConReportes = $repository->findAniosByPersonal($personal);
        $anios = array_values(array_unique(array_merge([$anioActual], $aniosConReportes)));
        rsort($anios);

        $anioSeleccionado = $request->query->getInt('anio', $anioActual);
        if (!\in_array($anioSeleccionado, $anios, true)) {
            $anioSeleccionado = $anioActual;
        }

        $datos = $constructor->build($personal, $anioSeleccionado);

        $templateData = [
            'datos'             => $datos,
            'anios'             => $anios,
            'anio_seleccionado' => $anioSeleccionado,
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('reporte_indicadores/index.html.twig', $templateData);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'reporte_indicadores',
            'content_url' => $this->generateUrl('app_reporte_indicadores_index', ['anio' => $anioSeleccionado]),
            'ptaAccess'   => $ptaAccessResolver->resolve($user),
        ]);
    }

    #[Route('/encargado', name: 'app_reporte_indicadores_encargado_index', methods: ['GET'])]
    public function encargadoIndex(
        Request $request,
        ReporteIndicadorTrimestreRepository $repository,
        PtaAccessResolver $ptaAccessResolver,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesion.');
        }

        if (!$moduloAccesoResolver->esEncargado($user, 'reporte_indicadores')) {
            throw $this->createAccessDeniedException('No eres encargado del módulo de reportes de indicadores.');
        }

        $templateData = [
            'reportes' => $repository->findAllOrderByRecent(),
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('reporte_indicadores/encargado_index.html.twig', $templateData);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'reporte_indicadores_encargado',
            'content_url' => $this->generateUrl('app_reporte_indicadores_encargado_index'),
            'ptaAccess' => $ptaAccessResolver->resolve($user),
        ]);
    }

    #[Route('/new', name: 'app_reporte_indicadores_new', methods: ['GET'])]
    public function new(
        Request $request,
        ConstructorVistaReporteIndicadoresService $constructor,
        PtaAccessResolver $ptaAccessResolver,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesion para crear reportes.');
        }

        if (!$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores')) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de reportes de indicadores.');
        }

        $personal = $user->getPersonal();

        if (!$personal) {
            throw $this->createAccessDeniedException('Tu usuario no tiene personal asignado.');
        }

        $fechaPrueba = null;
        $fechaPruebaTexto = trim((string) $request->query->get('fecha_prueba', '2026-10-01'));

        if ($this->getParameter('kernel.debug') && $fechaPruebaTexto !== '') {
            try {
                $fechaPrueba = new \DateTimeImmutable($fechaPruebaTexto);
            } catch (\Exception) {
                $fechaPrueba = null;
            }
        }

        $datos = $constructor->build($personal, $request->query->getInt('anio') ?: null, $fechaPrueba);

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('reporte_indicadores/new.html.twig', [
                'datos' => $datos,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'reporte_indicadores',
            'content_url' => $this->generateUrl('app_reporte_indicadores_new'),
            'ptaAccess' => $ptaAccessResolver->resolve($user),
        ]);
    }

    #[Route('/new/{trimestre}/crear', name: 'app_reporte_indicadores_trimestre_crear', methods: ['POST'])]
    public function crearTrimestre(
        Request $request,
        int $trimestre,
        ReporteIndicadorTrimestreRepository $repository,
        EntityManagerInterface $em,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesion para crear reportes.');
        }

        if (!$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores')) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de reportes de indicadores.');
        }

        if ($trimestre < 1 || $trimestre > 4) {
            throw $this->createNotFoundException('Trimestre invalido.');
        }

        if (!$this->isCsrfTokenValid('reporte_indicadores_trimestre_crear', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalido.');
        }

        $personal = $user->getPersonal();
        $departamento = $personal?->getDepartamento();
        $puesto = $personal?->getPuesto();

        if (!$personal || !$departamento || !$puesto) {
            throw $this->createAccessDeniedException('Tu usuario no tiene personal, departamento o puesto asignado.');
        }

        $anio = $request->request->getInt('anio', (int) (new \DateTimeImmutable('today'))->format('Y'));
        $reporte = $repository->findOneByPersonalAnioTrimestre($personal, $anio, $trimestre);

        if (!$reporte) {
            $reporte = (new ReporteIndicadorTrimestre())
                ->setPersonal($personal)
                ->setDepartamento($departamento)
                ->setPuesto($puesto)
                ->setAnio($anio)
                ->setTrimestre($trimestre)
                ->setEstado(ReporteIndicadorTrimestre::ESTADO_BORRADOR)
                ->setCreadoFecha(new \DateTimeImmutable());

            $em->persist($reporte);
            $em->flush();
        }

        return $this->redirectToRoute('app_reporte_indicadores_trimestre_captura', [
            'id' => $reporte->getId(),
        ]);
    }

    #[Route('/{id}/captura', name: 'app_reporte_indicadores_trimestre_captura', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_reporte_indicadores_trimestre_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ReporteIndicadorTrimestre $reporte,
        IndicadoresBasicosRepository $indicadoresRepository,
        ReporteIndicadorActividadRepository $actividadRepository,
        EntityManagerInterface $em,
        PtaAccessResolver $ptaAccessResolver,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User
            || !$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores')
            || $reporte->getPersonal()?->getId() !== $user->getPersonal()?->getId()
        ) {
            throw $this->createAccessDeniedException('No tienes permiso para editar este reporte.');
        }

        if ($reporte->isEntregado()) {
            return $this->redirectToRoute('app_reporte_indicadores_trimestre_show', [
                'id' => $reporte->getId(),
            ]);
        }

        $indicadores = $this->getIndicadoresParaReporte($reporte, $indicadoresRepository);
        $routeName = (string) $request->attributes->get('_route');
        $isEditRoute = $routeName === 'app_reporte_indicadores_trimestre_edit';
        $formRoute = $isEditRoute
            ? 'app_reporte_indicadores_trimestre_edit'
            : 'app_reporte_indicadores_trimestre_captura';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reporte_indicadores_guardar', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalido.');
            }

            try {
                $this->guardarReporteIndicadores(
                    $request,
                    $reporte,
                    $indicadoresRepository,
                    $actividadRepository,
                    $em
                );

                $this->addFlash('success', 'Reporte guardado correctamente.');

                return $this->redirectToRoute('lista_reporte');
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        $actividades = $actividadRepository->findByReporteWithEvidencias($reporte);

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render($isEditRoute ? 'reporte_indicadores/edit.html.twig' : 'reporte_indicadores/reporte_indicadores.html.twig', [
                'reporte' => $reporte,
                'indicadores_basicos' => $indicadores,
                'actividades' => $actividades,
                'uploads_base' => '/uploads/reporte_indicadores/' . $reporte->getId() . '/',
                'form_action' => $this->generateUrl($formRoute, [
                    'id' => $reporte->getId(),
                ]),
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'reporte_indicadores',
            'content_url' => $this->generateUrl($formRoute, [
                'id' => $reporte->getId(),
            ]),
            'ptaAccess' => $ptaAccessResolver->resolve($user),
        ]);
    }

    #[Route('/{id}/show', name: 'app_reporte_indicadores_trimestre_show', methods: ['GET'])]
    public function show(
        Request $request,
        ReporteIndicadorTrimestre $reporte,
        ReporteIndicadorActividadRepository $actividadRepository,
        PtaAccessResolver $ptaAccessResolver,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesion para ver este reporte.');
        }

        $esEncargado = $moduloAccesoResolver->esEncargado($user, 'reporte_indicadores');
        $esOwner = $reporte->getPersonal()?->getId() === $user->getPersonal()?->getId()
            && $moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores');

        if (!$esOwner && !$esEncargado) {
            throw $this->createAccessDeniedException('No tienes permiso para ver este reporte.');
        }

        $templateData = [
            'reporte' => $reporte,
            'actividades' => $actividadRepository->findByReporteWithEvidencias($reporte),
            'es_encargado' => $esEncargado,
        ];

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('reporte_indicadores/show.html.twig', $templateData);
        }

        $section = (!$esOwner && $esEncargado) ? 'reporte_indicadores_encargado' : 'reporte_indicadores';

        return $this->render('dashboard/index.html.twig', [
            'section' => $section,
            'content_url' => $this->generateUrl('app_reporte_indicadores_trimestre_show', [
                'id' => $reporte->getId(),
            ]),
            'ptaAccess' => $ptaAccessResolver->resolve($user),
        ]);
    }

    #[Route('/{id}/entregar', name: 'app_reporte_indicadores_trimestre_entregar', methods: ['POST'])]
    public function entregar(
        Request $request,
        ReporteIndicadorTrimestre $reporte,
        ReporteIndicadorActividadRepository $actividadRepository,
        EntityManagerInterface $em,
        ModuloAccesoResolver $moduloAccesoResolver
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User
            || !$moduloAccesoResolver->tieneAcceso($user, 'reporte_indicadores')
            || $reporte->getPersonal()?->getId() !== $user->getPersonal()?->getId()
        ) {
            throw $this->createAccessDeniedException('No tienes permiso para entregar este reporte.');
        }

        if (!$this->isCsrfTokenValid('reporte_indicadores_entregar', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalido.');
        }

        $actividades = $actividadRepository->findByReporteWithEvidencias($reporte);

        if ($actividades === []) {
            $this->addFlash('error', 'Debes guardar al menos una actividad antes de entregar.');

            return $this->redirectToRoute('app_reporte_indicadores_trimestre_edit', [
                'id' => $reporte->getId(),
            ]);
        }

        foreach ($actividades as $actividad) {
            if ($actividad->getEvidencias()->count() < 1) {
                $this->addFlash('error', 'Cada actividad debe tener al menos una evidencia antes de entregar.');

                return $this->redirectToRoute('app_reporte_indicadores_trimestre_edit', [
                    'id' => $reporte->getId(),
                ]);
            }
        }

        $reporte
            ->setEstado(ReporteIndicadorTrimestre::ESTADO_ENTREGADO)
            ->setEntregadoFecha(new \DateTimeImmutable('today'));

        $em->flush();

        $this->addFlash('success', 'Reporte entregado correctamente.');

        return $this->redirectToRoute('lista_reporte');
    }

    private function getIndicadoresParaReporte(
        ReporteIndicadorTrimestre $reporte,
        IndicadoresBasicosRepository $indicadoresRepository
    ): array {
        $departamento = $reporte->getDepartamento();

        if ($departamento && $departamento->getIndicadoresBasicos()->count() > 0) {
            $indicadores = $departamento
                ->getIndicadoresBasicos()
                ->filter(fn ($indicador) => $indicador->isActivo())
                ->toArray();

            usort($indicadores, fn ($a, $b) => strcmp($a->getNombreIndicador() ?? '', $b->getNombreIndicador() ?? ''));

            return $indicadores;
        }

        return array_values(array_filter(
            $indicadoresRepository->findAllOrderByNombre(),
            fn ($indicador) => $indicador->isActivo()
        ));
    }

    private function guardarReporteIndicadores(
        Request $request,
        ReporteIndicadorTrimestre $reporte,
        IndicadoresBasicosRepository $indicadoresRepository,
        ReporteIndicadorActividadRepository $actividadRepository,
        EntityManagerInterface $em
    ): void {
        $actividadesData = $request->request->all('actividades');
        $archivosData = $request->files->all('actividades');

        if ($actividadesData === []) {
            throw new \DomainException('Debes capturar al menos una actividad.');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/reporte_indicadores/' . $reporte->getId();

        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            throw new \DomainException('No se pudo preparar la carpeta de evidencias.');
        }

        $actividadesExistentes = $actividadRepository->findByReporteWithEvidencias($reporte);
        $actividadesExistentesPorId = [];

        foreach ($actividadesExistentes as $actividadExistente) {
            $actividadesExistentesPorId[$actividadExistente->getId()] = $actividadExistente;
        }

        $actividadesProcesadas = [];

        foreach ($actividadesData as $index => $actividadData) {
            $actividadId = (int) ($actividadData['id'] ?? 0);
            $accion = trim((string) ($actividadData['accion'] ?? ''));
            $descripcion = trim((string) ($actividadData['descripcion'] ?? ''));
            $indicadorId = (int) ($actividadData['indicadorBasico'] ?? 0);
            $archivos = $archivosData[$index]['evidencias'] ?? [];
            $evidenciasEliminadasIds = array_map(
                'intval',
                (array) ($actividadData['evidenciasEliminadas'] ?? [])
            );

            if ($accion === '' || $descripcion === '' || $indicadorId <= 0) {
                throw new \DomainException('No se permiten actividades con campos vacios.');
            }

            if (!is_array($archivos)) {
                $archivos = [$archivos];
            }

            $archivos = array_values(array_filter($archivos, fn ($archivo) => $archivo instanceof UploadedFile && $archivo->isValid()));

            $indicador = $indicadoresRepository->find($indicadorId);

            if (!$indicador || !$indicador->isActivo()) {
                throw new \DomainException('Selecciona un indicador valido para cada actividad.');
            }

            $actividad = $actividadesExistentesPorId[$actividadId] ?? null;

            if (!$actividad) {
                $actividad = (new ReporteIndicadorActividad())
                    ->setReporteTrimestre($reporte);
                $em->persist($actividad);
            }

            $actividad
                ->setIndicadorBasico($indicador)
                ->setAccion($accion)
                ->setDescripcion($descripcion);

            $evidenciasEliminadas = [];

            foreach ($actividad->getEvidencias() as $evidenciaExistente) {
                if (!in_array($evidenciaExistente->getId(), $evidenciasEliminadasIds, true)) {
                    continue;
                }

                $evidenciasEliminadas[] = $evidenciaExistente;
            }

            $evidenciasExistentesCount = $actividad->getEvidencias()->count();
            $evidenciasRestantesCount = $evidenciasExistentesCount - count($evidenciasEliminadas);

            if ($evidenciasEliminadasIds !== [] && count($evidenciasEliminadas) !== count(array_unique($evidenciasEliminadasIds))) {
                throw new \DomainException('No se pudo eliminar una de las evidencias seleccionadas.');
            }

            $totalEvidencias = $evidenciasRestantesCount + count($archivos);

            if ($totalEvidencias < 1 || $totalEvidencias > 5) {
                throw new \DomainException('Cada actividad debe tener minimo 1 y maximo 5 evidencias.');
            }

            foreach ($evidenciasEliminadas as $evidenciaEliminada) {
                $rutaArchivo = $this->getParameter('kernel.project_dir') . '/public' . $evidenciaEliminada->getRuta();
                if (is_file($rutaArchivo)) {
                    @unlink($rutaArchivo);
                }

                $actividad->removeEvidencia($evidenciaEliminada);
                $em->remove($evidenciaEliminada);
            }

            $ordenEvidencia = 1;
            foreach ($actividad->getEvidencias() as $evidenciaRestante) {
                $evidenciaRestante->setOrden($ordenEvidencia);
                $ordenEvidencia++;
            }

            foreach ($archivos as $orden => $archivo) {
                $mimeType = (string) $archivo->getMimeType();

                if (!str_starts_with($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                    throw new \DomainException('Las evidencias solo pueden ser imagenes o archivos PDF.');
                }

                $extension = strtolower($archivo->guessExtension() ?: $archivo->getClientOriginalExtension() ?: 'bin');
                $tamano = (int) $archivo->getSize();
                $nombreGuardado = bin2hex(random_bytes(16)) . '.' . $extension;
                $archivo->move($uploadsDir, $nombreGuardado);

                $evidencia = (new ReporteIndicadorEvidencia())
                    ->setActividad($actividad)
                    ->setArchivoNombreOriginal($archivo->getClientOriginalName())
                    ->setArchivoNombreGuardado($nombreGuardado)
                    ->setRuta('/uploads/reporte_indicadores/' . $reporte->getId() . '/' . $nombreGuardado)
                    ->setMimeType($mimeType)
                    ->setExtension($extension)
                    ->setTamano($tamano)
                    ->setOrden($ordenEvidencia + $orden)
                    ->setCreadoFecha(new \DateTimeImmutable('today'));

                $actividad->addEvidencia($evidencia);
                $em->persist($evidencia);
            }

            $actividadesProcesadas[] = $actividad->getId();
        }

        foreach ($actividadesExistentes as $actividadExistente) {
            if (in_array($actividadExistente->getId(), $actividadesProcesadas, true)) {
                continue;
            }

            foreach ($actividadExistente->getEvidencias() as $evidencia) {
                $rutaArchivo = $this->getParameter('kernel.project_dir') . '/public' . $evidencia->getRuta();
                if (is_file($rutaArchivo)) {
                    @unlink($rutaArchivo);
                }
            }

            $em->remove($actividadExistente);
        }

        $em->flush();
    }
}
