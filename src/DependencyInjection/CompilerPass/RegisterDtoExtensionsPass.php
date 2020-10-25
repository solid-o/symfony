<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterDtoExtensionsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('solido.dto-management.proxy_factory.configuration')) {
            return;
        }

        $definition = $container->findDefinition('solido.dto-management.proxy_factory.configuration');
        foreach ($this->findAndSortTaggedServices('solido.dto_extension', $container) as $reference) {
            $definition->addMethodCall('addExtension', [$reference]);
        }
    }
}
