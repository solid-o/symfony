<?php

declare(strict_types=1);

use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Solido\DtoManagement\InterfaceResolver\Resolver;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use Solido\DtoManagement\Proxy\Factory\Configuration;
use Solido\Symfony\DTO\ArgumentResolver;
use Solido\Symfony\DTO\Proxy\CacheWriterGeneratorStrategy;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('solido.dto-management.proxy_factory.configuration', Configuration::class)
            ->call('setProxiesTargetDir', ['%solido.dto-management.proxy_cache_dir%'])
            ->call('setGeneratorStrategy', [
                inline_service(CacheWriterGeneratorStrategy::class)
                    ->args([
                        service('solido.dto-management.proxy_factory.configuration'),
                        '%kernel.debug%',
                    ]),
            ])

        ->set('solido.dto-management.proxy_factory', AccessInterceptorFactory::class)
            ->args([
                service('solido.dto-management.proxy_factory.configuration'),
            ])

        ->alias(ServiceLocatorRegistryInterface::class, ServiceLocatorRegistry::class)
        ->set(ServiceLocatorRegistry::class)
            ->args([[]])

        ->alias('solido.dto-management.resolver', ResolverInterface::class)->public()
        ->alias(ResolverInterface::class, Resolver::class)->public()
        ->alias(Resolver::class, \Solido\Symfony\DTO\Resolver::class)
        ->set(\Solido\Symfony\DTO\Resolver::class)
            ->args([
                service(ServiceLocatorRegistry::class),
                service('request_stack')->nullOnInvalid(),
            ])

        ->set(ArgumentResolver::class)
            ->args([
                service(ResolverInterface::class),
            ])
            ->tag('controller.argument_value_resolver', ['priority' => 35]);
};
