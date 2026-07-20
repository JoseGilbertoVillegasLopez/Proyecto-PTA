<?php

namespace App\Repository;

use App\Entity\SolicitudGastosConfiguracion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosConfiguracion>
 */
class SolicitudGastosConfiguracionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosConfiguracion::class);
    }

    /**
     * La migración siembra la fila id=1; este helper crea el default si por
     * alguna razón no existe (entorno recién migrado a mano, etc.).
     */
    public function obtener(): SolicitudGastosConfiguracion
    {
        $config = $this->find(1);

        if ($config === null) {
            $config = new SolicitudGastosConfiguracion();
            $em = $this->getEntityManager();
            $em->persist($config);
            $em->flush();
        }

        return $config;
    }
}
