<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/** @Annotation() */
#[Attribute]
class Security
{
    public const ACCESS_DENIED_EXCEPTION = 'access_denied';
    public const RETURN_NULL = 'null';

    /** @Required() */
    public string $expression;

    public ?string $message = null;

    /** @Enum({"access_denied", "null"}) */
    public string $onInvalid = self::ACCESS_DENIED_EXCEPTION;

    /**
     * @param string|array<string, mixed> $expression
     * @phpstan-param string|array{expression?: string, message?: string, onInvalid: ?string, value?: string} $expression
     */
    public function __construct($expression, ?string $message = null, ?string $onInvalid = null)
    {
        if (is_string($expression)) {
            $data = ['expression' => $expression];
        } elseif (is_array($expression)) {
            $data = $expression;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($expression)));
        }

        $this->expression = $data['expression'] ?? $data['value'] ?? 'true';
        $this->message = $message ?? $data['message'] ?? null;
        $this->onInvalid = $onInvalid ?? $data['onInvalid'] ?? self::ACCESS_DENIED_EXCEPTION;
    }
}
