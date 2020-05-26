<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\Symfony\VarDumper\ProxyCaster;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\VarDumper\Caster\StubCaster;

class RegisterDtoProxyCasterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('var_dumper.cloner')) {
            return;
        }

        $container->findDefinition('var_dumper.cloner')
            ->addMethodCall('addCasters', [
                [
                    ProxyInterface::class => ProxyCaster::class . '::castDtoProxy',
                    ResolverInterface::class => StubCaster::class . '::cutInternals',
                ],
            ]);
    }
}
