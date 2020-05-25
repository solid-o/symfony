<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\PatchManager\Exception\FormNotSubmittedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FormNotSubmittedExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof FormNotSubmittedException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => 'No data sent.',
            'name' => $exception->getForm()->getName(),
        ], Response::HTTP_BAD_REQUEST));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
