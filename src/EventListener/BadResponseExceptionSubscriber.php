<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Symfony\Annotation\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class BadResponseExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof BadRequestException) {
            return;
        }

        $request = $this->duplicateRequest($exception->getResponse()->getErrors(), $event->getRequest());
        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);

        $event->setResponse($response);
    }

    /**
     * Clones the request for the exception.
     */
    protected function duplicateRequest(BadResponsePropertyTree $errors, Request $request): Request
    {
        $attributes = [
            '_controller' => [$this, 'errorAction'],
            '_security' => false,
            'errors' => $errors,
        ];

        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod(Request::METHOD_GET);

        return $request;
    }

    /**
     * This is public to be callable. DO NOT USE IT!
     * This method should be considered as private.
     *
     * @internal
     *
     * @View(Response::HTTP_BAD_REQUEST)
     */
    #[View(Response::HTTP_BAD_REQUEST)]
    public function errorAction(BadResponsePropertyTree $errors): BadResponsePropertyTree
    {
        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
