<?php

declare(strict_types=1);

use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\Symfony\Command\SmithyDumpCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('solido.smithy.extractor', 'Solido\\Smithy\\Extractor\\DtoMetadataExtractor')
            ->args([
                service(ResolverInterface::class),
            ])

        ->set('solido.smithy.renderer', 'Solido\\Smithy\\Renderer\\SmithyIdlRenderer')

        ->set('solido.smithy.generator', 'Solido\\Smithy\\SmithyGenerator')
            ->args([
                service('solido.smithy.extractor'),
                service('solido.smithy.renderer'),
            ])

        ->set(SmithyDumpCommand::class)
            ->public()
            ->args([
                service('solido.smithy.generator'),
                '%solido.smithy.config%',
            ])
            ->tag('console.command');
};
