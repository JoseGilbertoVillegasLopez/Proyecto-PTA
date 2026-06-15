<?php

namespace App\Repository;

use App\Entity\ReporteIndicadorEvidencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReporteIndicadorEvidencia>
 */
class ReporteIndicadorEvidenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReporteIndicadorEvidencia::class);
    }
}
