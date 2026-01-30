<?php

declare(strict_types=1);

use Solido\Common\AdapterFactoryInterface;
use Solido\PatchManager\PatchManager;
use Solido\PatchManager\PatchManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PatchManager::class)
            ->public()
            ->args([service('validator')->nullOnInvalid()])
            ->call('setCache', [service('cache.app')->ignoreOnInvalid()])
            ->call('setAdapterFactory', [service(AdapterFactoryInterface::class)])

        ->alias('solido.patch_manager', PatchManager::class)->public()
        ->alias(PatchManagerInterface::class, PatchManager::class)->public();
};
