<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\Cors\Exception\InvalidOriginException;
use Solido\Symfony\Cors\HandlerFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

use function assert;

class CorsListener implements EventSubscriberInterface
{
    private HandlerFactory $factory;

    public function __construct(HandlerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -768],
            KernelEvents::EXCEPTION => ['onException', 40],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof MethodNotAllowedHttpException) {
            return;
        }

        $request = $event->getRequest();
        if (! $request->isMethod(Request::METHOD_OPTIONS)) {
            return;
        }

        $handler = $this->factory->factory($request->getPathInfo(), $request->getHost());
        if ($handler === null) {
            return;
        }

        try {
            $response = $handler->handleCorsRequest($request, $exception->getHeaders()['Allow'] ?? null);
            assert($response instanceof Response);

            $event->setResponse($response);
            $event->allowCustomResponseCode();
        } catch (InvalidOriginException $exception) {
            // @ignoreException
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $handler = $this->factory->factory($request->getPathInfo(), $request->getHost());
        if ($handler === null) {
            return;
        }

        $handler->enhanceResponse($request, $response);
    }
}
