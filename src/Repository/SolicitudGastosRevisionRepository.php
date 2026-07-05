<?php

namespace App\Repository;

use App\Entity\SolicitudGastosRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosRevision>
 */
class SolicitudGastosRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosRevision::class);
    }
}
