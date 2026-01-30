<?php

declare(strict_types=1);

use Solido\Serialization\DTO\JmsSerializerProxySubscriber;
use Solido\Serialization\DTO\KcsSerializerProxySubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(KcsSerializerProxySubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set(JmsSerializerProxySubscriber::class)
            ->tag('jms_serializer.event_subscriber');
};
