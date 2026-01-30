<?php

declare(strict_types=1);

use Solido\Symfony\DTO\Extension\SecurityExtension;
use Solido\Symfony\DTO\Security\ExpressionLanguage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('solido.dto.security_expression_language', ExpressionLanguage::class)
            ->args([
                null,
                [],
            ])

        ->set(SecurityExtension::class)
            ->args([service('solido.dto.security_expression_language')])
            ->tag('solido.dto_extension', ['priority' => 30]);
};
