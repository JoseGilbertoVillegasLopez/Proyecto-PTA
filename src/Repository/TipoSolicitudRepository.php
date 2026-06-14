<?php

namespace App\Repository;

use App\Entity\TipoSolicitud;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TipoSolicitud>
 */
class TipoSolicitudRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoSolicitud::class);
    }

    public function findAllOrdenados(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
