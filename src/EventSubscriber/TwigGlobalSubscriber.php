<?php

namespace App\EventSubscriber;

use App\Service\Pta\PtaAccessResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private Security $security;
    private PtaAccessResolver $ptaAccessResolver;

    public function __construct(
        Environment $twig,
        Security $security,
        PtaAccessResolver $ptaAccessResolver
    ) {
        $this->twig = $twig;
        $this->security = $security;
        $this->ptaAccessResolver = $ptaAccessResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'injectGlobals',
        ];
    }

    public function injectGlobals(ControllerEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $ptaAccess = $this->ptaAccessResolver->resolve($user);

        $this->twig->addGlobal('ptaAccess', $ptaAccess);
    }
}
