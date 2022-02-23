<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\ApiProblem\Http\ApiProblem;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Serialization\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function assert;
use function is_array;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class BadResponseExceptionSubscriber implements EventSubscriberInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof BadRequestException) {
            return;
        }

        $data = $this->serializer->serialize($exception->getResponse()->getErrors(), 'json');
        assert(is_string($data));

        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($data));

        $problem = new ApiProblem(Response::HTTP_BAD_REQUEST, $data);

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
