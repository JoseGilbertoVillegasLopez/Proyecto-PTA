<?php

namespace App\Controller;

use App\Entity\SolicitudGastosConfiguracion;
use App\Entity\User;
use App\Repository\PuestoRepository;
use App\Repository\SolicitudGastosConfiguracionRepository;
use App\Repository\SolicitudGastosFolioSerieRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use App\Service\SolicitudGastos\SolicitudGastosFolioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/finanzas/configuracion')]
final class SolicitudGastosConfiguracionController extends AbstractController
{
    public function __construct(
        private ModuloAccesoResolver $moduloAccesoResolver,
    ) {}

    private function esEncargado(): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->moduloAccesoResolver->esEncargado($user, 'solicitud_gastos');
    }

    #[Route('', name: 'app_sg_configuracion_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        SolicitudGastosConfiguracionRepository $repo,
        SolicitudGastosFolioSerieRepository $folioSerieRepo,
        PuestoRepository $puestoRepo,
        SolicitudGastosFolioService $folioService,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        $config = $repo->obtener();
        $puestosConSerie = $puestoRepo->findConSerie();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('configuracion_solicitud_gastos', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $criterio = $request->request->getString('criterio_aprobacion');
            $folioAplicaA = $request->request->getString('folio_aplica_a');
            $folioCiclo = $request->request->getString('folio_ciclo_reinicio');
            $folioAlcance = $request->request->getString('folio_alcance');
            $ultimoFolioUsado = $request->request->getInt('folio_contador_actual');

            if (
                !in_array($criterio, SolicitudGastosConfiguracion::CRITERIOS_APROBACION, true)
                || !in_array($folioAplicaA, SolicitudGastosConfiguracion::FOLIO_APLICA_A_OPCIONES, true)
                || !in_array($folioCiclo, SolicitudGastosConfiguracion::FOLIO_CICLOS, true)
                || !in_array($folioAlcance, SolicitudGastosConfiguracion::FOLIO_ALCANCES, true)
                || $ultimoFolioUsado < 0
            ) {
                $this->addFlash('error', 'Alguno de los valores enviados no es válido.');

                return $this->redirectToRoute('app_sg_configuracion_edit');
            }

            $config->setCriterioAprobacion($criterio);
            $config->setMostrarMotivoRechazo($request->request->getBoolean('mostrar_motivo_rechazo'));
            $config->setFolioAplicaA($folioAplicaA);
            $config->setFolioCicloReinicio($folioCiclo);
            $config->setFolioAlcance($folioAlcance);

            if ($ultimoFolioUsado !== $config->getFolioContadorActual()) {
                $folioService->ajustarContadorManual($config, $ultimoFolioUsado);
            }

            $foliosSerieInput = $request->request->all('folio_serie');
            foreach ($puestosConSerie as $puesto) {
                $serie = $puesto->getSerie();
                if ($serie === null || $serie === '' || !array_key_exists($serie, $foliosSerieInput)) {
                    continue;
                }

                $nuevoValor = (int) $foliosSerieInput[$serie];
                if ($nuevoValor < 0) {
                    continue;
                }

                $folioSerieEntity = $folioSerieRepo->obtenerOCrear($serie);
                if ($nuevoValor !== $folioSerieEntity->getContadorActual()) {
                    $folioService->ajustarContadorSerieManual($folioSerieEntity, $nuevoValor, $folioCiclo);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Configuración guardada correctamente.');

            return $this->redirectToRoute('app_sg_configuracion_edit');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');
        $templateVars = [
            'config' => $config,
            'puestosConSerie' => $puestosConSerie,
            'foliosPorSerie' => $folioSerieRepo->findTodasIndexadas(),
        ];

        if ($isTurbo) {
            return $this->render('solicitud_gastos_configuracion/edit.html.twig', $templateVars);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos_configuracion',
            'content_url' => $this->generateUrl('app_sg_configuracion_edit'),
        ]);
    }
}
