<?php

namespace App\Repository;

use App\Entity\ReporteIndicadores;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReporteIndicadores>
 */
class ReporteIndicadoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReporteIndicadores::class);
    }

    /**
     * @return ReporteIndicadores[]
     */
    public function findByUsuarioCreador(User $user): array
    {
        return $this->createQueryBuilder('reporte')
            ->andWhere('reporte.creadoPor = :user')
            ->setParameter('user', $user)
            ->orderBy('reporte.creadoFecha', 'DESC')
            ->addOrderBy('reporte.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
