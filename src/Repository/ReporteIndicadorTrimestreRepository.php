<?php

namespace App\Repository;

use App\Entity\Personal;
use App\Entity\ReporteIndicadorTrimestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReporteIndicadorTrimestre>
 */
class ReporteIndicadorTrimestreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReporteIndicadorTrimestre::class);
    }

    /**
     * @return ReporteIndicadorTrimestre[]
     */
    public function findByPersonalOrderByRecent(Personal $personal): array
    {
        return $this->createQueryBuilder('reporte')
            ->andWhere('reporte.personal = :personal')
            ->setParameter('personal', $personal)
            ->orderBy('reporte.anio', 'DESC')
            ->addOrderBy('reporte.trimestre', 'DESC')
            ->addOrderBy('reporte.creadoFecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ReporteIndicadorTrimestre[]
     */
    public function findByPersonalAndAnio(Personal $personal, int $anio): array
    {
        return $this->createQueryBuilder('reporte')
            ->andWhere('reporte.personal = :personal')
            ->andWhere('reporte.anio = :anio')
            ->setParameter('personal', $personal)
            ->setParameter('anio', $anio)
            ->orderBy('reporte.trimestre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPersonalAnioTrimestre(Personal $personal, int $anio, int $trimestre): ?ReporteIndicadorTrimestre
    {
        return $this->findOneBy([
            'personal' => $personal,
            'anio' => $anio,
            'trimestre' => $trimestre,
        ]);
    }
}
