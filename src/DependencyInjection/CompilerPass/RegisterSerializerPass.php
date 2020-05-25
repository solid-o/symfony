<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Solido\Symfony\DependencyInjection\Configuration;
use Solido\Symfony\Serialization\Adapter\JmsSerializerAdapter;
use Solido\Symfony\Serialization\Adapter\KcsSerializerAdapter;
use Solido\Symfony\Serialization\Adapter\SymfonySerializerAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterSerializerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), $container->getExtensionConfig('solido'));
        if (! $config['serializer']['enabled']) {
            return;
        }

        if (isset($config['serializer']['id'])) {
            $container->setAlias('solido.serializer', new Alias($config['serializer']['id']));

            return;
        }

        if ($container->hasDefinition('kcs_serializer.serializer')) {
            $def = $container->register('solido.serializer', KcsSerializerAdapter::class)
                ->addArgument(new Reference('kcs_serializer.serializer'));
        } elseif ($container->hasDefinition('jms_serializer.serializer')) {
            $def = $container->register('solido.serializer', JmsSerializerAdapter::class)
                ->addArgument(new Reference('jms_serializer.serializer'));
        } elseif ($container->hasDefinition('serializer')) {
            $def = $container->register('solido.serializer', SymfonySerializerAdapter::class)
                ->addArgument(new Reference('serializer'));
        } else {
            throw new InvalidConfigurationException('Cannot find a valid serializer. Install or enable one of kcs/serializer, jms/serializer-bundle, symfony/serializer or specify the serializer adapter service id under solido.serializer.id configuration key.');
        }

        $def->addArgument($config['serializer']['groups']);
    }
}
