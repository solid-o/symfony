<?php

declare(strict_types=1);

use Solido\Symfony\DTO\Extension\LockExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(LockExtension::class)
            ->tag('solido.dto_extension', ['priority' => 25]);
};
