<?php

declare(strict_types=1);

namespace Solido\Symfony\ErrorRenderer;

use Solido\ApiProblem\Http\ApiProblem;
use Solido\Symfony\ErrorRenderer\Exception\DebugSerializableException;
use Solido\Symfony\ErrorRenderer\Exception\SerializableException;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

use function assert;
use function json_encode;
use function method_exists;

use const JSON_THROW_ON_ERROR;

class SerializerErrorRenderer implements ErrorRendererInterface
{
    private ErrorRendererInterface $fallbackErrorRenderer;
    private RequestStack $requestStack;
    private string $exceptionClass;

    public function __construct(ErrorRendererInterface $fallbackErrorRenderer, RequestStack $requestStack, bool $debug = false)
    {
        $this->fallbackErrorRenderer = $fallbackErrorRenderer;
        $this->requestStack = $requestStack;
        $this->exceptionClass = $debug ? DebugSerializableException::class : SerializableException::class;
    }

    public function render(Throwable $exception): FlattenException
    {
        if (method_exists($this->requestStack, 'getMainRequest')) {
            $request = $this->requestStack->getMainRequest();
        } else {
            $request = $this->requestStack->getMasterRequest(); /* @phpstan-ignore-line */
        }

        if ($request === null) {
            return $this->fallbackErrorRenderer->render($exception);
        }

        $flatten = FlattenException::createFromThrowable($exception);
        $ex = new $this->exceptionClass($flatten);
        assert($ex instanceof SerializableException);

        $problem = new ApiProblem($flatten->getStatusCode(), $ex->toArray());
        $flatten->setAsString(json_encode($problem, JSON_THROW_ON_ERROR));
        $flatten->setHeaders([
            'Content-Type' => 'application/problem+json',
            'Vary' => 'Accept',
        ] + $flatten->getHeaders());

        return $flatten;
    }
}
