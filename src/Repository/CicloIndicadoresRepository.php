<?php

namespace App\Repository;

use App\Entity\CicloIndicadores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CicloIndicadores>
 */
class CicloIndicadoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CicloIndicadores::class);
    }

    /**
     * @return CicloIndicadores[]
     */
    public function findLatestVisible(int $limit = 3): array
    {
        return array_reverse($this->createQueryBuilder('c')
            ->andWhere('c.visible = :visible')
            ->setParameter('visible', true)
            ->orderBy('c.fechaApertura', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult());
    }

    /**
     * @return CicloIndicadores[]
     */
    public function findAllOrderByFechaApertura(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.fechaApertura', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNombre(string $nombre): ?CicloIndicadores
    {
        return $this->findOneBy(['nombre' => $nombre]);
    }
}
