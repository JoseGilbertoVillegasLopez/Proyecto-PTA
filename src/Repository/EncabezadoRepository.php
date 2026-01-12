<?php

namespace App\Repository;

use App\Entity\Encabezado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Encabezado>
 */
class EncabezadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Encabezado::class);
    }

    //    /**
    //     * @return Encabezado[] Returns an array of Encabezado objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Encabezado
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findVisiblePta(array $access, array $filters = []): array
{
    $qb = $this->createQueryBuilder('e')
        ->join('e.responsable', 'r')
        ->join('r.puesto', 'p');

    /* =====================================
     * ESTADO INICIAL — SOLO SU PTA
     * ===================================== */
    if (
        empty($filters['puesto']) &&
        empty($filters['departamento'])
    ) {
        $qb
            ->andWhere('r.id = :personal')
            ->setParameter('personal', $filters['personal_id'] ?? 0);
    }

    /* =====================================
     * FILTRO POR PUESTO
     * ===================================== */
    if (!empty($filters['puesto'])) {
        $qb
            ->andWhere('p.id = :puesto')
            ->setParameter('puesto', $filters['puesto']);
    }

    /* =====================================
     * FILTRO POR DEPARTAMENTO (SUBÁRBOL)
     * ===================================== */
    if (!empty($filters['departamento'])) {

        $puestoRepo = $this->getEntityManager()->getRepository(\App\Entity\Puesto::class);
        $puesto = $puestoRepo->find($filters['departamento']);

        if ($puesto) {
            $ids = [$puesto->getId()];

            foreach ($puesto->getSubordinadosRecursivos() as $sub) {
                $ids[] = $sub->getId();
            }

            $qb
                ->andWhere('p.id IN (:puestosDepto)')
                ->setParameter('puestosDepto', $ids);
        }
    }

    /* =====================================
     * FILTRO AÑO (SIEMPRE anioEjecucion)
     * ===================================== */
    if (!empty($filters['anio'])) {
        $qb
            ->andWhere('e.anioEjecucion = :anio')
            ->setParameter('anio', $filters['anio']);
    }

    return $qb
        ->orderBy('e.fechaCreacion', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findAniosDisponibles(array $access, int $personalId): array
{
    $qb = $this->createQueryBuilder('e')
        ->select('DISTINCT e.anioEjecucion AS anio')
        ->join('e.responsable', 'r')
        ->join('r.puesto', 'p');

    if ($access['scope'] === 'GLOBAL') {
        // sin restricción
    }
    elseif ($access['scope'] === 'JERARQUICO') {
        $qb
            ->andWhere('p.id IN (:puestos)')
            ->setParameter('puestos', $access['puestos_visibles']);
    }
    else {
        $qb
            ->andWhere('r.id = :personal')
            ->setParameter('personal', $personalId);
    }

    $rows = $qb
        ->orderBy('anio', 'DESC')
        ->getQuery()
        ->getArrayResult();

    return array_map(fn ($r) => (int) $r['anio'], $rows);
}


}
