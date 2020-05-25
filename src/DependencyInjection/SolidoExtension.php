<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection;

use Solido\BodyConverter\Decoder\DecoderProviderInterface;
use Solido\Cors\RequestHandler;
use Solido\Cors\RequestHandlerInterface;
use Solido\DataTransformers\TransformerInterface;
use Solido\PatchManager\PatchManagerInterface;
use Solido\QueryLanguage\Processor\FieldInterface;
use Solido\Symfony\Cors\HandlerFactory;
use Solido\Symfony\EventListener\ViewHandler;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use function assert;
use function class_exists;
use function interface_exists;

class SolidoExtension extends Extension
{
    /**
     * @param array<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        assert($configuration !== null, 'Configuration is not null');

        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if ($config['body_converter']['enabled'] && interface_exists(DecoderProviderInterface::class)) {
            $loader->load('body_converter.xml');
        }

        $corsConfig = $container->resolveEnvPlaceholders($config['cors']);
        if ($corsConfig['enabled'] && interface_exists(RequestHandlerInterface::class)) {
            $loader->load('cors.xml');

            $i = 0;
            foreach ($corsConfig['paths'] as $pathRegex => &$pathConfig) {
                $container->register('.solido.symfony-bundle.cors.handler.' . ++$i, RequestHandler::class)
                    ->setArguments([
                        $pathConfig['allow_credentials'] ?? $corsConfig['allow_credentials'],
                        $pathConfig['allow_origin'] ?? $corsConfig['allow_origin'],
                        $pathConfig['allow_headers'] ?? $corsConfig['allow_headers'],
                        $pathConfig['expose_headers'] ?? $corsConfig['expose_headers'],
                        $pathConfig['max_age'] ?? $corsConfig['max_age'],
                    ]);

                $pathConfig = [
                    'paths' => $pathConfig['paths'],
                    'host' => $pathConfig['host'],
                    'factory' => new ServiceClosureArgument(new Reference('.solido.symfony-bundle.cors.handler.' . $i)),
                ];
            }

            unset($pathConfig);
            $container->getDefinition(HandlerFactory::class)->replaceArgument(0, $corsConfig);
        }

        if ($config['request']['enabled']) {
            $container->setParameter('solido.format.priorities', $config['request']['priorities']);
            $container->setParameter('solido.format.default_type', $config['request']['default_mime_type']);
            $loader->load('request.xml');

            if (interface_exists(VersionGuesserInterface::class)) {
                $container->setParameter('solido.versioning.custom_header_name', $config['request']['versioning']['custom_header_name']);
                $loader->load('versioning.xml');

                if ($container->has('solido.versioning.version_guesser_' . $config['request']['versioning']['guesser'])) {
                    $container->setAlias('solido.versioning.version_guesser', new Alias('solido.versioning.version_guesser_' . $config['request']['versioning']['guesser']));
                } else {
                    $container->setAlias('solido.versioning.version_guesser', new Alias($config['request']['versioning']['guesser']));
                }

                $container->setAlias(VersionGuesserInterface::class, new Alias('solido.versioning.version_guesser'));
            }
        }

        if ($config['serializer']['enabled']) {
            $loader->load('view.xml');

            $container->findDefinition(ViewHandler::class)->replaceArgument(2, $config['serializer']['charset']);
        }

        $this->loadIfExists($loader, 'data_transformers.xml', TransformerInterface::class);
        $this->loadIfExists($loader, 'patch_manager.xml', PatchManagerInterface::class);
        $this->loadIfExists($loader, 'query_language.xml', FieldInterface::class);
    }

    private function loadIfExists(FileLoader $loader, string $filename, string $className): void
    {
        if (! interface_exists($className) && ! class_exists($className)) {
            return;
        }

        $loader->load($filename);
    }
}
