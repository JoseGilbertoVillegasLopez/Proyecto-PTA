<?php

namespace App\Repository;

use App\Entity\ProcesoEstrategico;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProcesoEstrategico>
 */
class ProcesoEstrategicoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcesoEstrategico::class);
    }

//    /**
//     * @return ProcesoEstrategico[] Returns an array of ProcesoEstrategico objects
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

//    public function findOneBySomeField($value): ?ProcesoEstrategico
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function findAllOrderByNombre(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }


}
