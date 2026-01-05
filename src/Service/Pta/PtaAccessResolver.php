<?php

namespace App\Service\Pta;

use App\Entity\User;
use App\Entity\Personal;
use App\Entity\Puesto;
use App\Entity\Departamento;

class PtaAccessResolver
{
    /**
     * Resuelve el acceso del usuario al módulo PTA
     * según su rol y su contexto laboral.
     */
    public function resolve(User $user): array
    {
        /* =====================================
         * CONTEXTO LABORAL DEL USUARIO
         * ===================================== */
        $personal = $user->getPersonal();

        // Seguridad defensiva:
        // Usuario sin Personal asociado → solo sus PTA
        if (!$personal instanceof Personal) {
            return $this->baseAccess();
        }

        $puesto       = $personal->getPuesto();
        $departamento = $personal->getDepartamento();

        /* =====================================
         * ACCESO BASE (ROL NORMAL)
         * ===================================== */
        $access = $this->baseAccess();

        /* =====================================
         * ROLE_DIRECCION_GENERAL
         * Acceso total (solo supervisión)
         * ===================================== */
        if (in_array('ROLE_DIRECCION_GENERAL', $user->getRoles(), true)) {
            return $this->globalAccess();
        }

        /* =====================================
         * ROLE_ADMIN
         * Acceso total + UI administrativa
         * ===================================== */
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->globalAccess();
        }

        /* =====================================
         * ROLE_SUBDIRECCION
         * Ve todo su propio departamento
         * ===================================== */
        if (in_array('ROLE_SUBDIRECCION', $user->getRoles(), true)) {
            $access['scope'] = 'DEPARTAMENTAL';

            // IDs de departamentos visibles
            $access['departamentos'] = [
                $departamento->getId(),
            ];

            // Puede filtrar por puesto (solo dentro de su depto)
            $access['filters']['puesto'] = true;

            return $access;
        }

        /* =====================================
         * ROLE_DIRECCION
         * Ve múltiples departamentos según su puesto
         * ===================================== */
        if (in_array('ROLE_DIRECCION', $user->getRoles(), true)) {
            $access['scope'] = 'MULTI_DEPARTAMENTAL';

            // IDs de departamentos permitidos
            $access['departamentos'] =
                $this->resolveDepartamentosPorDireccion(
                    $puesto,
                    $departamento
                );

            // Puede filtrar por departamento y puesto
            $access['filters']['departamento'] = true;
            $access['filters']['puesto'] = true;

            return $access;
        }

        /* =====================================
         * ROL BASE
         * ===================================== */
        return $access;
    }

    /* =====================================================
     * ACCESO BASE (ROL NORMAL)
     * ===================================================== */
    private function baseAccess(): array
    {
        return [
            'scope' => 'PROPIO',

            // Para PROPIO no se usan departamentos
            'departamentos' => [],

            'filters' => [
                'anio' => true,
                'departamento' => false,
                'puesto' => false,
            ],
        ];
    }

    /* =====================================================
     * ACCESO GLOBAL (ADMIN / DIRECCIÓN GENERAL)
     * ===================================================== */
    private function globalAccess(): array
    {
        return [
            'scope' => 'GLOBAL',

            // GLOBAL no restringe departamentos
            'departamentos' => [],

            'filters' => [
                'anio' => true,
                'departamento' => true,
                'puesto' => true,
            ],
        ];
    }

    /**
     * Resuelve los IDs de departamentos visibles para una DIRECCIÓN
     * según el puesto que ocupa.
     *
     * IMPORTANTE:
     * - Los IDs están CONTROLADOS por scripts
     * - NO deben cambiar entre entornos
     *
     * @return int[] IDs de Departamento
     */
    private function resolveDepartamentosPorDireccion(
        ?Puesto $puesto,
        Departamento $departamentoActual
    ): array {
        $puestoId = $puesto?->getId();

        return match ($puestoId) {

            // =================================================
            // DIRECCIÓN ACADÉMICA
            // =================================================
            10 => [ // ID Puesto: Dirección Académica
                21, // ID Departamento: Subdirección Académica
                22, // ID Departamento: Subdirección Posgrado e Investigación
            ],

            // =================================================
            // DIRECCIÓN PLANEACIÓN Y VINCULACIÓN
            // =================================================
            11 => [ // ID Puesto: Dirección Planeación y Vinculación
                23, // ID Departamento: Subdirección de Planeación
                24, // ID Departamento: Subdirección de Vinculación
            ],

            // =================================================
            // DIRECCIÓN SERVICIOS ADMINISTRATIVOS
            // =================================================
            12 => [ // ID Puesto: Dirección Servicios Administrativos
                $departamentoActual->getId(),
            ],

            // =================================================
            // FALLBACK SEGURO
            // =================================================
            default => [
                $departamentoActual->getId(),
            ],
        };
    }
}
