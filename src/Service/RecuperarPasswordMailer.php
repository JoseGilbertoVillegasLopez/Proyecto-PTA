<?php

#este archivo es el servicio que envía el correo de bienvenida al usuario recién creado, incluyendo información relevante como su nombre 
#completo, puesto, departamento, usuario y una contraseña temporal si aplica. aprte de la configuración del servicio de correo en Symfony.
#los parámetros como la dirección de correo del remitente, el nombre del remitente, la dirección de soporte, el nombre de la aplicación y la
# URL de inicio de sesión se inyectan en el servicio a través del constructor.
# los datos los optenemos de la entidad Usuario y su relación con Personal, Puesto y Departamento.

namespace App\Service;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mime\Address;

final class RecuperarPasswordMailer # aqui se define la clase del servicio que se usará para enviar el correo de bienvenida, sirve para inyectar el
# servicio en el controlador
{
    public function __construct(
        private MailerInterface $mailer, # inyectamos el servicio de mailer de symfony
        private MessageBusInterface $bus, # inyectamos el servicio de bus de mensajería para enviar correos de forma asíncrona
        private string $fromEmail, # la dirección de correo del remitente
        private string $fromName, # el nombre del remitente
        private string $soporteEmail, # la dirección de correo de soporte
        private string $appName, # el nombre de la aplicación
        private string $loginUrl, # la URL de inicio de sesión
    ) {}

    public function sendBienvenida(User $usuario, ?string $passwordTemporal = null): void # el método que envía el correo de bienvenida
    {
        $personal = $usuario->getPersonal(); # obtenemos el objeto Personal relacionado con el Usuario
        $nombreCompleto = (string) $personal; # convertimos el objeto Personal a cadena usando el método __toString definido en la entidad Personal


        $email = (new TemplatedEmail()) // creamos el correo usando plantillas Twig para el cuerpo HTML y texto plano
            ->from(new Address($this->fromEmail, $this->fromName)) # configuramos el remitente del correo toamndo los datos inyectados en el constructor
            ->to(new Address($personal->getCorreo() ?? '')) # configuramos el destinatario del correo, usando el correo del personal relacionado o del usuario
            ->subject(sprintf('[%s] Bienvenido/a — Acceso a la plataforma', $this->appName)) # el asunto del correo
            ->htmlTemplate('admin/emails/recuperar_password.html.twig') # la plantilla Twig para el cuerpo HTML del correo
            ->textTemplate('admin/emails/recuperar_password.txt.twig') # la plantilla Twig para el cuerpo de texto plano del correo
            ->context([  # los datos que se pasan a las plantillas Twig para personalizar el correo
                'appName'         => $this->appName,
                'fecha'           => new \DateTimeImmutable(),
                'nombreCompleto'  => $nombreCompleto,
                'usuario'         => $usuario->getUsuario(),
                'passwordTemporal'=> $passwordTemporal,
                'loginUrl'        => $this->loginUrl,
                'soporteEmail'    => $this->soporteEmail,
            ]);

        // Con MAILER_DSN = null://null no saldrá realmente; más adelante activamos Brevo.
        $this->bus->dispatch(new SendEmailMessage($email));
    }
}
