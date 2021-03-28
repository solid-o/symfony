<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection;

use Solido\BodyConverter\Decoder\DecoderProviderInterface;
use Solido\Common\Urn\UrnConverter;
use Solido\Cors\RequestHandler;
use Solido\Cors\RequestHandlerInterface;
use Solido\DataTransformers\Transformer\DateTimeTransformer;
use Solido\DataTransformers\TransformerInterface;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\PatchManager\PatchManagerInterface;
use Solido\PolicyChecker\PolicyChecker;
use Solido\PolicyChecker\PolicyCheckerInterface;
use Solido\PolicyChecker\Test\TestPolicyChecker;
use Solido\PolicyChecker\Voter\VoterInterface;
use Solido\QueryLanguage\Processor\FieldInterface;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\Cors\HandlerFactory;
use Solido\Symfony\EventListener\ViewHandler;
use Solido\Symfony\Security\ActionListener;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
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
        $loader->load('compat_sensio_framework_extra.xml');

        if ($config['body_converter']['enabled'] && interface_exists(DecoderProviderInterface::class)) {
            $loader->load('body_converter.xml');
        }

        if ($config['urn']['enabled']) {
            $loader->load('urn.xml');
            $container->setParameter('solido.urn.urn_default_domain', $config['urn']['default_domain'] ?? '');

            if ($container->hasParameter('kernel.build_dir')) {
                $container->getDefinition(UrnConverter::class)
                    ->replaceArgument(2, new Parameter('kernel.build_dir'));

                $container->getDefinition('solido.urn.urn_class_cache_warmer')
                    ->replaceArgument(1, new Parameter('kernel.build_dir'));
            }
        }

        if ($config['test']) {
            $loader->load('test.xml');

            if ($config['security']['policy_checker']['enabled'] && ! isset($config['security']['policy_checker']['service'])) {
                $config['security']['policy_checker']['service'] = TestPolicyChecker::class;
            }
        }

        if ($config['security']) {
            if ($config['security']['action_listener']['enabled']) {
                $container->register(ActionListener::class, ActionListener::class)
                    ->addTag('kernel.event_subscriber')
                    ->addArgument(new Reference('security.token_storage'))
                    ->addArgument(new Reference('security.authorization_checker'))
                    ->addArgument($config['security']['action_listener']['prefix'] ?? null);
            }

            if ($config['security']['policy_checker']['enabled']) {
                $loader->load('security_policy_checker.xml');
                if ($container->getParameter('kernel.debug')) {
                    $loader->load('security_policy_checker_debug.xml');
                }

                $container->setAlias(PolicyCheckerInterface::class, $config['security']['policy_checker']['service'] ?? PolicyChecker::class);
                $container->registerForAutoconfiguration(VoterInterface::class)->addTag('solido.security.policy_checker.voter');
            }
        }

        if ($config['form']['register_data_mapper']) {
            $loader->load('form_data_mapper.xml');
        }

        if ($config['form']['auto_submit']) {
            if (! $config['body_converter']['enabled']) {
                throw new InvalidConfigurationException('Form auto submit needs body converter to be enabled');
            }

            $loader->load('form_auto_submit.xml');
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

            $corsConfig['factory'] = new ServiceClosureArgument(new Reference('.solido.symfony-bundle.cors.handler.main'));
            $container->register('.solido.symfony-bundle.cors.handler.main', RequestHandler::class)
                ->setArguments([
                    $corsConfig['allow_credentials'],
                    $corsConfig['allow_origin'],
                    $corsConfig['allow_headers'],
                    $corsConfig['expose_headers'],
                    $corsConfig['max_age'],
                ]);

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
            if (! interface_exists(SerializerInterface::class)) {
                throw new InvalidConfigurationException('Solido serialization component is not installed. Run composer require solido/serialization to install it.');
            }

            $loader->load('view.xml');
            $loader->load('serializer.xml');
            if ($config['serializer']['catch_exceptions']) {
                $loader->load('exception_listeners.xml');
                $loader->load('serializer_error_renderer.xml');
            }

            $container->findDefinition(ViewHandler::class)->replaceArgument(2, $config['serializer']['charset']);
            $container->setAlias(SerializerInterface::class, new Alias('solido.serializer', true));
        }

        if (interface_exists(ResolverInterface::class)) {
            $loader->load('dto.xml');
            $container->registerForAutoconfiguration(ExtensionInterface::class)->addTag('solido.dto_extension');

            if ($config['dto']['routing']['loader']) {
                $loader->load('dto_routing_loader.xml');
            }

            $definition = $container->findDefinition(ServiceLocatorRegistry::class);
            foreach ($config['dto']['namespaces'] as $namespace) {
                $definition->addTag('solido.dto_service_locator_registry.namespace', ['value' => $namespace]);
            }

            foreach ($config['dto']['exclude'] as $exclude) {
                $definition->addTag('solido.dto_service_locator_registry.exclude', ['value' => $exclude]);
            }
        }

        $this->loadIfExists($loader, 'data_transformers.xml', TransformerInterface::class);
        $dateTimeTransformerDefinition = $container->getDefinition(DateTimeTransformer::class);
        $dateTimeTransformerDefinition->replaceArgument(0, $config['data_transformers']['date_time']['timezone']);

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
