<?php

declare(strict_types=1);

use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Common\AdapterFactory;
use Solido\Common\AdapterFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(AdapterFactoryInterface::class, AdapterFactory::class)
        ->set(AdapterFactory::class)
            ->args([
                service(ResponseFactoryInterface::class)->nullOnInvalid(),
            ]);
};
