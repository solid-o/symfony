<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Solido\BodyConverter\Decoder\DecoderInterface;
use Solido\BodyConverter\Decoder\DecoderProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function assert;
use function is_subclass_of;

class RegisterBodyConverterDecoders implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(DecoderProvider::class)) {
            return;
        }

        $decoders = [];

        foreach ($container->findTaggedServiceIds('solido.body_converter.decoder') as $serviceId => $unused) {
            $definition = $container->getDefinition($serviceId);
            $definition->setLazy(true);

            $class = $definition->getClass();
            assert($class !== null && is_subclass_of($class, DecoderInterface::class));

            $format = $class::getFormat();
            $decoders[$format] = new Reference($serviceId);
        }

        $provider = $container->findDefinition(DecoderProvider::class);
        $provider->replaceArgument(0, $decoders);
    }
}
