<?php

namespace App\Repository;

use App\Entity\PartidasPresupuestales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PartidasPresupuestales>
 */
class PartidasPresupuestalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartidasPresupuestales::class);
    }

//    /**
//     * @return PartidasPresupuestales[] Returns an array of PartidasPresupuestales objects
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

//    public function findOneBySomeField($value): ?PartidasPresupuestales
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function findAllOrderByCapituloPartida(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.capitulo', 'ASC')
            ->addOrderBy('p.partida', 'ASC')
            ->getQuery()
            ->getResult();
        }



}
