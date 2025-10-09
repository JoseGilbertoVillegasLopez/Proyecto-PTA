<?php

namespace App\Service;

use App\Entity\Personal;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Mantiene sincronía Personal ↔ Usuario.
 *
 * Responsabilidades:
 * - Crear o actualizar un Usuario en función de un Personal.
 * - Decidir si debe generarse un password temporal nuevo.
 * - Hashear el password de manera segura.
 * - Asignar el rol correcto según el Puesto del Personal.
 * - Disparar correo de bienvenida/actualización (asíncrono) con el password temporal.
 *
 * Notas:
 * - El "username" del sistema será SIEMPRE el correo de la persona (política acordada).
 * - El password en claro NUNCA se persiste; sólo se usa para enviarlo por correo.
 */
class UserAccountManager
{
    /**
     * Constructor con inyección de dependencias necesarias.
     *
     * @param EntityManagerInterface         $em               Para persistir/consultar entidades vía Doctrine.
     * @param UserPasswordHasherInterface    $passwordHasher   Para hashear el password con el algoritmo configurado.
     * @param PasswordGenerator              $passwordGeneratorGenerador de contraseñas temporales seguras.
     * @param WelcomeMailer                  $welcomeMailer    Servicio que envía correos (trabaja asíncrono con Messenger).
     * @param AsignadorRol                   $asignadorRol     Encapsula la lógica de mapeo Puesto → Rol.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,                 // Guarda/consulta entidades (INSERT/UPDATE/SELECT).
        private readonly UserPasswordHasherInterface $passwordHasher,// Hashea password (p.ej. argon2id configurado en security.yaml).
        private readonly PasswordGenerator $passwordGenerator,       // Genera password aleatorio (sólo en memoria).
        private readonly WelcomeMailer $welcomeMailer,               // Envía email de bienvenida/actualización (async).
        private readonly AsignadorRol $asignadorRol,                 // Asigna rol al Usuario en función del Puesto.
    ) {}

    /**
     * Sincroniza el Usuario a partir de un Personal (crear o editar).
     *
     * Flujo:
     *  1) Localiza si ya existe un Usuario vinculado al Personal.
     *  2) Si no existe, crea uno nuevo y lo vincula.
     *  3) Establece el username como el correo del Personal.
     *  4) Asigna rol según el Puesto.
     *  5) Decide si corresponde generar password nuevo.
     *  6) Si corresponde, genera password temporal, lo hashea y lo guarda.
     *  7) Sincroniza bandera de "activo" (si existe en ambas entidades).
     *  8) Guarda cambios con flush().
     *  9) Si hubo password nuevo, envía correo (asíncrono) con el temporal.
     *
     * @param Personal $personal  Entidad Personal ya gestionada por Doctrine (persistente o administrada).
     * @param bool     $isUpdate  true si se trata de edición de Personal; false si es creación.
     *
     * @return void
     */
    public function syncFromPersonal(Personal $personal, bool $isUpdate = false): void
    {
        // 1) Buscar si ya existe Usuario vinculado a este Personal
        /** @var Usuario|null $usuario */
        $usuario = $this->em
            ->getRepository(Usuario::class)     // Obtiene el repositorio de Usuario.
            ->findOneBy(['personal' => $personal]); // Busca por relación 'personal' = $personal.

        // 2) Determina si ya existía (booleano a partir de instancia)
        $existia = $usuario instanceof Usuario; // true si encontró un Usuario, false si es null.

        // 3) Si no existe usuario, crear uno nuevo y vincularlo
        if (!$existia) {
            $usuario = $this->crearUsuarioPara($personal); // Fabrica Usuario y lo vincula al Personal.
            $this->em->persist($usuario);                  // Marca la entidad para INSERT en flush().
        }

        // 4) El username del sistema será SIEMPRE el correo del Personal
        $nuevoCorreo    = trim((string) $personal->getCorreo());         // Toma correo actual desde Personal (limpiando espacios).
        $correoAnterior = $existia ? (string) $usuario->getUsuario() : null; // Si existía, recupera el username previo.
        $usuario->setUsuario($nuevoCorreo);                              // Alinea username del Usuario con el correo del Personal.

        // 5) Asignar rol según el Puesto (a través del helper AsignadorRol)
        $this->asignadorRol->asignarRolSegunPuesto($usuario, $personal); // Mapea Puesto → ROLE_*

        // 6) Decidir si hay que generar password nuevo:
        //    - SIEMPRE al crear Usuario
        //    - En edición, SOLO si cambió el correo
        $debePasswordNuevo = $this->requierePasswordNuevo(
            $existia ? $usuario : null, // Pasa el usuario actual si existía, o null si es nuevo.
            $personal,                  // El Personal fuente de la sincronización.
            $isUpdate,                  // Bandera de edición/creación.
            $correoAnterior,            // Username/correo previo (si había).
            $nuevoCorreo                // Username/correo nuevo (desde Personal).
        );

        // 7) Si corresponde, generar password temporal, hashearlo y guardarlo
        $plainPassword = null; // Variable local para retener el password temporal (sólo en memoria).
        if ($debePasswordNuevo) {
            $plainPassword = $this->passwordGenerator->generate();          // Crea password aleatorio seguro (CSPRNG).
            $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword); // Hashea usando algoritmo del usuario.
            $usuario->setPassword($hash);                                   // Persiste SÓLO el hash (nunca el claro).
        }

