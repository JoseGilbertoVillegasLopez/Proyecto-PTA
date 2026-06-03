<?php

namespace App\Repository;

use App\Entity\HistorialIndicadorValor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistorialIndicadorValor>
 */
class HistorialIndicadorValorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistorialIndicadorValor::class);
    }

    /**
     * Devuelve todos los registros de un indicador para un mes específico,
     * ordenados del más reciente al más antiguo.
     * Útil para mostrar el historial de cambios de un mes en particular.
     */
    public function findByIndicadorYMes(int $indicadorId, int $mes): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.indicador = :indicador')
            ->andWhere('h.mes = :mes')
            ->setParameter('indicador', $indicadorId)
            ->setParameter('mes', $mes)
            ->orderBy('h.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
