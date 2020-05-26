<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization;

/**
 * Represents a serialization interface
 *
 * Adapters should forward data, format and serialization groups to the underlying
 * implementations. Custom serializers can implement this interface to
 * create their own adapters.
 */
interface SerializerInterface
{
    /**
     * Serializes data to be returned as API response.
     *
     * @param mixed $data
     * @param array<string, mixed>|null $context
     *
     * @return mixed
     *
     * @phpstan-param array{groups?: string[]|null, type?: ?string, serialize_null?: bool, enable_max_depth?: bool} $context
     */
    public function serialize($data, string $format, ?array $context = null);
}
