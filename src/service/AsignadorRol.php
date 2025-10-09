<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\Usuario;

/**
 * Extrae tu lógica de asignación de roles según Puesto.
 * Así la reutilizas aquí y no queda atada a un controller.
 */
class AsignadorRol
{
    public function asignarRolSegunPuesto(Usuario $usuario, Personal $personal): void
    {
        //obtengo el puesto de la persona
        $puesto = $personal->getPuesto(); 
        //obtengo el nombre del puesto en mayusculas y sin espacios al inicio o final
        $nombre = mb_strtoupper(trim($puesto?->getNombre()?? ''), 'UTF-8'); 
        //remuevo acentos para mayor compatibilidad
        $nombre = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $nombre);


        //asigno el rol segun el nombre del puesto
         if ($nombre === 'DIRECCION GENERAL'){
            $usuario->setRol('ROLE_DIRECCION_GENERAL');
         }
        elseif (in_array($nombre, [
            'DIRECCION ACADEMICA',
            'DIRECCION DE PLANEACION Y VINCULACION',
            'DIRECCION SUBDIRECCION DE SERVICIOS ADMINISTRATIVOS'
        ])) {
                $usuario->setRol('ROLE_DIRECCION');
            }
        elseif (in_array($nombre, [
            'SUBDIRECCION ACADEMICA',
            'SUBDIRECCION DE POSGRADO E INVESTIGACION',
            'SUBDIRECCION DE VINCULACION',
            'SUBDIRECCION DE PLANEACION'
        ])) {
                $usuario->setRol('ROLE_SUBDIRECCION');
            }
        elseif (in_array($nombre, [
            'DEV',
            'ADMIN'
        ])) {
                $usuario->setRol('ROLE_ADMIN');
            }
        else{
            $usuario->setRol('ROLE_USER');
        }
    }
}
