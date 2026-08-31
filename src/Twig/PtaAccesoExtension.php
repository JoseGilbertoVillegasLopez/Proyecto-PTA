<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\Pta\PtaAccessResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PtaAccesoExtension extends AbstractExtension
{
    public function __construct(
        private readonly PtaAccessResolver $resolver,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pta_alcance', $this->ptaAlcance(...)),
        ];
    }

    /**
     * Alcance PTA del usuario actual: 'GLOBAL' | 'JERARQUICO' | 'PROPIO' | null (sin sesión/personal).
     * Reutiliza PtaAccessResolver — no duplica la lógica de alcance.
     */
    public function ptaAlcance(): ?string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $this->resolver->resolve($user)['scope'] ?? null;
    }
}
