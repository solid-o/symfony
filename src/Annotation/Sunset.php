<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
use InvalidArgumentException;
use Solido\Symfony\Configuration\ConfigurationInterface;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation()
 */
#[Attribute]
class Sunset implements ConfigurationInterface
{
    /** @Required() */
    public string $date;

    /**
     * @param string|array<string, mixed> $date
     * @phpstan-param string|array{date?: string, value?: string} $date
     */
    public function __construct($date)
    {
        if (is_string($date)) {
            $data = ['date' => $date];
        } elseif (is_array($date)) {
            $data = $date;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($date)));
        }

        $date = $data['date'] ?? $data['value'] ?? null;
        if ($date === null) {
            throw new InvalidArgumentException('Date is required for Sunset annotation/attribute');
        }

        $this->date = $date;
    }

    public function getAliasName(): string
    {
        return 'solido_sunset';
    }
}
