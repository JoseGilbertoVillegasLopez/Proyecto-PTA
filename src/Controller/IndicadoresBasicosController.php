<?php

namespace App\Controller;

use App\Entity\IndicadoresBasicos;
use App\Entity\SemaforoIndicadores;
use App\Entity\SemaforoIndicadoresMedia;
use App\Entity\User;
use App\Form\IndicadoresBasicos\IndicadoresBasicosType;
use App\Form\IndicadoresBasicos\IndicadoresBasicosEditType;
use App\Repository\IndicadoresBasicosRepository;
use App\Repository\SemaforoIndicadoresMediaRepository;
use App\Repository\SemaforoIndicadoresRepository;
use App\Service\Indicadores\CicloIndicadoresService;
use App\Service\Indicadores\SemaforoIndicadoresColorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('indicadores_basicos')]
final class IndicadoresBasicosController extends AbstractController
{
    #[Route(name: 'app_indicadores_basicos_index', methods: ['GET'])]
    public function index(IndicadoresBasicosRepository $repository): Response
    {
        return $this->render('indicadores_basicos/index.html.twig', [
            'indicadores_basicos' => $repository->findAllOrderByNombre(),
            'can_view_plantilla_indicadores' => $this->canViewPlantillaIndicadores(),
        ]);
    }

    #[Route('/plantilla_indicadores', name: 'app_indicadores_basicos_plantilla_indicadores', methods: ['GET'])]
    public function plantillaIndicadores(
        Request $request,
        IndicadoresBasicosRepository $repository,
        SemaforoIndicadoresRepository $semaforoRepository,
        SemaforoIndicadoresMediaRepository $mediaRepository,
        CicloIndicadoresService $cicloService,
        SemaforoIndicadoresColorService $colorService
    ): Response
    {
        if (!$this->canViewPlantillaIndicadores()) {
            throw $this->createAccessDeniedException('No tienes permiso para ver la plantilla de indicadores.');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');
        $templateData = $this->buildPlantillaIndicadoresData(
            $repository,
            $semaforoRepository,
            $mediaRepository,
            $cicloService,
            $colorService,
            selectedCycleIds: [
                $request->query->get('ciclo_1'),
                $request->query->get('ciclo_2'),
                $request->query->get('ciclo_3'),
            ],
            recentChanges: $request->getSession()->get('plantilla_indicadores_recent_changes', []),
            canEditPlantilla: $this->canEditPlantillaIndicadores()
        );

        if ($isTurbo) {
            return $this->render('indicadores_basicos/plantilla_indicadores.html.twig', $templateData);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'plantilla_indicadores',
            'content_url' => $this->generateUrl('app_indicadores_basicos_plantilla_indicadores'),
        ]);
    }

