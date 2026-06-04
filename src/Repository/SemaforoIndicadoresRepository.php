<?php

namespace App\Repository;

use App\Entity\CicloIndicadores;
use App\Entity\IndicadoresBasicos;
use App\Entity\SemaforoIndicadores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SemaforoIndicadores>
 */
class SemaforoIndicadoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SemaforoIndicadores::class);
    }

    public function findOneByIndicadorAndCiclo(IndicadoresBasicos $indicador, CicloIndicadores $ciclo): ?SemaforoIndicadores
    {
        return $this->findOneBy([
            'indicadorBasico' => $indicador,
            'ciclo' => $ciclo,
        ]);
    }

    /**
     * @param IndicadoresBasicos[] $indicadores
     * @param CicloIndicadores[] $ciclos
     */
    public function findIndexedByIndicadores(array $indicadores, array $ciclos = []): array
    {
        if ($indicadores === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.ciclo', 'c')
            ->addSelect('c')
            ->andWhere('s.indicadorBasico IN (:indicadores)')
            ->setParameter('indicadores', $indicadores);

        if ($ciclos !== []) {
            $qb
                ->andWhere('s.ciclo IN (:ciclos)')
                ->setParameter('ciclos', $ciclos);
        }

        $registros = $qb->getQuery()->getResult();

        $indexed = [];

        foreach ($registros as $registro) {
            $indexed[$registro->getIndicadorBasico()?->getId()][$registro->getCiclo()?->getId()] = $registro;
        }

        return $indexed;
    }
}
