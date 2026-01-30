<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Stringable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

use function assert;
use function is_string;

class ControllerVersionValidatorListener implements EventSubscriberInterface
{
    public function __construct(private ServiceLocatorRegistryInterface $registry)
    {
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /** @phpstan-var class-string<object> | null $interface */
        $interface = $request->attributes->get('_solido_dto_interface');
        if ($interface === null) {
            return;
        }

        $version = $request->attributes->get('_version', 'latest');
        assert($version instanceof Stringable || is_string($version));

        $version = (string) $version;
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
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 20],
        ];
    }
}
