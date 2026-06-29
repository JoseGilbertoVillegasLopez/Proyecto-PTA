<?php

namespace App\Controller;

use App\Entity\SolicitudGastosBanco;
use App\Entity\User;
use App\Repository\SolicitudGastosBancoRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/finanzas/bancos')]
final class SolicitudGastosBancoController extends AbstractController
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

    #[Route('', name: 'app_sg_banco_index', methods: ['GET'])]
    public function index(
        Request $request,
        SolicitudGastosBancoRepository $repo,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        $bancos = $repo->findAllOrdenados();

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('solicitud_gastos_banco/index.html.twig', [
                'bancos' => $bancos,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos_bancos',
            'content_url' => $this->generateUrl('app_sg_banco_index'),
        ]);
    }

    #[Route('/nuevo', name: 'app_sg_banco_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('nuevo_banco', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $nombre = trim($request->request->getString('nombre'));

            if ($nombre !== '') {
                $banco = new SolicitudGastosBanco();
                $banco->setNombre($nombre);
                $em->persist($banco);
                $em->flush();

                $this->addFlash('success', 'Banco "' . $nombre . '" agregado correctamente.');
            }

            return $this->redirectToRoute('app_sg_banco_index');
        }

        $isTurbo = $request->headers->get('Turbo-Frame');

        if ($isTurbo) {
            return $this->render('solicitud_gastos_banco/new.html.twig');
        }

        return $this->render('dashboard/index.html.twig', [
            'section'     => 'solicitud_gastos_bancos',
            'content_url' => $this->generateUrl('app_sg_banco_new'),
        ]);
    }

    #[Route('/{id}/reactivar', name: 'app_sg_banco_reactivar', methods: ['POST'])]
    public function reactivar(
        Request $request,
        SolicitudGastosBanco $banco,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('reactivar_banco_' . $banco->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $banco->setEstado('activo');
        $em->flush();

        $this->addFlash('success', 'Banco "' . $banco->getNombre() . '" reactivado.');

        return $this->redirectToRoute('app_sg_banco_index');
    }

    #[Route('/{id}/cancelar', name: 'app_sg_banco_cancelar', methods: ['POST'])]
    public function cancelar(
        Request $request,
        SolicitudGastosBanco $banco,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->esEncargado()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cancelar_banco_' . $banco->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $banco->setEstado('cancelado');
        $em->flush();

        $this->addFlash('warning', 'Banco "' . $banco->getNombre() . '" cancelado.');

        return $this->redirectToRoute('app_sg_banco_index');
    }
}
