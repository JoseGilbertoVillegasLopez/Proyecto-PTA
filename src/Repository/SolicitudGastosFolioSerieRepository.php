<?php

namespace App\Repository;

use App\Entity\SolicitudGastosFolioSerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SolicitudGastosFolioSerie>
 */
class SolicitudGastosFolioSerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudGastosFolioSerie::class);
    }

    public function obtenerOCrear(string $serie): SolicitudGastosFolioSerie
    {
        $fila = $this->findOneBy(['serie' => $serie]);

        if ($fila === null) {
            $fila = new SolicitudGastosFolioSerie();
            $fila->setSerie($serie);
            $em = $this->getEntityManager();
            $em->persist($fila);
        }

        return $fila;
    }

    /** @return SolicitudGastosFolioSerie[] indexadas por serie */
    public function findTodasIndexadas(): array
    {
        $indexadas = [];
        foreach ($this->findAll() as $fila) {
            $indexadas[$fila->getSerie()] = $fila;
        }

        return $indexadas;
    }
}
