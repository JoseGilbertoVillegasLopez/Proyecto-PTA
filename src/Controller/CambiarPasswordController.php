<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

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
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $password1 = $request->request->get('password');
            $password2 = $request->request->get('password_confirm');

        if ($password1 !== $password2) {
            $this->addFlash(
                'error',
                'Las contraseñas no coinciden. Por favor, verifica que ambas sean iguales'
            );

            return $this->redirectToRoute('app_cambiar_password');
        } else {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $password1)
                );
                $user->setCambiarPassword(false);

                $em->flush();

                // ADMIN → dashboard
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    return $this->redirectToRoute('app_admin_dashboard');
                }

                // TODOS LOS DEMÁS → PTA
                return $this->redirectToRoute('app_encabezado_index');

            }
        }

        return $this->render('security/cambiar_password.html.twig', [
            'personal' => $user->getPersonal(),
        ]);
    }
}
