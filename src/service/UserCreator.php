<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * -------------------------------------------------------------
 * Servicio: UserCreator
 * -------------------------------------------------------------
 * Este servicio se encarga exclusivamente de CREAR un nuevo Usuario
 * a partir de una entidad Personal.
 *
 * Funcionalidades principales:
 *  - Crear un nuevo Usuario vinculado a un Personal.
 *  - Generar una contraseña temporal aleatoria.
 *  - Hashear (encriptar) la contraseña antes de guardarla.
 *  - Asignar el rol correcto según el puesto del Personal.
 *  - Enviar un correo de bienvenida con las credenciales generadas.
 *
 * Este servicio NO se usa para actualizaciones; solo para altas nuevas.
 * -------------------------------------------------------------
 */
class UserCreator
{
    /**
     * Inyección de dependencias necesarias.
     *
     * @param EntityManagerInterface         $em               Maneja la persistencia y comunicación con la base de datos.
     * @param UserPasswordHasherInterface    $passwordHasher   Hashea contraseñas según la configuración del proyecto (Argon2id, bcrypt, etc.).
     * @param PasswordGenerator              $passwordGeneratorGenera contraseñas temporales aleatorias y seguras.
     * @param WelcomeMailer                  $welcomeMailer    Envía los correos de bienvenida (asíncrono mediante Messenger).
     * @param AsignadorRol                   $asignadorRol     Determina el rol del usuario según su puesto.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly WelcomeMailer $welcomeMailer,
        private readonly AsignadorRol $asignadorRol,
    ) {}

    /**
     * Crea un nuevo Usuario a partir de un Personal.
     *
     * Flujo:
     *  1) Crear entidad Usuario y vincularla con el Personal.
     *  2) Asignar el correo del Personal como nombre de usuario (username).
     *  3) Asignar rol según el puesto (usando AsignadorRol).
     *  4) Generar contraseña aleatoria y hashearla.
     *  5) Persistir en la base de datos.
     *  6) Enviar correo de bienvenida con credenciales.
     *
     * @param Personal $personal La entidad Personal recién creada.
     * @return void
     */
    public function createFromPersonal(Personal $personal): void
    {
        // 1️⃣ Crear nueva entidad Usuario y vincularla con el Personal
        $usuario = new User();
        $usuario->setPersonal($personal);

        // 2️⃣ El nombre de usuario (username) será el correo del Personal
        $usuario->setUsuario($personal->getCorreo());

        // 3️⃣ Asignar el rol correspondiente según el puesto
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal);

        // 4️⃣ Generar una contraseña aleatoria segura
        $plainPassword = $this->passwordGenerator->generate();

        // Hashear la contraseña generada
        $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword);

        // Asignar el hash a la entidad Usuario
        $usuario->setPassword($hash);

        // 5️⃣ Sincronizar campo "activo" si existe en ambas entidades
        if (method_exists($usuario, 'setActivo') && method_exists($personal, 'isActivo')) {
            $usuario->setActivo((bool) $personal->isActivo());
        }

        // Guardar el nuevo usuario en base de datos
        $this->em->persist($usuario);
        $this->em->flush();

        // 6️⃣ Enviar correo de bienvenida con las credenciales generadas
        // Se envía el password en claro solo al mailer, nunca se guarda.
        $this->welcomeMailer->sendBienvenida($usuario, $plainPassword);
    }
}
