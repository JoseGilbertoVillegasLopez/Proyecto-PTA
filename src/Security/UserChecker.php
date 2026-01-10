<?php

namespace App\Security;
// Namespace del componente de seguridad de la aplicación

use App\Entity\User;
// Importa la entidad User para poder validar su tipo y propiedades

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
// Excepción especial que permite mostrar un mensaje personalizado al usuario
// cuando se bloquea el inicio de sesión

use Symfony\Component\Security\Core\User\UserCheckerInterface;
// Interfaz que Symfony usa para validar usuarios
// ANTES y DESPUÉS de la autenticación

use Symfony\Component\Security\Core\User\UserInterface;
// Interfaz base que implementan los usuarios del sistema

class UserChecker implements UserCheckerInterface
// Clase que se registra en security.yaml como user_checker
{
    public function checkPreAuth(UserInterface $user): void
    {
        // Método que Symfony ejecuta ANTES de validar la contraseña

        if (!$user instanceof User) {
            // Verifica que el usuario autenticado sea instancia de App\Entity\User
            // Evita errores si se usa otro tipo de usuario o autenticación
            return;
            // Si no es User, no se aplica ninguna validación adicional
        }

        if ($user->isActivo() !== true) {
            // Comprueba el estado lógico del usuario (activo / inactivo)
            // Se exige que el valor sea estrictamente true

            throw new CustomUserMessageAccountStatusException(
                // Lanza una excepción que bloquea el login

                'No es posible iniciar sesión. '
                .'Detectamos que esta cuenta fue dada de baja, por lo que el acceso ya no está disponible. '
                .'Si tienes dudas, estamos aquí para ayudarte.'
                // Mensaje que se mostrará directamente en la vista de login
                // No revela detalles técnicos ni lanza error 500
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Método que Symfony ejecuta DESPUÉS de una autenticación exitosa

        // no necesitamos nada aquí
        // Se deja vacío porque no se requiere validación posterior al login
    }
}
