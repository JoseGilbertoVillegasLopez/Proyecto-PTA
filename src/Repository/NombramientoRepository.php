<?php

namespace App\Repository;

use App\Entity\Nombramiento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Nombramiento>
 */
class NombramientoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nombramiento::class);
    }

    /**
     * @return Nombramiento[]
     */
    public function findForHistorial(
        array $puestosDepartamentoIds,
        ?int $puestoId,
        ?bool $activo
    ): array {
        $qb = $this->createQueryBuilder('n')
            ->addSelect('personal', 'departamento', 'puesto')
            ->join('n.personal', 'personal')
            ->join('personal.departamento', 'departamento')
            ->join('personal.puesto', 'puesto')
            ->orderBy('personal.nombre', 'ASC')
            ->addOrderBy('personal.ap_paterno', 'ASC')
            ->addOrderBy('personal.ap_materno', 'ASC')
            ->addOrderBy('n.fecha_subida', 'DESC');

        if ($puestosDepartamentoIds !== []) {
            $qb
                ->andWhere('puesto.id IN (:puestosDepartamentoIds)')
                ->setParameter('puestosDepartamentoIds', $puestosDepartamentoIds);
        }

        if ($puestoId) {
            $qb
                ->andWhere('puesto.id = :puestoId')
                ->setParameter('puestoId', $puestoId);
        }

        if ($activo !== null) {
            $qb
                ->andWhere('n.activo = :activo')
                ->setParameter('activo', $activo);
        }

        return $qb->getQuery()->getResult();
    }
}
