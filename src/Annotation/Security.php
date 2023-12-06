<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;

#[Attribute]
class Security
{
    public const ACCESS_DENIED_EXCEPTION = 'access_denied';
    public const RETURN_NULL = 'null';

    public function __construct(
        public string $expression = 'true',
        public string|null $message = null,
        public string $onInvalid = self::ACCESS_DENIED_EXCEPTION,
    ) {
    }
}
