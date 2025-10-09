<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Mantiene sincronía Personal ↔ Usuario.
 * - Crea/actualiza Usuario a partir de Personal.
 * - Genera y hashea password cuando aplique.
 * - Asigna rol según el Puesto.
 * - Envía correo de bienvenida/actualización usando WelcomeMailer (asíncrono).
 */
class UserAccountManager
{
    // Inyectamos dependencias necesarias para la orquestación
    public function __construct(
        private readonly EntityManagerInterface $em,                 // Para persistir/consultar entidades
        private readonly UserPasswordHasherInterface $passwordHasher,// Para hashear el password (argon2id)
        private readonly PasswordGenerator $passwordGenerator,       // Generador de passwords aleatorios seguros
        private readonly WelcomeMailer $welcomeMailer,               // TU servicio para enviar el correo
        private readonly AsignadorRol $asignadorRol,                 // Lógica de asignar rol por puesto
    ) {}

    /**
     * Sincroniza el Usuario a partir de un Personal (crear/editar).
     *
     * @param Personal $personal  La entidad Personal recién creada/editada (ya gestionada por Doctrine).
     * @param bool     $isUpdate  true si fue edición, false si fue creación.
     */
    public function syncFromPersonal(Personal $personal, bool $isUpdate = false): void
    {
        // 1) Buscar si ya existe Usuario vinculado a este Personal
        /** @var Usuario|null $usuario */
        $usuario = $this->em->getRepository(Usuario::class)
            ->findOneBy(['personal' => $personal]);

        // 2) Saber si ya existía
        $existia = $usuario instanceof Usuario;

        // 3) Si no existe, crear uno nuevo y vincularlo
        if (!$existia) {
            $usuario = $this->crearUsuarioPara($personal); // set defaults + vinculación
            $this->em->persist($usuario);                  // marcar para INSERT
        }

        // 4) El username del sistema será SIEMPRE el correo del Personal
        $nuevoCorreo    = trim((string) $personal->getCorreo());
        $correoAnterior = $existia ? (string) $usuario->getUsuario() : null;
        $usuario->setUsuario($nuevoCorreo);

        // 5) Asignar rol según el Puesto (usa tu helper AsignadorRol)
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal);

        // 6) Decidir si hay que generar password nuevo:
        //    - SIEMPRE al crear Usuario
        //    - En edición, SOLO si cambió el correo (tu política acordada)
        $debePasswordNuevo = $this->requierePasswordNuevo(
            $existia ? $usuario : null,
            $personal,
            $isUpdate,
            $correoAnterior,
            $nuevoCorreo
        );

        // 7) Si corresponde, generamos el password, lo hasheamos y lo guardamos
        $plainPassword = null; // lo conservamos en memoria solo para enviarlo por email
        if ($debePasswordNuevo) {
            // Genera un password aleatorio seguro (CSPRNG)
            $plainPassword = $this->passwordGenerator->generate();

            // Hashea el password con el hasher configurado (argon2id según tu security.yaml)
            $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword);

            // Almacenamos el hash en la entidad Usuario
            $usuario->setPassword($hash);
        }

        // 8) (Opcional) Sincronizar "activo" si ambas entidades manejan esa bandera
        if (method_exists($usuario, 'setActivo') && method_exists($personal, 'isActivo')) {
            $usuario->setActivo((bool) $personal->isActivo());
        }

        // 9) Asegurar relación inversa por si no quedó seteada
        if (method_exists($usuario, 'setPersonal')) {
            $usuario->setPersonal($personal);
        }

        // 10) Guardar cambios en DB
        $this->em->flush();

        // 11) Enviar correo SI se generó un password nuevo
        //     (tu WelcomeMailer trabaja asíncrono por Messenger)
        if ($plainPassword !== null) {
            // Tu firma es: sendBienvenida(Usuario $usuario, ?string $passwordTemporal = null): void
            // Pasamos el password en claro SOLO al mailer (no se persiste).
            $this->welcomeMailer->sendBienvenida($usuario, $plainPassword);
        }
    }

    /**
     * Reglas de negocio para decidir si generamos password nuevo:
     * - Creación => true
     * - Edición  => true SOLO si cambió el correo
     */
    private function requierePasswordNuevo(
        ?Usuario $usuarioActual,
        Personal $personal,
        bool $isUpdate,
        ?string $correoAnterior,
        string $nuevoCorreo
    ): bool {
        if (!$usuarioActual) {
            return true; // Usuario nuevo
        }
        if ($isUpdate && $correoAnterior !== $nuevoCorreo) {
            return true; // Cambio crítico de usuario (correo)
        }
        return false; // Otros cambios (puesto/depto) no regeneran password
    }

    /**
     * Helper para instanciar Usuario nuevo con defaults y vinculación al Personal.
     */
    private function crearUsuarioPara(Personal $personal): Usuario
    {
        $u = new Usuario();                       // Nueva entidad Usuario
        if (method_exists($u, 'setPersonal')) {
            $u->setPersonal($personal);           // Vincular al Personal
        }
        if (method_exists($u, 'setActivo') && method_exists($personal, 'isActivo')) {
            $u->setActivo((bool) $personal->isActivo()); // opcional: mismo estado
        }
        return $u;                                // Listo para persistir
    }
}
