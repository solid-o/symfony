<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Attribute;
// phpcs:enable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Solido\Symfony\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_int;
use function Safe\sprintf;

/**
 * @Annotation()
 */
#[Attribute]
class View implements ConfigurationInterface
{
    public int $statusCode = Response::HTTP_OK;
    public ?string $groupsProvider = null;
    public ?string $serializationType = null;

    /** @var string[] */
    public array $groups = [];

    /**
     * @param int|array<string, mixed>|null $statusCode
     * @param string[]|array<string|int, mixed> $groups
     *
     * @phpstan-param mixed $statusCode
     */
    public function __construct($statusCode = null, ?string $groupsProvider = null, ?string $serializationType = null, ?array $groups = null)
    {
        if (is_int($statusCode)) {
            $data = ['statusCode' => $statusCode];
        } elseif (is_array($statusCode)) {
            $data = $statusCode;
        } elseif ($statusCode !== null) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be an int. %s passed', __METHOD__, get_debug_type($statusCode)));
        }

        $this->statusCode = $data['statusCode'] ?? $data['value'] ?? Response::HTTP_OK;
        $this->groupsProvider = $groupsProvider ?? $data['groupsProvider'] ?? null;
        $this->serializationType = $serializationType ?? $data['serializationType'] ?? null;
        $this->groups = $groups ?? $data['groups'] ?? [];
    }

    public function getAliasName(): string
    {
        return 'rest_view';
    }
}
