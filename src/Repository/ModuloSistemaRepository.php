<?php

namespace App\Repository;

use App\Entity\ModuloSistema;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuloSistema>
 */
class ModuloSistemaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuloSistema::class);
    }

    public function findOneBySlug(string $slug): ?ModuloSistema
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /** @return ModuloSistema[] */
    public function findAllActivos(): array
    {
        return $this->findBy(['activo' => true], ['label' => 'ASC']);
    }
}
