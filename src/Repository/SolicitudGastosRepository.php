<?php

namespace App\Repository;

use App\Entity\Personal;
use App\Entity\SolicitudGastos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastos>
 */
class SolicitudGastosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastos::class);
    }

    public function findByPersonal(Personal $personal, ?string $desde, ?string $hasta): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.solicitante = :personal')
            ->setParameter('personal', $personal)
            ->orderBy('s.fechaSolicitud', 'DESC');

        if ($desde) {
            $qb->andWhere('s.fechaSolicitud >= :desde')
               ->setParameter('desde', new \DateTime($desde . ' 00:00:00'));
        }

        if ($hasta) {
            $qb->andWhere('s.fechaSolicitud <= :hasta')
               ->setParameter('hasta', new \DateTime($hasta . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllFiltradas(
        ?int    $departamentoId,
        ?int    $puestoId,
        ?string $estado      = null,
        ?string $fechaDesde  = null,
        ?string $fechaHasta  = null,
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->join('s.solicitante', 'p')
            ->orderBy('s.fechaSolicitud', 'DESC');

        if ($departamentoId) {
            $qb->andWhere('p.departamento = :departamento')
               ->setParameter('departamento', $departamentoId);
        }

        if ($puestoId) {
            $qb->andWhere('p.puesto = :puesto')
               ->setParameter('puesto', $puestoId);
        }

        if ($estado && in_array($estado, SolicitudGastos::ESTADOS, true)) {
            $qb->andWhere('s.estado = :estado')
               ->setParameter('estado', $estado);
        }

        if ($fechaDesde) {
            $qb->andWhere('s.fechaSolicitud >= :desde')
               ->setParameter('desde', new \DateTime($fechaDesde . ' 00:00:00'));
        }

        if ($fechaHasta) {
            $qb->andWhere('s.fechaSolicitud <= :hasta')
               ->setParameter('hasta', new \DateTime($fechaHasta . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }
}
