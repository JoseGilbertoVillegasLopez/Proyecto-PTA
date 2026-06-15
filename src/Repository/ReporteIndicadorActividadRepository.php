<?php

namespace App\Repository;

use App\Entity\ReporteIndicadorActividad;
use App\Entity\ReporteIndicadorTrimestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReporteIndicadorActividad>
 */
class ReporteIndicadorActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReporteIndicadorActividad::class);
    }

    /**
     * @return ReporteIndicadorActividad[]
     */
    public function findByReporteWithEvidencias(ReporteIndicadorTrimestre $reporte): array
    {
        return $this->createQueryBuilder('actividad')
            ->leftJoin('actividad.indicadorBasico', 'indicador')
            ->addSelect('indicador')
            ->leftJoin('actividad.evidencias', 'evidencia')
            ->addSelect('evidencia')
            ->andWhere('actividad.reporteTrimestre = :reporte')
            ->setParameter('reporte', $reporte)
            ->orderBy('actividad.id', 'ASC')
            ->addOrderBy('evidencia.orden', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
