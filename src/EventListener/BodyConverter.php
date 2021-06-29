<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\BodyConverter\BodyConverterInterface as Converter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function class_exists;

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

        // @phpstan-ignore-next-line
        $request->request = class_exists(InputBag::class) ? new InputBag($parameterBag->all()) : $parameterBag;
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
