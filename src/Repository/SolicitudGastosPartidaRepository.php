<?php

namespace App\Repository;

use App\Entity\SolicitudGastosPartida;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosPartida>
 */
class SolicitudGastosPartidaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosPartida::class);
    }
}
