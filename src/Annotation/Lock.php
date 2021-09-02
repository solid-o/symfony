<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
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

        $this->expression = $data['expression'] ?? $data['value'];
    }
}
