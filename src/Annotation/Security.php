<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

/**
 * @Annotation()
 */
class Security
{
    public const ACCESS_DENIED_EXCEPTION = 'access_denied';
    public const RETURN_NULL = 'null';

    /** @Required() */
    public string $expression;

    public ?string $message = null;

    /** @Enum({"access_denied", "null"}) */
    public string $onInvalid = self::ACCESS_DENIED_EXCEPTION;
}
