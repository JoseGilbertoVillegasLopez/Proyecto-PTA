<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuloAccesoExtension extends AbstractExtension
{
    public function __construct(
        private ModuloAccesoResolver $resolver,
        private Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('es_encargado_modulo', $this->esEncargadoModulo(...)),
            new TwigFunction('tiene_acceso_modulo', $this->tieneAccesoModulo(...)),
        ];
    }

    public function esEncargadoModulo(string $slug): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->resolver->esEncargado($user, $slug);
    }

    public function tieneAccesoModulo(string $slug): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->resolver->tieneAcceso($user, $slug);
    }
}
