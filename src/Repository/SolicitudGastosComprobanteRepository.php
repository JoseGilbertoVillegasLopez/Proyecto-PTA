<?php

namespace App\Repository;

use App\Entity\SolicitudGastosComprobante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosComprobante>
 */
class SolicitudGastosComprobanteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosComprobante::class);
    }
}
