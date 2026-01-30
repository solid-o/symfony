<?php

declare(strict_types=1);

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Solido\DataTransformers\Transformer\Base64UriFileTransformer;
use Solido\DataTransformers\Transformer\Base64UriToPsr7FileTransformer;
use Solido\DataTransformers\Transformer\BooleanTransformer;
use Solido\DataTransformers\Transformer\DateTimeTransformer;
use Solido\DataTransformers\Transformer\DateTransformer;
use Solido\DataTransformers\Transformer\IntegerTransformer;
use Solido\DataTransformers\Transformer\Money\CurrencyTransformer;
use Solido\DataTransformers\Transformer\Money\MoneyTransformer;
use Solido\DataTransformers\Transformer\PageTokenTransformer;
use Solido\DataTransformers\Transformer\PhoneNumberTransformer;
use Solido\DataTransformers\Transformer\UrnToItemTransformer;
use Solido\Symfony\DTO\Extension\TransformerExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(Base64UriFileTransformer::class)
        ->alias('solido.data_transformer.base64_data_uri', Base64UriFileTransformer::class)

        ->set(Base64UriToPsr7FileTransformer::class)
            ->args([
                service(UploadedFileFactoryInterface::class)->ignoreOnInvalid(),
                service(StreamFactoryInterface::class)->ignoreOnInvalid(),
            ])
        ->alias('solido.data_transformer.base64_data_uri_to_psr7', Base64UriToPsr7FileTransformer::class)

        ->set(BooleanTransformer::class)
        ->alias('solido.data_transformer.boolean', BooleanTransformer::class)

        ->set(DateTimeTransformer::class)
            ->args([
                null,
                true,
            ])
        ->alias('solido.data_transformer.date_time', DateTimeTransformer::class)

        ->set(DateTransformer::class)
        ->alias('solido.data_transformer.date', DateTransformer::class)

        ->set(IntegerTransformer::class)
        ->alias('solido.data_transformer.integer', IntegerTransformer::class)

        ->set(PageTokenTransformer::class)
        ->alias('solido.data_transformer.page_token', PageTokenTransformer::class)

        ->set(PhoneNumberTransformer::class)
        ->alias('solido.data_transformer.phone_number', PhoneNumberTransformer::class)

        ->set(UrnToItemTransformer::class)
            ->args([service('solido.urn.urn_converter')])
        ->alias('solido.data_transformer.urn_to_item', UrnToItemTransformer::class)

        ->set(CurrencyTransformer::class)
        ->alias('solido.data_transformer.money_currency', CurrencyTransformer::class)

        ->set(MoneyTransformer::class)
        ->alias('solido.data_transformer.money', MoneyTransformer::class)

        ->set(TransformerExtension::class)
            ->args([
                service('service_container'),
            ])
            ->tag('solido.dto_extension', ['priority' => 40]);
};
