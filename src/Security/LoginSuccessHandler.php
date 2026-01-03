<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage
    ) {}

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        // 🔴 Seguridad extra
        if (!$user instanceof User) {
            return new RedirectResponse(
                $this->router->generate('app_login')
            );
        }

        // 🔴 PRIMER LOGIN → cambiar contraseña
        if ($user->isCambiarPassword()) {
            return new RedirectResponse(
                $this->router->generate('app_cambiar_password')
            );
        }

        // 🟢 DASHBOARD SEGÚN ROL
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->router->generate('app_admin_dashboard')
            );
        }

        return new RedirectResponse(
            $this->router->generate('app_admin_dashboard')
        );
    }
}
