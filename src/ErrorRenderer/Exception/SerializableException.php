<?php

declare(strict_types=1);

namespace Solido\Symfony\ErrorRenderer\Exception;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class is used as helper to serialize an exception.
 *
 * @internal
 */
class SerializableException
{
    protected string $errorMessage;
    protected int $errorCode;

    public function __construct(FlattenException $exception)
    {
        $this->errorMessage = Response::$statusTexts[$exception->getStatusCode()] ?? 'Unknown error';
        $this->errorCode = (int) $exception->getCode();
    }

    public function __toString(): string
    {
        return 'An error has occurred: ' . $this->errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
        ];
    }
}
