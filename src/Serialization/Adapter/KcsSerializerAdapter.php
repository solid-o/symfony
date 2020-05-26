<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization\Adapter;

use Kcs\Serializer\Exception\UnsupportedFormatException as KcsUnsupportedFormatExceptionAlias;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Solido\Symfony\Serialization\Exception\UnsupportedFormatException;
use Solido\Symfony\Serialization\SerializerInterface as SerializerAdapterInterface;
use function assert;

class KcsSerializerAdapter implements SerializerAdapterInterface
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
        $serializerContext = SerializationContext::create()
            ->setGroups($context['groups'] ?? $this->defaultGroups)
            ->setSerializeNull($context['serialize_null'] ?? true);

        if ($context['enable_max_depth'] ?? false) {
            $serializerContext->enableMaxDepthChecks();
        }

        assert($serializerContext instanceof SerializationContext);

        try {
            return $this->serializer->serialize($data, $format, $serializerContext, isset($context['type']) ? Type::parse($context['type']) : null);
        } catch (KcsUnsupportedFormatExceptionAlias $e) {
            throw new UnsupportedFormatException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
