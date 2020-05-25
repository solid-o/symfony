<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\BodyConverter\BodyConverter as Converter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BodyConverter implements EventSubscriberInterface
{
    private Converter $bodyConverter;

    public function __construct(Converter $bodyConverter)
    {
        $this->bodyConverter = $bodyConverter;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $parameterBag = $this->bodyConverter->decode($request);

        if ($parameterBag->count() === 0 && $request->request->count() !== 0) {
            return;
        }

        $request->request = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 35],
        ];
    }
}
