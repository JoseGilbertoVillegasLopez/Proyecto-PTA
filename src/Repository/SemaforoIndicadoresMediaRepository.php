<?php

namespace App\Repository;

use App\Entity\IndicadoresBasicos;
use App\Entity\SemaforoIndicadoresMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SemaforoIndicadoresMedia>
 */
class SemaforoIndicadoresMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SemaforoIndicadoresMedia::class);
    }

    public function findOneByIndicador(IndicadoresBasicos $indicador): ?SemaforoIndicadoresMedia
    {
        return $this->findOneBy([
            'indicadorBasico' => $indicador,
        ]);
    }

    /**
     * @param IndicadoresBasicos[] $indicadores
     */
    public function findIndexedByIndicadores(array $indicadores): array
    {
        if ($indicadores === []) {
            return [];
        }

        $registros = $this->createQueryBuilder('m')
            ->andWhere('m.indicadorBasico IN (:indicadores)')
            ->setParameter('indicadores', $indicadores)
            ->getQuery()
            ->getResult();

        $indexed = [];

        foreach ($registros as $registro) {
            $indexed[$registro->getIndicadorBasico()?->getId()] = $registro;
        }

        return $indexed;
    }
}
