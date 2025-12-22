<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse {
        /** @var User $user */
        $user = $token->getUser();

        // 🔴 PRIMER INICIO DE SESIÓN → cambiar contraseña
        if ($user->isCambiarPassword()) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_cambiar_password')
            );
        }

        // 🟢 LOGIN NORMAL → dashboard según rol
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_admin_dashboard')
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_admin_dashboard')
        );
    }
}
