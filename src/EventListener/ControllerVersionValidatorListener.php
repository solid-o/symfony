<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerVersionValidatorListener implements EventSubscriberInterface
{
    private ServiceLocatorRegistryInterface $registry;

    public function __construct(ServiceLocatorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $interface = $request->attributes->get('_solido_dto_interface');

        if ($interface === null) {
            return;
        }

        $version = (string) $request->attributes->get('_version', 'latest');
        if ($version === 'latest') {
            return;
        }

        if (! $this->registry->has($interface)) {
            throw new NotFoundHttpException('Resource not found');
        }

        $locator = $this->registry->get($interface);
        if (! $locator->has($version)) {
            throw new NotFoundHttpException('This endpoint is not available for the requested version');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): iterable
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 20],
        ];
    }
}
