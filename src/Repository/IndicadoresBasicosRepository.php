<?php

namespace App\Repository;

use App\Entity\IndicadoresBasicos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IndicadoresBasicos>
 */
class IndicadoresBasicosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndicadoresBasicos::class);
    }

//    /**
//     * @return IndicadoresBasicos[] Returns an array of IndicadoresBasicos objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?IndicadoresBasicos
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findAllOrderByNombre(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.nombreIndicador', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
