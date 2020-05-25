<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\PatchManager\Exception\UnmergeablePatchException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UnmergeablePatchExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof UnmergeablePatchException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => $exception->getMessage() ?: 'Unmergeable resource.',
        ], Response::HTTP_NOT_ACCEPTABLE));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
