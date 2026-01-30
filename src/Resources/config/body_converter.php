<?php

declare(strict_types=1);

use Solido\BodyConverter\BodyConverter;
use Solido\BodyConverter\BodyConverterInterface;
use Solido\BodyConverter\Decoder\DecoderProvider;
use Solido\BodyConverter\Decoder\DecoderProviderInterface;
use Solido\BodyConverter\Decoder\JsonDecoder;
use Solido\Common\AdapterFactoryInterface;
use Solido\Symfony\EventListener\BodyConverter as BodyConverterListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(DecoderProviderInterface::class, DecoderProvider::class)
        ->set(DecoderProvider::class)
            ->args([
                [],
            ])

        ->set(JsonDecoder::class)
            ->tag('solido.body_converter.decoder')

        ->alias(BodyConverterInterface::class, BodyConverter::class)
        ->alias('solido.body_converter', BodyConverterInterface::class)
        ->set(BodyConverter::class)
            ->args([
                service(DecoderProviderInterface::class),
                service(AdapterFactoryInterface::class),
            ])

        ->set(BodyConverterListener::class)
            ->args([
                service(BodyConverterInterface::class),
            ])
            ->call('setRequestMatcher', [service('solido.request_matcher')->nullOnInvalid()])
            ->tag('kernel.event_subscriber');
};
