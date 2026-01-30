<?php

declare(strict_types=1);

use Solido\Symfony\ErrorRenderer\SerializerErrorRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(SerializerErrorRenderer::class)
        ->decorate('error_renderer', invalidBehavior: ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
        ->args([
            service(SerializerErrorRenderer::class . '.inner'),
            service('request_stack'),
            '%kernel.debug%',
        ]);
};
