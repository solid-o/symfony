<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Solido\Symfony\EventListener\BadResponseExceptionSubscriber;
use Solido\Symfony\EventListener\MappingErrorExceptionSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ControllerCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        try {
            $def = $container->findDefinition('solido.dto-management.controller_listener_warmer');
        } catch (ServiceNotFoundException $e) {
            return;
        }

        if (! $container->hasDefinition(MappingErrorExceptionSubscriber::class)) {
            return;
        }

        $def->addMethodCall('addAdditionalController', [
            [BadResponseExceptionSubscriber::class, 'errorAction'],
        ]);
    }
}
