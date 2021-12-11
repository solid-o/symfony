<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
use InvalidArgumentException;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation()
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Lock
{
    /** @Required() */
    public string $expression;

    /**
     * @param string|array<string, mixed> $expression
     * @phpstan-param string|array{expression?: string, value?: string} $expression
     */
    public function __construct($expression)
    {
        if (is_string($expression)) {
            $data = ['expression' => $expression];
        } elseif (is_array($expression)) {
            $data = $expression;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($expression)));
        }

        $expression = $data['expression'] ?? $data['value'] ?? null;
        if ($expression === null) {
            throw new InvalidArgumentException('Expression is required for Lock annotation/attribute');
        }

        $this->expression = $expression;
    }
}
