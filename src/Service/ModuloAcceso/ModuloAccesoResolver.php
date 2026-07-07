<?php

namespace App\Service\ModuloAcceso;

use App\Entity\User;
use App\Repository\ModuloAccesoRepository;

class ModuloAccesoResolver
{
    public function __construct(
        private ModuloAccesoRepository $repo,
    ) {}

    public function esEncargado(User $user, string $slug): bool
    {
        $puestoId = $this->getPuestoId($user);
        if ($puestoId === null) {
            return false;
        }

        return $this->repo->existsForModuloSlugAndPuesto($slug, $puestoId, 'encargado');
    }

    public function tieneAcceso(User $user, string $slug): bool
    {
        $puestoId = $this->getPuestoId($user);
        if ($puestoId === null) {
            return false;
        }

        return $this->repo->existsForModuloSlugAndPuesto($slug, $puestoId, 'acceso');
    }

    /**
     * @return \App\Entity\Puesto[]
     */
    public function getPuestosConRol(string $slug, string $tipo): array
    {
        return $this->repo->findPuestosForModulo($slug, $tipo);
    }

    /**
     * Cargo del encargado ('revisor'|'supervisor'|'autoriza') para módulos que lo usan.
     * Devuelve null si el usuario no es encargado del módulo o el módulo no usa cargos.
     */
    public function getCargoEncargado(User $user, string $slug): ?string
    {
        $puestoId = $this->getPuestoId($user);
        if ($puestoId === null) {
            return null;
        }

        $acceso = $this->repo->findOneForModuloSlugAndPuesto($slug, $puestoId, 'encargado');

        return $acceso?->getCargo();
    }

    private function getPuestoId(User $user): ?int
    {
        $personal = $user->getPersonal();
        if ($personal === null) {
            return null;
        }

        $puesto = $personal->getPuesto();
        if ($puesto === null) {
            return null;
        }

        return $puesto->getId();
    }
}
