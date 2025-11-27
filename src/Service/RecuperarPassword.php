<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * -------------------------------------------------------------
 * Servicio: RecuperarPassword
 * -------------------------------------------------------------
 * Este servicio se encarga exclusivamente de crear un nuvo password para los usuario que lo olvidaron y piden el restablecimiento *
 * Funcionalidades principales:
 *  - Crear un nuevo password para el usuario.
 *  - Actualizar el campo canbiarPassword a true.
 *  - Generar una contraseña temporal aleatoria.
 *  - Hashear (encriptar) la contraseña antes de guardarla.
 *  - Enviar un correo de bienvenida con las credenciales generadas.
 *
 * Este servicio NO se usa para actualizaciones; solo para altas nuevas.
 * -------------------------------------------------------------
 */
class RecuperarPassword
{
    /**
     * Inyección de dependencias necesarias.
     *
     * @param EntityManagerInterface         $em               Maneja la persistencia y comunicación con la base de datos.
     * @param UserPasswordHasherInterface    $passwordHasher   Hashea contraseñas según la configuración del proyecto (Argon2id, bcrypt, etc.).
     * @param PasswordGenerator              $passwordGeneratorGenera contraseñas temporales aleatorias y seguras.
     * @param RecuperarPasswordMailer                  $RecuperarPasswordMailer    Envía los correos de bienvenida (asíncrono mediante Messenger).
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly RecuperarPasswordMailer $RecuperarPasswordMailer,
    ) {}

    /**
     * Crea un nuevo password a partir de un Usuario.
     *
     * Flujo:
     *  1) Crear entidad Usuario y vincularla con el Personal.
     *  4) Generar contraseña aleatoria y hashearla.
     *  5) Persistir en la base de datos.
     *  6) Enviar correo de bienvenida con credenciales.
     *
     * @param Personal $personal La entidad Personal recién creada.
     * @return void
     */
    public function createFromPersonal(User $usuario): void
    {

        // 4️⃣ Generar una contraseña aleatoria segura
        $plainPassword = $this->passwordGenerator->generate();

        // Hashear la contraseña generada
        $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword);

        // Asignar el hash a la entidad Usuario
        $usuario->setPassword($hash);


        //Indicar que se debe cambiar el password en el primer inicio de sesión
        $usuario->setCambiarPassword(true);
        // Guardar el nuevo usuario en base de datos
        $this->em->persist($usuario);
        $this->em->flush();

        // 6️⃣ Enviar correo de bienvenida con las credenciales generadas
        // Se envía el password en claro solo al mailer, nunca se guarda.
        $this->RecuperarPasswordMailer->sendBienvenida($usuario, $plainPassword);
    }
}
