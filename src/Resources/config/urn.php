<?php

declare(strict_types=1);

use Solido\Common\Urn\UrnConverter;
use Solido\Common\Urn\UrnConverterInterface;
use Solido\Symfony\Urn\UrnClassCacheWarmer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(UrnConverterInterface::class, 'solido.urn.urn_converter')
        ->alias('solido.urn.urn_converter', UrnConverter::class)
        ->set(UrnConverter::class)
        ->args([
            [
                service('doctrine')->ignoreOnInvalid(),
                service('doctrine_mongodb')->ignoreOnInvalid(),
                service('doctrine_phpcr')->ignoreOnInvalid(),
            ],
            service('config_cache_factory'),
            '%kernel.cache_dir%',
        ])

        ->set('solido.urn.urn_class_cache_warmer', UrnClassCacheWarmer::class)
            ->private()
            ->args([
                service('solido.urn.urn_converter'),
                null,
            ])
            ->tag('kernel.cache_warmer');
};
