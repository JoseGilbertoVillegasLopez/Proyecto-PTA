<?php

namespace App\Repository;

use App\Entity\SolicitudGastosBanco;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosBanco>
 */
class SolicitudGastosBancoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosBanco::class);
    }

    /** @return SolicitudGastosBanco[] */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.estado = :estado')
            ->setParameter('estado', 'activo')
            ->orderBy('b.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return SolicitudGastosBanco[] */
    public function findAllOrdenados(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.estado', 'ASC')
            ->addOrderBy('b.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
