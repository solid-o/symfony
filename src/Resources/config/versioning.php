<?php

declare(strict_types=1);

use Solido\Common\AdapterFactoryInterface;
use Solido\Versioning\AcceptHeaderVersionGuesser;
use Solido\Versioning\CustomHeaderVersionGuesser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias('solido.versioning.version_guesser_accept', AcceptHeaderVersionGuesser::class)
        ->set(AcceptHeaderVersionGuesser::class)
            ->args([
                '%solido.format.priorities%',
                service(AdapterFactoryInterface::class),
            ])

        ->alias('solido.versioning.version_guesser_custom_header', CustomHeaderVersionGuesser::class)
        ->set(CustomHeaderVersionGuesser::class)
            ->args([
                '%solido.versioning.custom_header_name%',
                service(AdapterFactoryInterface::class),
            ]);
};
