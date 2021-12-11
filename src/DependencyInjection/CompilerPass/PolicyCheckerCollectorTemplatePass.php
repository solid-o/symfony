<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Solido\PolicyChecker\DataCollector\PolicyCheckerDataCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PolicyCheckerCollectorTemplatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(PolicyCheckerDataCollector::class)) {
            return;
        }

        $definition = $container->findDefinition(PolicyCheckerDataCollector::class);
        [$decorated] = $definition->getDecoratedService() ?? [null];

        if ($decorated === null || ! $container->has($decorated)) {
            return;
        }

        $originalService = $container->findDefinition($decorated);
        $tag = $originalService->getTag('data_collector');

        if (! isset($tag[0]['template'])) {
            return;
        }

        $definition->addMethodCall('setBaseTemplate', [$tag[0]['template']]);
    }
}
