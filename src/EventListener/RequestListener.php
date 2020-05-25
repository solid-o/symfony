<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Solido\Symfony\Request\FormatGuesserInterface;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\NoConfigurationException;

class RequestListener implements EventSubscriberInterface
{
    private FormatGuesserInterface $formatGuesser;
    private ?VersionGuesserInterface $versionGuesser;
    private bool $debug;

    public function __construct(
        FormatGuesserInterface $formatGuesser,
        ?VersionGuesserInterface $versionGuesser,
        bool $debug = false
    ) {
        $this->formatGuesser = $formatGuesser;
        $this->versionGuesser = $versionGuesser;
        $this->debug = $debug;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $version = 'latest';
        $format = $this->formatGuesser->guess($request);

        $format = $format !== null ? $request->getFormat($format) : null;
        if ($format === null) {
            $response = new Response('No format acceptable', Response::HTTP_NOT_ACCEPTABLE, ['Content-Type' => 'text/plain']);
            $event->setResponse($response);

            return;
        }

        if ($this->versionGuesser !== null) {
            $version = $this->versionGuesser->guess($request, $version);
        }

        $request->attributes->set('_format', $format);
        $request->attributes->set('_version', $version);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        if (! $this->debug || ! $e instanceof NotFoundHttpException) {
            return;
        }

        if (! $e->getPrevious() instanceof NoConfigurationException) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_format', 'html');
        $request->setRequestFormat('html');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 40],
            KernelEvents::EXCEPTION => ['onKernelException', 250],
        ];
    }
}
