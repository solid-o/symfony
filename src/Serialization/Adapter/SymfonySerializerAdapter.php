<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization\Adapter;

use Solido\Symfony\Serialization\Exception\UnsupportedFormatException;
use Solido\Symfony\Serialization\SerializerInterface as SerializerAdapterInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerAdapter implements SerializerAdapterInterface
{
    /** @var string[] */
    private array $defaultGroups;
    private SerializerInterface $serializer;

    /**
     * @param string[] $defaultGroups
     */
    public function __construct(SerializerInterface $serializer, array $defaultGroups = ['Default'])
    {
        $this->serializer = $serializer;
        $this->defaultGroups = $defaultGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, string $format, ?array $context = null)
    {
        $context = [
            'groups' => $context['groups'] ?? $this->defaultGroups,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => $context['serialize_null'] ?? true,
        ];

        try {
            return $this->serializer->serialize($data, $format, $context);
        } catch (NotEncodableValueException $e) {
            throw new UnsupportedFormatException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
