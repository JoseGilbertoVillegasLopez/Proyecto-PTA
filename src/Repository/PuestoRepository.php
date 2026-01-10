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
    //
    // Método de ejemplo para obtener un solo resultado
    // Devuelve null si no se encuentra ningún registro
}