    #[Route('/plantilla_indicadores/edit', name: 'app_indicadores_basicos_plantilla_indicadores_edit', methods: ['GET','POST'])]
    public function editPlantillaIndicadores(
        Request $request,
        IndicadoresBasicosRepository $repository,
        SemaforoIndicadoresRepository $semaforoRepository,
        SemaforoIndicadoresMediaRepository $mediaRepository,
        CicloIndicadoresService $cicloService,
        SemaforoIndicadoresColorService $colorService,
        EntityManagerInterface $em
    ): Response {
        if (!$this->canEditPlantillaIndicadores()) {
            throw $this->createAccessDeniedException('Solo el departamento de Estadistica y Evaluacion puede editar esta plantilla.');
        }

        $grupos = $repository->findActivosGroupedByGrupo();
        $indicadores = $this->flattenIndicadoresFromGroups($grupos);
        $ciclos = $cicloService->getCiclosVisibles();
        $recentChanges = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_plantilla_indicadores', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalido.');
            }

            $captura = $request->request->all('indicadores');

            foreach ($indicadores as $indicador) {
                $indicadorId = (string) $indicador->getId();
                $indicadorData = $captura[$indicadorId] ?? [];
                $ciclosData = $indicadorData['ciclos'] ?? [];

                foreach ($ciclos as $ciclo) {
                    if (!$ciclo->isActivo()) {
                        continue;
                    }

                    $cicloId = (string) $ciclo->getId();
                    $cicloData = $ciclosData[$cicloId] ?? [];
                    $cantidad1 = $this->normalizeDecimalInput($cicloData['cantidad1'] ?? null);
                    $cantidad2 = $this->normalizeDecimalInput($cicloData['cantidad2'] ?? null);
                    $resultado = $this->calculateResultadoCiclo($cantidad1, $cantidad2);

                    $registro = $semaforoRepository->findOneByIndicadorAndCiclo($indicador, $ciclo);
                    $previousCantidad1 = $registro?->getCantidad1();
                    $previousCantidad2 = $registro?->getCantidad2();
                    $previousResultado = $registro?->getResultadoCiclo();

                    if (!$registro) {
                        $registro = new SemaforoIndicadores();
                        $registro->setIndicadorBasico($indicador);
                        $registro->setCiclo($ciclo);
                        $em->persist($registro);
                    }

                    $this->trackChangedField($recentChanges, $indicadorId, $cicloId, 'cantidad1', $previousCantidad1, $cantidad1);
                    $this->trackChangedField($recentChanges, $indicadorId, $cicloId, 'cantidad2', $previousCantidad2, $cantidad2);
                    $this->trackChangedField($recentChanges, $indicadorId, $cicloId, 'resultado', $previousResultado, $resultado);

                    $registro
                        ->setCantidad1($cantidad1)
                        ->setCantidad2($cantidad2)
                        ->setResultadoCiclo($resultado);
                }

                $mediaData = $indicadorData['media'] ?? [];
                $media = $mediaRepository->findOneByIndicador($indicador);
                $mediaEstatal = $this->normalizeDecimalInput($mediaData['estatal'] ?? null);
                $mediaNacional = $this->normalizeDecimalInput($mediaData['nacional'] ?? null);
                $previousMediaEstatal = $media?->getMediaEstatal();
                $previousMediaNacional = $media?->getMediaNacional();

                if (!$media) {
                    $media = new SemaforoIndicadoresMedia();
                    $media->setIndicadorBasico($indicador);
                    $em->persist($media);
                }

                $this->trackChangedField($recentChanges, $indicadorId, 'media', 'estatal', $previousMediaEstatal, $mediaEstatal);
                $this->trackChangedField($recentChanges, $indicadorId, 'media', 'nacional', $previousMediaNacional, $mediaNacional);

                $media
                    ->setMediaEstatal($mediaEstatal)
                    ->setMediaNacional($mediaNacional);
            }

            $em->flush();
            $request->getSession()->set('plantilla_indicadores_recent_changes', $recentChanges);

            return $this->redirectToRoute('app_indicadores_basicos_plantilla_indicadores');
        }

        $templateData = $this->buildPlantillaIndicadoresData(
            $repository,
            $semaforoRepository,
            $mediaRepository,
            $cicloService,
            $colorService,
            $grupos,
            $indicadores,
            recentChanges: $request->getSession()->get('plantilla_indicadores_recent_changes', []),
            canEditPlantilla: true
        );
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/plantilla_indicadores_edit.html.twig', $templateData);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'plantilla_indicadores',
            'content_url' => $this->generateUrl('app_indicadores_basicos_plantilla_indicadores_edit'),
        ]);
    }

    #[Route('/new', name: 'app_indicadores_basicos_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $indicador = new IndicadoresBasicos();
        $form = $this->createForm(IndicadoresBasicosType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $indicador->setActivo(true);
            $em->persist($indicador);
            $em->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/new.html.twig', [
                'indicador' => $indicador,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_new'),
        ]);
    }

    #[Route('/{id}', name: 'app_indicadores_basicos_show', methods: ['GET'])]
    public function show(Request $request, IndicadoresBasicos $indicador): Response
    {
        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/show.html.twig', [
                'indicador' => $indicador,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_show', [
                'id' => $indicador->getId(),
            ]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_indicadores_basicos_edit', methods: ['GET','POST'])]
    public function edit(Request $request, IndicadoresBasicos $indicador, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(IndicadoresBasicosEditType::class, $indicador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('app_indicadores_basicos_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('indicadores_basicos/edit.html.twig', [
                'indicador' => $indicador,
                'form' => $form,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section' => 'indicadores_basicos',
            'content_url' => $this->generateUrl('app_indicadores_basicos_edit', [
                'id' => $indicador->getId(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_indicadores_basicos_delete', methods: ['POST'])]
    public function delete(Request $request, IndicadoresBasicos $indicador, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid(
            'delete' . $indicador->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $indicador->setActivo(false);
            $em->flush();
        }

        return $this->redirectToRoute('app_indicadores_basicos_index');
    }

    private function buildPlantillaIndicadoresData(
        IndicadoresBasicosRepository $repository,
        SemaforoIndicadoresRepository $semaforoRepository,
        SemaforoIndicadoresMediaRepository $mediaRepository,
        CicloIndicadoresService $cicloService,
        SemaforoIndicadoresColorService $colorService,
        ?array $grupos = null,
        ?array $indicadores = null,
        array $selectedCycleIds = [],
        array $recentChanges = [],
        bool $canEditPlantilla = false
    ): array {
        $grupos ??= $repository->findActivosGroupedByGrupo();
        $indicadores ??= $this->flattenIndicadoresFromGroups($grupos);
        $ciclos = $selectedCycleIds === []
            ? $cicloService->getCiclosVisibles()
            : $cicloService->getCiclosParaVista($selectedCycleIds);

        $valores = $semaforoRepository->findIndexedByIndicadores($indicadores, $ciclos);
        $medias = $mediaRepository->findIndexedByIndicadores($indicadores);

        return [
            'grupos_indicadores' => $grupos,
            'ciclos' => $ciclos,
            'ciclos_disponibles' => $cicloService->getCiclosDisponibles(),
            'valores' => $valores,
            'medias' => $medias,
            'semaforo_colores' => $colorService->buildIndexedColors($indicadores, $valores, $medias),
            'recent_changes' => $recentChanges,
            'can_edit_plantilla' => $canEditPlantilla,
        ];
    }

    private function flattenIndicadoresFromGroups(array $grupos): array
    {
        $indicadores = [];

        foreach ($grupos as $grupo) {
            foreach ($grupo['indicadores'] as $indicador) {
                $indicadores[] = $indicador;
            }
        }

        return $indicadores;
    }

    private function normalizeDecimalInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);

        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function calculateResultadoCiclo(?string $cantidad1, ?string $cantidad2): ?string
    {
        if ($cantidad1 === null || $cantidad2 === null) {
            return null;
        }

        $cantidad1Float = (float) $cantidad1;
        $cantidad2Float = (float) $cantidad2;

        if ($cantidad1Float === 0.0 && $cantidad2Float === 0.0) {
            return '0.00';
        }

        if ($cantidad2Float === 0.0) {
            return null;
        }

        return number_format(($cantidad1Float / $cantidad2Float) * 100, 2, '.', '');
    }

    private function trackChangedField(
        array &$recentChanges,
        string $indicadorId,
        string $group,
        string $field,
        ?string $previousValue,
        ?string $newValue
    ): void {
        if ($previousValue === $newValue) {
            return;
        }

        $recentChanges[$indicadorId][$group][$field] = true;
    }

    private function canEditPlantillaIndicadores(): bool
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $departamento = $user->getPersonal()?->getDepartamento()?->getNombre();

        return $this->normalizeAccessName($departamento ?? '') === 'ESTADISTICA Y EVALUACION';
    }

    private function canViewPlantillaIndicadores(): bool
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->canEditPlantillaIndicadores() || $this->isGranted('ROLE_DIRECCION_GENERAL')) {
            return true;
        }

        $puesto = $this->normalizeAccessName($user->getPersonal()?->getPuesto()?->getNombre() ?? '');

        return in_array($puesto, [
            'SUBDIRECCION DE PLANEACION',
            'DIRECCION DE PLANEACION Y VINCULACION',
        ], true);
    }

    private function normalizeAccessName(string $value): string
    {
        $value = trim($value);
        $value = strtr($value, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]);
        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
