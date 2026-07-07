<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PersonalRepository;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API dedicada al módulo de Solicitud de Gastos para buscar
 * personal que puede figurar como autorizador (jefe de área, autoriza).
 * Búsqueda por nombre completo o por puesto.
 */
#[Route('/solicitud-gastos/api/autorizadores')]
final class SolicitudGastosAutorizadorApiController extends AbstractController
{
    public function __construct(
        private readonly ModuloAccesoResolver $moduloAccesoResolver,
    ) {}

    #[Route('/buscar', name: 'app_solicitud_gastos_autorizadores_buscar', methods: ['GET'])]
    public function buscar(Request $request, PersonalRepository $personalRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->moduloAccesoResolver->tieneAcceso($user, 'solicitud_gastos')) {
            throw $this->createAccessDeniedException('No tienes acceso al módulo de solicitud de gastos.');
        }

        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        $qb = $personalRepository->createQueryBuilder('p')
            ->join('p.puesto', 'pu')
            ->andWhere(
                'LOWER(p.nombre) LIKE :q
                OR LOWER(p.ap_paterno) LIKE :q
                OR LOWER(p.ap_materno) LIKE :q
                OR LOWER(pu.nombre) LIKE :q'
            )
            ->andWhere('p.activo = true')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setMaxResults(10);

        $result = $qb->getQuery()->getResult();

        $data = [];
        foreach ($result as $p) {
            $data[] = [
                'id' => $p->getId(),
                'nombre' => trim($p->getNombre() . ' ' . $p->getApPaterno() . ' ' . $p->getApMaterno()),
                'puesto' => $p->getPuesto()?->getNombre() ?? '',
            ];
        }

        return $this->json($data);
    }
}
