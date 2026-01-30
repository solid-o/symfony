<?php

declare(strict_types=1);

use Solido\Symfony\Cors\HandlerFactory;
use Solido\Symfony\EventListener\CorsListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias('solido.cors.handler_factory', HandlerFactory::class)
        ->set(HandlerFactory::class)
            ->args([null])

        ->set(CorsListener::class)
            ->args([
                service(HandlerFactory::class),
            ])
            ->tag('kernel.event_subscriber');
};
