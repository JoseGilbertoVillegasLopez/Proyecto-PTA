<?php

namespace App\Service;
// Define el namespace del servicio dentro de la capa de servicios de la aplicación

use App\Entity\Personal;
// Importa la entidad Personal, origen de los datos para crear el usuario

use App\Entity\User;
// Importa la entidad User que será creada y persistida

use Doctrine\ORM\EntityManagerInterface;
// Permite interactuar con Doctrine para persistir entidades

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// Servicio oficial de Symfony para hashear contraseñas
// Respeta la configuración definida en security.yaml

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
        // EntityManager inyectado; se usa para persistir y sincronizar entidades

        private readonly UserPasswordHasherInterface $passwordHasher,
        // Servicio que aplica el hash configurado en security.yaml

        private readonly PasswordGenerator $passwordGenerator,
        // Servicio propio encargado de generar contraseñas seguras en texto plano

        private readonly WelcomeMailer $welcomeMailer,
        // Servicio encargado de enviar el correo de bienvenida

        private readonly AsignadorRol $asignadorRol,
        // Servicio que decide qué rol asignar según el puesto del Personal
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
        // Se instancia la entidad User, aún no persistida

        // 🔥 VINCULAR AMBOS LADOS DE LA RELACIÓN
        $usuario->setPersonal($personal); // lado dueño
        // Establece la relación desde User hacia Personal
        // Doctrine considera este lado como el dueño de la relación

        $personal->setUser($usuario);     // 👈 ESTA ES LA CLAVE (lado inverso)
        // Mantiene sincronizada la relación bidireccional
        // Evita inconsistencias en memoria y problemas con Doctrine

        // 2️⃣ El nombre de usuario (username) será el correo del Personal
        $usuario->setUsuario($personal->getCorreo());
        // Define el identificador de login usando el correo del personal

        // 3️⃣ Asignar el rol correspondiente según el puesto
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal);
        // Delegación completa de la lógica de roles
        // El controlador y el servicio no conocen reglas internas

        // 4️⃣ Generar una contraseña aleatoria segura
        $plainPassword = $this->passwordGenerator->generate();
        // Se genera la contraseña en texto plano SOLO para enviarla por correo

        // Hashear la contraseña generada
        $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword);
        // Aplica el algoritmo configurado (Argon2id, bcrypt, etc.)
        // Usa el usuario para aplicar sal/contexto correcto

        // Asignar el hash a la entidad Usuario
        $usuario->setPassword($hash);
        // Se guarda únicamente el hash, nunca la contraseña en claro

        // 5️⃣ Sincronizar campo "activo" si existe en ambas entidades
        if (method_exists($usuario, 'setActivo') && method_exists($personal, 'isActivo')) {
            // Verifica dinámicamente que ambas entidades tengan el campo
            // Evita errores si la propiedad no existe en alguna

            $usuario->setActivo((bool) $personal->isActivo());
            // Copia el estado activo/inactivo desde Personal hacia User
        }

        //Indicar que se debe cambiar el password en el primer inicio de sesión
        $usuario->setCambiarPassword(true);
        // Bandera usada por el LoginSuccessHandler
        // Obliga al usuario a cambiar su contraseña al primer login

        // Guardar el nuevo usuario en base de datos
        $this->em->persist($usuario);
        // Marca la entidad User para inserción

        $this->em->flush();
        // Ejecuta el INSERT real en la base de datos

        // 6️⃣ Enviar correo de bienvenida con las credenciales generadas
        // Se envía el password en claro solo al mailer, nunca se guarda.
        $this->welcomeMailer->sendBienvenida($usuario, $plainPassword);
        // Envía el correo con usuario y contraseña temporal
        // Después de este punto, el password plano deja de existir
    }
}
