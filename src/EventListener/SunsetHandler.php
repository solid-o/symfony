<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use DateTime;
use DateTimeImmutable;
use Solido\Symfony\Annotation\Sunset;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Sunset header field if Sunset annotation is found.
 *
 * @see https://tools.ietf.org/html/draft-wilde-sunset-header-10
 */
class SunsetHandler implements EventSubscriberInterface
{
    /**
     * Modify the response adding a Sunset header if needed.
     */
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        $annotation = $request->attributes->get('_solido_sunset');
        if (! $annotation instanceof Sunset) {
            return;
        }

        $date = new DateTimeImmutable($annotation->date);

        $response = $event->getResponse();
        $response->headers->set('Sunset', $date->format(DateTime::RFC2822));
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }
}
