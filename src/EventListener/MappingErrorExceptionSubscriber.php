<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\ApiProblem\Http\DataInvalidProblem;
use Solido\DataMapper\Exception\MappingErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MappingErrorExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof MappingErrorException) {
            return;
        }

        $problem = new DataInvalidProblem($exception->getResult());
        $event->setResponse($problem->toResponse());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
