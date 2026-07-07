<?php

namespace App\Repository;

use App\Entity\ReportePtaTrimestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReportePtaTrimestre>
 */
class ReportePtaTrimestreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportePtaTrimestre::class);
    }

    /**
     * Todos los reportes entregados (estado=true) con filtros opcionales.
     *
     * @return ReportePtaTrimestre[]
     */
    public function findEntregadosConFiltros(
        ?int $anio,
        ?int $trimestre,
        ?int $puestoId,
        ?int $departamentoId,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->join('r.encabezado', 'e')
            ->join('e.responsable', 'p')
            ->join('p.puesto', 'pu')
            ->join('p.departamento', 'd')
            ->where('r.estado = true')
            ->orderBy('r.anio', 'DESC')
            ->addOrderBy('r.trimestre', 'ASC')
            ->addOrderBy('p.ap_paterno', 'ASC');

        if ($anio !== null) {
            $qb->andWhere('r.anio = :anio')->setParameter('anio', $anio);
        }

        if ($trimestre !== null) {
            $qb->andWhere('r.trimestre = :trimestre')->setParameter('trimestre', $trimestre);
        }

        if ($puestoId !== null) {
            $qb->andWhere('pu.id = :puesto')->setParameter('puesto', $puestoId);
        } elseif ($departamentoId !== null) {
            $qb->andWhere('d.id = :departamento')->setParameter('departamento', $departamentoId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Años disponibles en reportes entregados, para el selector de filtro.
     *
     * @return int[]
     */
    public function findAniosEntregados(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('DISTINCT r.anio')
            ->where('r.estado = true')
            ->orderBy('r.anio', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'anio');
    }
}

