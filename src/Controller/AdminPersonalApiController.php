<?php

namespace App\Controller;

use App\Repository\PersonalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api/personal')]
final class AdminPersonalApiController extends AbstractController
{
    #[Route('/buscar', name: 'admin_personal_buscar', methods: ['GET'])]
    public function buscar(Request $request, PersonalRepository $personalRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        // Ajusta estos campos a tu entidad Personal si se llaman diferente
        $result = $personalRepository->createQueryBuilder('p')
            ->andWhere(
                'LOWER(p.nombre) LIKE :q 
                OR LOWER(p.ap_paterno) LIKE :q 
                OR LOWER(p.ap_materno) LIKE :q'
            )
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();


            $data = [];

            foreach ($result as $p) {
                $data[] = [
                    'id' => $p->getId(),
                    'nombre' => trim(
                        $p->getNombre() . ' ' . $p->getApPaterno() . ' ' . $p->getApMaterno()
                    ),
                ];
            }



        return $this->json($data);
    }
}
