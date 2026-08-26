<?php

namespace App\Repository;

use App\Entity\PtaResponsableAdicional;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PtaResponsableAdicional>
 */
class PtaResponsableAdicionalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PtaResponsableAdicional::class);
    }
}
