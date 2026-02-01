<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

use Solido\Serialization\DTO\JmsSerializerProxySubscriber;
use Solido\Serialization\DTO\KcsSerializerProxySubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $exists = static function (string $class): bool {
        try {
            new ReflectionClass($class);

            return true;
        } catch (Throwable) {
            return false;
        }
    };

    if ($exists(KcsSerializerProxySubscriber::class)) {
        $container->services()
            ->set(KcsSerializerProxySubscriber::class)
            ->tag('kernel.event_subscriber');
    }

    if ($exists(JmsSerializerProxySubscriber::class)) {
        $container->services()
            ->set(JmsSerializerProxySubscriber::class)
            ->tag('jms_serializer.event_subscriber');
    }
};
