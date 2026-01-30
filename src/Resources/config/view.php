<?php

declare(strict_types=1);

use Solido\Symfony\EventListener\SunsetHandler;
use Solido\Symfony\EventListener\ViewHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ViewHandler::class)
            ->args([
                service('solido.serializer'),
                service('security.token_storage')->nullOnInvalid(),
                null, // charset
            ])
            ->tag('kernel.event_subscriber')

        ->set(SunsetHandler::class)
            ->tag('kernel.event_subscriber');
};
