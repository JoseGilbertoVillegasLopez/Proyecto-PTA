<?php

// Definimos el espacio de nombres (namespace) de este controlador.
// Esto le indica a Symfony dónde se encuentra este archivo dentro de la estructura del proyecto.
namespace App\Controller;

// Importamos las clases necesarias que usaremos en este controlador:
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Clase base para controladores en Symfony.
use Symfony\Component\HttpFoundation\Response; // Clase para crear respuestas HTTP.
use Symfony\Component\Routing\Annotation\Route; // Permite definir rutas usando anotaciones encima de los métodos.
use Symfony\Component\Mailer\MailerInterface; // Interfaz del servicio Mailer de Symfony (para enviar correos).
use Symfony\Component\Mime\Email; // Clase para construir mensajes de correo electrónico.

// Definimos la clase del controlador. 
// Todos los controladores en Symfony suelen extender de AbstractController.
class TestEmailController extends AbstractController
{
    // Definimos una ruta (endpoint) llamada "/test-email".
    // Cuando un usuario acceda a esta URL, se ejecutará el método `sendTestEmail`.
    #[Route('/test-email', name: 'app_test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        // 🧩 Creamos una nueva instancia de la clase Email.
        // Aquí configuramos todos los datos que tendrá el correo electrónico.
        $email = (new Email())
            // Dirección del remitente (quién envía el correo).
            // Puedes usar cualquier dirección simulada, no tiene que existir realmente.
            ->from('noreply@pta-system.local')

            // Dirección del destinatario (a quién se envía).
            // En Mailtrap no importa cuál pongas; todos los correos se capturan en tu sandbox.
            ->to('test@example.com')

            // Asunto del correo.
            ->subject('Correo de prueba desde Symfony 🧩')

            // Cuerpo del correo en texto plano (opcional, por si el cliente no soporta HTML).
            ->text('¡Hola mi loco! Este es un correo de prueba enviado desde Symfony con Mailtrap 😎')

            // Cuerpo del correo en formato HTML (lo que verá el usuario en Mailtrap).
            ->html('<h1 style="color: #007bff;">¡Hola mi loco!</h1>
                    <p>Este es un correo de prueba enviado desde <strong>Symfony</strong> usando <em>Mailtrap</em>. Todo funciona bien. 🚀</p>');

        // 🧠 Usamos un bloque try-catch para manejar errores al enviar el correo.
        try {
            // Enviamos el correo usando el servicio Mailer.
            // Symfony internamente usa el MAILER_DSN configurado en el archivo .env.local.
            $mailer->send($email);

            // Si todo sale bien, devolvemos una respuesta HTTP (200 OK)
            // indicando que el correo fue enviado correctamente.
            return new Response('✅ Correo de prueba enviado correctamente.');
        } catch (\Exception $e) {
            // Si ocurre algún error (por ejemplo, credenciales incorrectas o sin conexión),
            // capturamos la excepción y mostramos el mensaje de error.
            return new Response('❌ Error al enviar el correo: ' . $e->getMessage());
        }
    }
}
