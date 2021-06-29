<?php

declare(strict_types=1);

namespace Solido\Symfony\ErrorRenderer;

use Solido\Serialization\Exception\UnsupportedFormatException;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\ErrorRenderer\Exception\DebugSerializableException;
use Solido\Symfony\ErrorRenderer\Exception\SerializableException;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

use function method_exists;

class SerializerErrorRenderer implements ErrorRendererInterface
{
    private ErrorRendererInterface $fallbackErrorRenderer;
    private RequestStack $requestStack;
    private SerializerInterface $serializer;
    private string $exceptionClass;

    /**
     * @var array<string, mixed>
     * @phpstan-var array{groups?: string[]|null, type?: ?string, serialize_null?: bool}
     */
    private array $serializationContext;

    /**
     * @param array<string, mixed> $serializationContext
     * @phpstan-param array{groups?: string[]|null, type?: ?string, serialize_null?: bool} $serializationContext
     */
    public function __construct(ErrorRendererInterface $fallbackErrorRenderer, RequestStack $requestStack, SerializerInterface $serializer, array $serializationContext, bool $debug = false)
    {
        $this->fallbackErrorRenderer = $fallbackErrorRenderer;
        $this->requestStack = $requestStack;
        $this->serializer = $serializer;
        $this->serializationContext = $serializationContext;
        $this->exceptionClass = $debug ? DebugSerializableException::class : SerializableException::class;
    }

    public function render(Throwable $exception): FlattenException
    {
        if (method_exists($this->requestStack, 'getMainRequest')) {
            $request = $this->requestStack->getMainRequest();
        } else {
            $request = $this->requestStack->getMasterRequest();
        }

        if ($request === null) {
            return $this->fallbackErrorRenderer->render($exception);
        }

        $flatten = FlattenException::createFromThrowable($exception);

        $format = $request->getRequestFormat() ?? 'json';
        $ex = new $this->exceptionClass($flatten);

        try {
            $data = $this->serializer->serialize($ex, $format, $this->serializationContext);
        } catch (UnsupportedFormatException $e) {
            return $this->fallbackErrorRenderer->render($exception);
        }

        $flatten->setAsString($data);
        $flatten->setHeaders([
            'Content-Type' => $request->getMimeType($format) ?? 'text/html',
            'Vary' => 'Accept',
        ] + $flatten->getHeaders());

        return $flatten;
    }
}
