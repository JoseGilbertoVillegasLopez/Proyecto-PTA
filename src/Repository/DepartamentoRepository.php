<?php

namespace App\Repository;
// Namespace del repositorio dentro de la capa Repository

use App\Entity\Departamento;
// Importa la entidad Departamento que este repositorio va a manejar

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// Clase base de Doctrine que provee métodos comunes (find, findAll, etc.)

use Doctrine\Persistence\ManagerRegistry;
// Permite a Doctrine acceder al EntityManager correspondiente

/**
 * @extends ServiceEntityRepository<Departamento>
 * Indica que este repositorio está tipado para la entidad Departamento
 * (útil para autocompletado y análisis estático)
 */
class DepartamentoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // Constructor del repositorio

        parent::__construct($registry, Departamento::class);
        // Llama al constructor padre y le indica:
        //  - qué EntityManager usar
        //  - qué entidad maneja este repositorio (Departamento)
    }

    //    /**
    //     * @return Departamento[] Returns an array of Departamento objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
    //
    // Métodos de ejemplo generados automáticamente por Symfony
    // Sirven como plantilla para crear consultas personalizadas
    // Actualmente NO se usan y están correctamente comentados

    //    public function findOneBySomeField($value): ?Departamento
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    //
    // Otro método de ejemplo para obtener un solo resultado
    // Devuelve null si no se encuentra ningún registro

    public function findActivos()
    {
        // Método personalizado para obtener solo departamentos activos

        return $this->createQueryBuilder('d')
            // Crea un QueryBuilder usando "d" como alias de Departamento

            ->andWhere('d.activo = :activo')
            // Agrega condición WHERE para filtrar solo activos

            ->setParameter('activo', true)
            // Define el valor del parámetro :activo

            ->orderBy('d.nombre', 'ASC')
            // Ordena los resultados alfabéticamente por nombre

            ->getQuery()
            // Convierte el QueryBuilder en una consulta Doctrine

            ->getResult();
            // Ejecuta la consulta y devuelve un array de Departamento
    }

}
