<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\Usuario;

/**
 * Encapsula la lógica de asignación de roles según el Puesto del Personal.
 *
 * Ventajas:
 * - Centraliza reglas para que no queden dispersas en controladores/servicios.
 * - Facilita pruebas unitarias y mantenimiento cuando cambien los nombres de puestos.
 */
class AsignadorRol
{
    /**
     * Asigna el rol apropiado al Usuario según el nombre del Puesto del Personal.
     *
     * Normaliza el nombre del puesto:
     * - Trim (espacios).
     * - Mayúsculas (multibyte).
     * - Reemplazo de acentos (compatibilidad de comparación).
     *
     * Reglas actuales:
     * - "DIRECCION GENERAL"  → ROLE_DIRECCION_GENERAL
     * - {DIRECCION ACADEMICA, DIRECCION DE PLANEACION Y VINCULACION,
     *    DIRECCION SUBDIRECCION DE SERVICIOS ADMINISTRATIVOS} → ROLE_DIRECCION
     * - {SUBDIRECCION ACADEMICA, SUBDIRECCION DE POSGRADO E INVESTIGACION,
     *    SUBDIRECCION DE VINCULACION, SUBDIRECCION DE PLANEACION} → ROLE_SUBDIRECCION
     * - {DEV, ADMIN} → ROLE_ADMIN
     * - Cualquier otro → ROLE_USER
     *
     * @param Usuario  $usuario  Usuario a modificar.
     * @param Personal $personal Personal del cual se extrae el Puesto.
     *
     * @return void
     */
    public function asignarRolSegunPuesto(Usuario $usuario, Personal $personal): void
    {
        // Obtiene la entidad Puesto relacionada con el Personal (puede ser null).
        $puesto = $personal->getPuesto();

        // Obtiene el nombre del puesto (o cadena vacía) y lo normaliza:
        // - trim: elimina espacios a los lados.
        // - mb_strtoupper(..., 'UTF-8'): convierte a MAYÚSCULAS respetando multibyte.
        $nombre = mb_strtoupper(trim($puesto?->getNombre() ?? ''), 'UTF-8');

        // Remueve acentos comunes (á,é,í,ó,ú → A,E,I,O,U) para robustecer la comparación por igualdad.
        $nombre = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $nombre);

        // Mapeo 1: nombre exactamente "DIRECCION GENERAL"
        if ($nombre === 'DIRECCION GENERAL') {
            $usuario->setRol('ROLE_DIRECCION_GENERAL'); // Aplica rol específico y retorna (implícito).
        }
        // Mapeo 2: Varios nombres que se consideran "dirección"
        elseif (in_array($nombre, [
            'DIRECCION ACADEMICA',
            'DIRECCION DE PLANEACION Y VINCULACION',
            'SUBDIRECCION DE SERVICIOS ADMINISTRATIVOS'
        ], true)) {
            $usuario->setRol('ROLE_DIRECCION');
        }
        // Mapeo 3: Subdirecciones
        elseif (in_array($nombre, [
            'SUBDIRECCION ACADEMICA',
            'SUBDIRECCION DE POSGRADO E INVESTIGACION',
            'SUBDIRECCION DE VINCULACION',
            'SUBDIRECCION DE PLANEACION'
        ], true)) {
            $usuario->setRol('ROLE_SUBDIRECCION');
        }
        // Mapeo 4: Perfiles técnicos/administradores globales
        elseif (in_array($nombre, [
            'DEV',
            'ADMIN'
        ], true)) {
            $usuario->setRol('ROLE_ADMIN');
        }
        // Por defecto: usuario estándar
        else{
            $usuario->setRol('ROLE_USER');
        }
    }
}
