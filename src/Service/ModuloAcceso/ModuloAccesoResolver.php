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

    /**
     * Retorna true si el usuario tiene cualquier tipo de acceso al módulo
     * (tanto 'encargado' como 'acceso').
     */
    public function tieneAcceso(User $user, string $slug): bool
    {
        $puestoId = $this->getPuestoId($user);
        if ($puestoId === null) {
            return false;
        }

        return $this->repo->existsForModuloSlugAndPuesto($slug, $puestoId, null);
    }

    /**
     * @return \App\Entity\Puesto[]
     */
    public function getPuestosConRol(string $slug, string $tipo): array
    {
        return $this->repo->findPuestosForModulo($slug, $tipo);
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
