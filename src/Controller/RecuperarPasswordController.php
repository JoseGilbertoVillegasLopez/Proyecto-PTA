<?php

namespace App\Controller;

use App\Repository\PersonalRepository;
use App\Service\RecuperarPassword;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecuperarPasswordController extends AbstractController
{
    /**
     * -----------------------------------------------------------
     * Ruta: /recuperar-password
     * -----------------------------------------------------------
     * Esta acción muestra el formulario y procesa la solicitud
     * del usuario para recuperar su contraseña. Se valida:
     *  - El correo existe
     *  - El puesto coincide
     *  - El departamento coincide
     * Si todo es correcto → Se llama al servicio que genera
     * un nuevo password, lo hashéa, lo guarda y envía correo.
     * -----------------------------------------------------------
     */
    #[Route('/recuperar-password', name: 'app_recuperar_password')]
    public function mostrarYProcesarFormulario(
        Request $request,
        PersonalRepository $personalRepository,
        RecuperarPassword $servicioRecuperarPassword
    ): Response {

        // Variables para mostrar mensajes en la vista
        $error = null;
        $mensaje = null;

        // Si el usuario envió el formulario por método POST
        if ($request->isMethod('POST')) {

            // Obtener y limpiar los datos enviados por el usuario
            $email        = trim($request->request->get('email'));
            $puestoInput  = trim($request->request->get('puesto'));
            $deptoInput   = trim($request->request->get('departamento'));

            /**
             * -----------------------------------------------------------
             * 1️⃣ Buscar el registro de Personal según el correo
             * -----------------------------------------------------------
             * Este correo es único para cada persona, por eso se usa
             * como punto de verificación inicial.
             */
            $personal = $personalRepository->findOneBy(['correo' => $email]);

            if (!$personal) {
                // Si no hay coincidencia → se muestra error
                $error = "No existe ningún usuario con ese correo.";
            } else {

                /**
                 * -----------------------------------------------------------
                 * 2️⃣ Verificar que Personal tenga un Usuario asociado
                 * -----------------------------------------------------------
                 */
                $usuario = $personal->getUser();

                if (!$usuario) {
                    $error = "No se encontró una cuenta asociada a ese correo.";
                } else {

                    /**
                     * -----------------------------------------------------------
                     * 3️⃣ Validar puesto y departamento
                     * -----------------------------------------------------------
                     * Obtenemos el puesto y departamento REALES del registro
                     * y los comparamos con lo que el usuario escribió.
                     * -----------------------------------------------------------
                     */
                    $puestoReal = $personal->getPuesto()?->getNombre();
                    $deptoReal  = $personal->getDepartamento()?->getNombre();

                    // Comparación literal (podemos mejorarla luego si quieres: case-insensitive)
                    if ($puestoReal !== $puestoInput || $deptoReal !== $deptoInput) {
                        $error = "Los datos no coinciden. Verifica tu puesto y departamento.";
                    } else {

                        /**
                         * -----------------------------------------------------------
                         * 4️⃣ Todo coincide → Pedimos al servicio generar un nuevo password
                         * -----------------------------------------------------------
                         * Llamamos al método que:
                         *  - Genera un password temporal
                         *  - Lo hashéa
                         *  - Lo guarda en BD
                         *  - Pone cambiarPassword = true
                         *  - Envía correo con el password temporal
                         * -----------------------------------------------------------
                         */
                        $servicioRecuperarPassword->resetPasswordForUser($usuario);

                        // Mensaje de éxito que aparecerá en la vista
                        $mensaje = "Se generó una nueva contraseña y se envió a tu correo.";
                    }
                }
            }
        }

        /**
         * -----------------------------------------------------------
         * Renderiza la vista y pasa los mensajes (éxito o error).
         * -----------------------------------------------------------
         */
        return $this->render('security/recuperar_password.html.twig', [
            'error'   => $error,
            'mensaje' => $mensaje,
        ]);
    }
}
