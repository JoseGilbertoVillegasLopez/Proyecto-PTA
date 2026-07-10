<?php

namespace App\Repository;
// Namespace del repositorio dentro de la capa Repository

use App\Entity\Puesto;
// Importa la entidad Puesto que este repositorio va a manejar

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// Clase base de Doctrine que provee métodos comunes como find(), findAll(), etc.

use Doctrine\Persistence\ManagerRegistry;
// Permite a Doctrine acceder al EntityManager adecuado

/**
 * @extends ServiceEntityRepository<Puesto>
 * Indica que este repositorio está tipado para la entidad Puesto
 * Mejora autocompletado y análisis estático
 */
class PuestoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // Constructor del repositorio

        parent::__construct($registry, Puesto::class);
        // Llama al constructor padre indicando:
        //  - qué EntityManager usar
        //  - qué entidad maneja este repositorio (Puesto)
    }

    //    /**
    //     * @return Puesto[] Returns an array of Puesto objects
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
    //
    // Método de ejemplo generado automáticamente por Symfony
    // Sirve como plantilla para consultas personalizadas
    // Actualmente no se utiliza

    //    public function findOneBySomeField($value): ?Puesto
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * En la jerarquia institucional, los puestos con subordinados funcionan
     * como nodos de departamento.
     *
     * @return Puesto[]
     */
    public function findDepartamentosJerarquicos(): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.subordinados', 'subordinado')
            ->distinct()
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Puesto[]
     */
    public function findAllOrdenados(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Puesto[]
     */
    public function findSubordinadosRecursivosOrdenados(Puesto $departamento): array
    {
        $puestos = $departamento->getSubordinadosRecursivos()->toArray();

        usort(
            $puestos,
            static fn (Puesto $a, Puesto $b): int => strcmp(
                $a->getNombre() ?? '',
                $b->getNombre() ?? ''
            )
        );

        return $puestos;
    }

    /**
     * Puestos con serie capturada (única forma de participar en el folio por
     * serie de solicitud_gastos), ordenados por serie.
     *
     * @return Puesto[]
     */
    public function findConSerie(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.serie IS NOT NULL')
            ->andWhere("p.serie <> ''")
            ->orderBy('p.serie', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