        // 8) (Opcional) Sincronizar "activo" si ambas entidades manejan esa bandera
        if (method_exists($usuario, 'setActivo') && method_exists($personal, 'isActivo')) {
            $usuario->setActivo((bool) $personal->isActivo()); // Copia el estado activo del Personal.
        }

        // 9) Asegurar relación inversa (por si el setter no se llamó en otro lado)
        if (method_exists($usuario, 'setPersonal')) {
            $usuario->setPersonal($personal); // Garantiza que Usuario → Personal está seteado.
        }

        // 10) Guardar cambios en DB (INSERT/UPDATE según corresponda)
        $this->em->flush(); // Ejecuta SQL pendiente: inserciones/actualizaciones.

        // 11) Enviar correo SÓLO si se generó un password nuevo
        //     (WelcomeMailer se ejecuta asíncronamente vía Messenger/queue)
        if ($plainPassword !== null) {
            // Firma esperada: sendBienvenida(Usuario $usuario, ?string $passwordTemporal = null): void
            // Pasamos el password temporal en claro únicamente al mailer (no a la base de datos).
            $this->welcomeMailer->sendBienvenida($usuario, $plainPassword);
        }
    }

    /**
     * Reglas de negocio para decidir si generamos password nuevo.
     *
     * @param Usuario|null $usuarioActual  Usuario existente o null si es creación.
     * @param Personal     $personal       Personal asociado (informativo si tu política cambia a futuro).
     * @param bool         $isUpdate       true si viene de edición; false si creación.
     * @param string|null  $correoAnterior Username/correo previo (si existía).
     * @param string       $nuevoCorreo    Username/correo nuevo (desde Personal).
     *
     * @return bool true si hay que generar password; false en caso contrario.
     */
    private function requierePasswordNuevo(
        ?Usuario $usuarioActual,
        Personal $personal,
        bool $isUpdate,
        ?string $correoAnterior,
        string $nuevoCorreo
    ): bool {
        if (!$usuarioActual) {
            return true; // Caso creación: siempre generamos password temporal.
        }
        if ($isUpdate && $correoAnterior !== $nuevoCorreo) {
            return true; // Cambio crítico (username/correo): regenerar password temporal.
        }
        return false; // Ediciones no críticas (p.ej. rol/puesto) no fuerzan password nuevo.
    }

    /**
     * Crea una instancia de Usuario nueva, con valores por defecto y relación al Personal.
     *
     * @param Personal $personal Personal a vincular.
     * @return Usuario Usuario preparado para persistir (persist() + flush()).
     */
    private function crearUsuarioPara(Personal $personal): Usuario
    {
        $u = new Usuario();                                   // Crea nueva entidad Usuario (aún no persistida).
        if (method_exists($u, 'setPersonal')) {
            $u->setPersonal($personal);                       // Vincula Usuario → Personal.
        }
        if (method_exists($u, 'setActivo') && method_exists($personal, 'isActivo')) {
            $u->setActivo((bool) $personal->isActivo());      // Copia el estado activo (si ambas entidades lo soportan).
        }
        return $u;                                            // Retorna la entidad lista para persistir.
    }
}
