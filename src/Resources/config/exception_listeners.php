<?php

declare(strict_types=1);

use Solido\Symfony\EventListener\BadResponseExceptionSubscriber;
use Solido\Symfony\EventListener\InvalidJSONExceptionSubscriber;
use Solido\Symfony\EventListener\MappingErrorExceptionSubscriber;
use Solido\Symfony\EventListener\UnmergeablePatchExceptionSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(BadResponseExceptionSubscriber::class)
            ->args([service('solido.serializer')->ignoreOnInvalid()])
            ->tag('kernel.event_subscriber')

        ->set(MappingErrorExceptionSubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set(InvalidJSONExceptionSubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set(UnmergeablePatchExceptionSubscriber::class)
            ->tag('kernel.event_subscriber');
};
