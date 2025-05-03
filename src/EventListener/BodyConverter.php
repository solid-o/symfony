<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\BodyConverter\BodyConverterInterface as Converter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function class_exists;

class BodyConverter implements EventSubscriberInterface
{
    use RequestMatcherTrait;

    public function __construct(private readonly Converter $bodyConverter)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (! $this->requestMatches($request)) {
            return;
        }

        $parameters = $this->bodyConverter->decode($request);

        if (empty($parameters) && $request->request->count() !== 0) {
            return;
        }

        // @phpstan-ignore-next-line
        $request->request = class_exists(InputBag::class) ? new InputBag($parameters) : new ParameterBag($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 35],
        ];
    }
}
