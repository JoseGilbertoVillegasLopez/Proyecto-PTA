<?php

namespace App\Repository;

use App\Entity\SolicitudGastosEvidencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosEvidencia>
 */
class SolicitudGastosEvidenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosEvidencia::class);
    }
}
