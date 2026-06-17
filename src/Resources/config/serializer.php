<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (
        class_exists('Kcs\Serializer\EventDispatcher\PreSerializeEvent') &&
        class_exists('Solido\Serialization\DTO\KcsSerializerProxySubscriber')
    ) {
        $container->services()
            ->set('Solido\Serialization\DTO\KcsSerializerProxySubscriber')
            ->tag('kernel.event_subscriber');
    }

    if (
        interface_exists('JMS\Serializer\EventDispatcher\EventSubscriberInterface') &&
        class_exists('Solido\Serialization\DTO\JmsSerializerProxySubscriber')
    ) {
        $container->services()
            ->set('Solido\Serialization\DTO\JmsSerializerProxySubscriber')
            ->tag('jms_serializer.event_subscriber');
    }
};
