<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterSecurityExtensionLanguageProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('solido.dto.security_expression_language')) {
            return;
        }

        $definition = $container->findDefinition('solido.dto.security_expression_language');

        $references = [];
        foreach ($container->findTaggedServiceIds('solido.security.expression_language_provider') as $serviceId => $unused) {
            $references[] = new Reference($serviceId);
        }

        $definition->replaceArgument(1, $references);
    }
}
