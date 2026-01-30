<?php

declare(strict_types=1);

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Solido\Symfony\Config\ControllerCacheWarmer;
use Solido\Symfony\DTO\Routing\AnnotationRoutingLoader;
use Solido\Symfony\EventListener\ControllerListener;
use Solido\Symfony\EventListener\ControllerVersionValidatorListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('solido.dto-management.routing.loader', AnnotationRoutingLoader::class)
            ->args([
                service(ServiceLocatorRegistryInterface::class),
                '%kernel.environment%',
            ])
            ->tag('routing.loader')

        ->set('solido.dto-management.controller_listener', ControllerListener::class)
            ->args([
                service('config_cache_factory'),
                '%kernel.cache_dir%',
            ])
            ->tag('kernel.event_subscriber')

        ->set('solido.dto-management.controller_listener_warmer', ControllerCacheWarmer::class)
            ->args([
                service('solido.dto-management.controller_listener'),
                service('router.default'),
                service('controller_resolver'),
            ])

        ->set('solido.dto-management.controller_version_validator_listener', ControllerVersionValidatorListener::class)
            ->args([service(ServiceLocatorRegistryInterface::class)])
            ->tag('kernel.event_subscriber');
};
