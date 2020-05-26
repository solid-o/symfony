<?php

declare(strict_types=1);

namespace Solido\Symfony\ErrorRenderer\Exception;

use Symfony\Component\ErrorHandler\Exception\FlattenException;

/**
 * Used as helper to serialize exceptions when debug is enabled
 * Exposes stack traces.
 *
 * @internal
 */
class DebugSerializableException extends SerializableException
{
    /**
     * @var array<array<string, mixed>>
     * @phpstan-var array<array{message: string, class: class-string|null, trace: mixed}>
     */
    private array $exception;

    public function __construct(FlattenException $exception)
    {
        parent::__construct($exception);

        $this->exception = $exception->toArray();
        $this->errorMessage = $exception->getMessage();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getException(): array
    {
        return $this->exception;
    }
}
