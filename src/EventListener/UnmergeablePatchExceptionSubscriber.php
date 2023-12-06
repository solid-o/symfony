<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\ApiProblem\Http\ApiProblem;
use Solido\PatchManager\Exception\UnmergeablePatchException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

        $problem = new ApiProblem(Response::HTTP_NOT_ACCEPTABLE, [
            'error' => $exception->getMessage() ?: 'Unmergeable resource.',
        ]);

        $event->setResponse($problem->toResponse());
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
