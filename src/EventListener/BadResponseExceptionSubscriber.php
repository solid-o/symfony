<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Symfony\Serialization\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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

        $content = $this->prepareContent($exception->getResponse(), $event->getRequest());

        $event->setResponse(new Response($content, Response::HTTP_BAD_REQUEST));
    }

    private function prepareContent(BadResponse $response, Request $request): string
    {
        $format = $request->attributes->get('_format') ?? 'json';

        return $this->serializer->serialize($response->getErrors(), $format);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
