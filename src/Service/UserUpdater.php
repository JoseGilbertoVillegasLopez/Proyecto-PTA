<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly AsignadorRol $asignadorRol,
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
        // 1️⃣ Buscar el Usuario asociado al Personal
        /** @var User|null $usuario */
        $usuario = $this->em->getRepository(User::class)
            ->findOneBy(['personal' => $personal]);

        // 2️⃣ Si no hay Usuario asociado, no hacemos nada
        if (!$usuario) {
            return;
        }

        // 3️⃣ Actualizar el correo y rol según el nuevo Personal
        $correoAnterior = (string) $usuario->getUsuario();
        $nuevoCorreo    = (string) $personal->getCorreo();

        $usuario->setUsuario($nuevoCorreo);
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal);

        // 5️⃣ Guardar los cambios en la base de datos
        $this->em->flush();
    }
}
