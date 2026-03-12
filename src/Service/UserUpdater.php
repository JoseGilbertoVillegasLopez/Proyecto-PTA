<?php

namespace App\Service;
// Namespace del servicio encargado de lógica de dominio relacionada con usuarios

use App\Entity\Personal;
// Importa la entidad Personal, que es el origen de los cambios

use App\Entity\User;
// Importa la entidad User que será actualizada

use Doctrine\ORM\EntityManagerInterface;
// Permite interactuar con Doctrine para persistir cambios

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// Servicio de Symfony para hashear contraseñas (inyectado aunque aquí no se use)

/**
 * -------------------------------------------------------------
 * Servicio: UserUpdater
 * -------------------------------------------------------------
 * Este servicio se encarga exclusivamente de ACTUALIZAR la información
 * de un Usuario ya existente, a partir de los cambios hechos en Personal.
 *
 * Funcionalidades principales:
 *  - Buscar el Usuario asociado al Personal.
 *  - Actualizar correo y rol según cambios en el Personal.
 *  - Regenerar contraseña si cambió el correo.
 *  - Reenviar correo de credenciales si hubo cambio de usuario/correo.
 *
 * Este servicio NO crea usuarios nuevos.
 * -------------------------------------------------------------
 */
class UserUpdater
{
    /**
     * Inyección de dependencias necesarias.
     *
     * @param EntityManagerInterface         $em               Maneja operaciones de base de datos.
     * @param UserPasswordHasherInterface    $passwordHasher   Hashea contraseñas seguras.
     * @param PasswordGenerator              $passwordGeneratorGenerador de contraseñas aleatorias.
     * @param AsignadorRol                   $asignadorRol     Determina el rol según el puesto.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        // EntityManager para sincronizar cambios con la BD

        private readonly UserPasswordHasherInterface $passwordHasher,
        // Servicio de hash (inyectado para futuras extensiones, no usado aquí)

        private readonly PasswordGenerator $passwordGenerator,
        // Generador de contraseñas (inyectado para futuros flujos)

        private readonly AsignadorRol $asignadorRol,
        // Servicio que encapsula la lógica de asignación de roles
    ) {}

    /**
     * Sincroniza los cambios de un Personal con su Usuario asociado.
     *
     * Flujo:
     *  1) Buscar el Usuario vinculado al Personal.
     *  2) Si no existe, terminar (no crea nuevos).
     *  3) Actualizar correo y rol según el Personal.
     *  4) Si cambió el correo, generar nueva contraseña y reenviar credenciales.
     *  5) Guardar cambios en base de datos.
     *
     * @param Personal $personal Entidad Personal con cambios recientes.
     * @return void
     */
    public function updateFromPersonal(Personal $personal): void
    {
        // ✅ TOMAR EL USER DESDE LA RELACIÓN
        $usuario = $personal->getUser();
        // Obtiene el usuario asociado usando la relación bidireccional
        // No se consulta el repositorio: se confía en el estado del objeto

        if (!$usuario) {
            // Si el Personal no tiene usuario asociado
            // este servicio NO crea uno nuevo
            return;
            // Se corta la ejecución silenciosamente
        }

        // correo
        $usuario->setUsuario($personal->getCorreo());
        // Sincroniza el nombre de usuario (login) con el correo del Personal

        // rol
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal);
        // Recalcula el rol en caso de que el puesto haya cambiado

        // 🔥 ACTIVO / INACTIVO
        $usuario->setActivo($personal->isActivo() === true);
        // Sincroniza el estado lógico del usuario
        // Se fuerza comparación estricta para evitar null o valores ambiguos

        // 🔥 FORZAR GUARDADO
        $this->em->persist($usuario);
        // Marca explícitamente el usuario como gestionado por Doctrine
        // Aunque no siempre es necesario, deja la intención clara

        $this->em->flush();
        // Ejecuta el UPDATE real en la base de datos
    }
}
