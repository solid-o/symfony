<?php

declare(strict_types=1);

use Solido\QueryLanguage\Form\FieldType;
use Solido\QueryLanguage\Form\PageTokenType;
use Solido\QueryLanguage\Form\QueryType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(FieldType::class)
            ->args([
                service('translator')->nullOnInvalid(),
            ])
            ->tag('form.type')

        ->set(PageTokenType::class)
            ->tag('form.type')

        ->set(QueryType::class)
            ->tag('form.type');
};
