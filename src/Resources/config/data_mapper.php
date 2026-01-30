<?php

declare(strict_types=1);

use Solido\BodyConverter\BodyConverterInterface;
use Solido\Common\AdapterFactoryInterface;
use Solido\DataMapper\DataMapperFactory;
use Solido\DataMapper\Form\RequestHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(RequestHandler::class)
            ->args([
                service('form.server_params')->nullOnInvalid(),
                service(AdapterFactoryInterface::class),
                service(BodyConverterInterface::class)->ignoreOnInvalid(),
            ])

        ->set(DataMapperFactory::class)
            ->call('setFormRequestHandler', [service(RequestHandler::class)->ignoreOnInvalid()])
            ->call('setFormRegistry', [service('form.registry')->ignoreOnInvalid()])
            ->call('setTranslator', [service('translator')->ignoreOnInvalid()])
            ->call('setAdapterFactory', [service(AdapterFactoryInterface::class)->ignoreOnInvalid()])
            ->call('setBodyConverter', [service(BodyConverterInterface::class)->ignoreOnInvalid()])
            ->call('setPropertyAccessor', [service('property_accessor')->ignoreOnInvalid()])
            ->call('setValidator', [service('validator')->ignoreOnInvalid()]);
};
