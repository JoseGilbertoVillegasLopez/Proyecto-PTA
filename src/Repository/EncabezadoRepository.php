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



public function findPtasForMonitoring(array $access, int $anio): array
{
    $qb = $this->createQueryBuilder('e')
        ->join('e.responsable', 'r')
        ->join('r.puesto', 'p')
        ->andWhere('e.anioEjecucion = :anio')
        ->setParameter('anio', $anio);

    if ($access['scope'] === 'GLOBAL') {
        // ve todo
    }
    elseif ($access['scope'] === 'JERARQUICO') {
        $qb
            ->andWhere('p.id IN (:puestos)')
            ->setParameter('puestos', $access['puestos_visibles']);
    }
    else {
        // PROPIO
        $qb
            ->andWhere('r.id = :personal')
            ->setParameter('personal', $access['personal_id']);
    }

    return $qb
        ->orderBy('e.fechaCreacion', 'DESC')
        ->getQuery()
        ->getResult();
}


/**
 * =========================================================
 * PTA — HISTORIAL INDEX
 * ---------------------------------------------------------
 * Caso de uso exclusivo para:
 * /pta/historial (index)
 * =========================================================
 */
/**
 * =========================================================
 * PTA — HISTORIAL INDEX
 * =========================================================
 */
public function findForHistorialIndex(
    array $access,
    int $anio,
    ?int $personalId,
    ?int $puestoId
): array {

    $result = [
        'propio' => [],
        'puesto' => [],
    ];

    /* =====================================================
     * PTA PROPIO
     * ===================================================== */
    if ($personalId) {

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.responsable', 'p')
            ->andWhere('e.anioEjecucion = :anio')
            ->andWhere('p.id = :personalId')
            ->setParameter('anio', $anio)
            ->setParameter('personalId', $personalId)
            ->orderBy('e.id', 'DESC');

        // 🔒 Scope jerárquico
        if ($access['scope'] === 'JERARQUICO') {
            $qb->andWhere('p.puesto IN (:puestos)')
               ->setParameter('puestos', $access['puestos_visibles']);
        }

        $result['propio'] = $qb->getQuery()->getResult();
    }

    /* =====================================================
     * PTA DEL PUESTO (MISMO PUESTO, OTRO RESPONSABLE)
     * ===================================================== */
    if ($personalId && $puestoId) {

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.responsable', 'p')
            ->andWhere('e.anioEjecucion = :anio')
            ->andWhere('p.puesto = :puestoId')
            ->andWhere('p.id != :personalId')
            ->setParameter('anio', $anio)
            ->setParameter('puestoId', $puestoId)
            ->setParameter('personalId', $personalId)
            ->orderBy('e.id', 'DESC');

        // 🔒 Scope jerárquico
        if ($access['scope'] === 'JERARQUICO') {
            $qb->andWhere('p.puesto IN (:puestos)')
               ->setParameter('puestos', $access['puestos_visibles']);
        }

        $result['puesto'] = $qb->getQuery()->getResult();
    }

    return $result;
}


}
