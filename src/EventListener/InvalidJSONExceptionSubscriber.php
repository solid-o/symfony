<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\BodyConverter\Exception\DecodeException;
use Solido\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InvalidJSONExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof InvalidJSONException && ! $exception instanceof DecodeException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => $exception->getMessage() ?: 'Invalid document.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
