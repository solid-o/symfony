<?php

declare(strict_types=1);

use Solido\Symfony\EventListener\RequestListener;
use Solido\Symfony\Request\FormatGuesser;
use Solido\Symfony\Request\FormatGuesserInterface;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(FormatGuesserInterface::class, FormatGuesser::class)
        ->set(FormatGuesser::class)
            ->args([
                '%solido.format.priorities%',
                '%solido.format.default_type%',
            ])

        ->set(RequestListener::class)
            ->args([
                service(FormatGuesserInterface::class),
                service(VersionGuesserInterface::class)->nullOnInvalid(),
                '%kernel.debug%',
            ])
            ->call('setRequestMatcher', [service('solido.request_matcher')->nullOnInvalid()])
            ->tag('kernel.event_subscriber');
};
