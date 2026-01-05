<?php

namespace App\Repository;

use App\Entity\Puesto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Puesto>
 */
class PuestoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Puesto::class);
    }

    //    /**
    //     * @return Puesto[] Returns an array of Puesto objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Puesto
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findByDepartamentoIds(array $departamentoIds): array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.departamento IN (:departamentos)')
        ->setParameter('departamentos', $departamentoIds)
        ->orderBy('p.nombre', 'ASC')
        ->getQuery()
        ->getResult();
}

}
