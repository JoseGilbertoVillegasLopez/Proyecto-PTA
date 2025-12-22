<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class CambiarPasswordController extends AbstractController
{
    #[Route('/cambiar-password', name: 'app_cambiar_password')]
    public function cambiarPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $error = null;

        if ($request->isMethod('POST')) {

            $password1 = $request->request->get('password');
            $password2 = $request->request->get('password_confirm');

            if (!$password1 || !$password2) {
                $error = 'Ambos campos son obligatorios.';
            } elseif ($password1 !== $password2) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                // Hashear y guardar contraseña
                $hashedPassword = $passwordHasher->hashPassword($user, $password1);
                $user->setPassword($hashedPassword);

                // Marcar que ya no necesita cambiar contraseña
                $user->setCambiarPassword(false);

                $em->flush();

                // 👉 salir de esta vista y entrar al flujo normal
                return $this->redirectToRoute('app_admin_dashboard');



            }
        }

        return $this->render('security/cambiar_password.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
