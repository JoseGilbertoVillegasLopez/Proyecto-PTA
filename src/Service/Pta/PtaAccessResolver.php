<?php

namespace App\Service\Pta;

use App\Entity\User;
use App\Entity\Personal;
use App\Entity\Puesto;

class PtaAccessResolver
{
    /**
     * =====================================================
     * RESOLVER DE ACCESO PTA
     * -----------------------------------------------------
     * PRINCIPIOS:
     * - La jerarquía vive en PUESTO
     * - El resolver define ALCANCE, no consultas
     * - Departamento = Puesto con subordinados
     * =====================================================
     */
    public function resolve(User $user): array
    {
        /* =====================================
         * SEGURIDAD DEFENSIVA
         * ===================================== */
        $personal = $user->getPersonal();

        if (!$personal instanceof Personal) {
            return $this->baseAccess();
        }

        $puesto = $personal->getPuesto();

        /* =====================================
         * ACCESO GLOBAL
         * ===================================== */
        if (
            in_array('ROLE_ADMIN', $user->getRoles(), true) ||
            in_array('ROLE_DIRECCION_GENERAL', $user->getRoles(), true)
        ) {
            return $this->globalAccess();
        }

        /* =====================================
         * ACCESO JERÁRQUICO
         * ===================================== */
        if (
            in_array('ROLE_DIRECCION', $user->getRoles(), true) ||
            in_array('ROLE_SUBDIRECCION', $user->getRoles(), true)
        ) {

            if (!$puesto instanceof Puesto) {
                return $this->baseAccess();
            }

            $puestosVisibles = [];
            $departamentosVisibles = [];

            // Siempre incluir su propio puesto
            $puestosVisibles[$puesto->getId()] = $puesto;

            // Subordinados recursivos
            foreach ($puesto->getSubordinadosRecursivos() as $sub) {
                $puestosVisibles[$sub->getId()] = $sub;
            }

            // Detectar "departamentos" (puestos con subordinados)
            foreach ($puestosVisibles as $p) {
                if (count($p->getSubordinados()) > 0) {
                    $departamentosVisibles[] = $p->getId();
                }
            }

            return [
                'scope' => 'JERARQUICO',

                'puestos_visibles' => array_keys($puestosVisibles),

                'departamentos_visibles' => $departamentosVisibles,

                'filters' => [
                    'anio' => true,
                    'puesto' => true,
                    'departamento' => true,
                ],
            ];
        }

        /* =====================================
         * ACCESO PROPIO
         * ===================================== */
        return $this->baseAccess();
    }

    /* =====================================================
     * ACCESO BASE — SOLO SUS PTA
     * ===================================================== */
    private function baseAccess(): array
    {
        return [
            'scope' => 'PROPIO',

            'puestos_visibles' => [],

            'departamentos_visibles' => [],

            'filters' => [
                'anio' => true,
                'puesto' => false,
                'departamento' => false,
            ],
        ];
    }

    /* =====================================================
     * ACCESO GLOBAL — VE TODO
     * ===================================================== */
    private function globalAccess(): array
    {
        return [
            'scope' => 'GLOBAL',

            'puestos_visibles' => [],

            'departamentos_visibles' => [],

            'filters' => [
                'anio' => true,
                'puesto' => true,
                'departamento' => true,
            ],
        ];
    }
}
