<?php

namespace App\Repository;

use App\Entity\ModuloAcceso;
use App\Entity\Puesto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuloAcceso>
 */
class ModuloAccesoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuloAcceso::class);
    }

    /**
     * Comprueba si un puesto tiene un rol en un módulo dado su slug.
     * Si $tipo es null, cualquier tipo cuenta (encargado o acceso).
     */
    public function existsForModuloSlugAndPuesto(string $slug, int $puestoId, ?string $tipo): bool
    {
        $qb = $this->createQueryBuilder('ma')
            ->select('COUNT(ma.id)')
            ->join('ma.modulo', 'm')
            ->where('m.slug = :slug')
            ->andWhere('ma.puesto = :puesto')
            ->setParameter('slug', $slug)
            ->setParameter('puesto', $puestoId);

        if ($tipo !== null) {
            $qb->andWhere('ma.tipo = :tipo')->setParameter('tipo', $tipo);
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Retorna los puestos asignados a un módulo con un tipo determinado.
     *
     * @return Puesto[]
     */
    public function findPuestosForModulo(string $slug, string $tipo): array
    {
        return $this->createQueryBuilder('ma')
            ->select('p')
            ->join('ma.puesto', 'p')
            ->join('ma.modulo', 'm')
            ->where('m.slug = :slug')
            ->andWhere('ma.tipo = :tipo')
            ->setParameter('slug', $slug)
            ->setParameter('tipo', $tipo)
            ->getQuery()
            ->getResult();
    }
}
