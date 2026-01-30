<?php

declare(strict_types=1);

use Solido\PolicyChecker\Test\TestPolicyChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(TestPolicyChecker::class);
};
