<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization\Adapter;

use JMS\Serializer\Exception\UnsupportedFormatException as JMSUnsupportedFormatExceptionAlias;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Solido\Symfony\Serialization\Exception\UnsupportedFormatException;
use Solido\Symfony\Serialization\SerializerInterface as SerializerAdapterInterface;

class JmsSerializerAdapter implements SerializerAdapterInterface
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

        try {
            return $this->serializer->serialize($data, $format, $serializerContext, $context['type'] ?? null);
        } catch (JMSUnsupportedFormatExceptionAlias $e) {
            throw new UnsupportedFormatException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
