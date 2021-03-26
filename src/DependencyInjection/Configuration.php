<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection;

use InvalidArgumentException;
use Solido\BodyConverter\BodyConverterInterface;
use Solido\Cors\Configuration as CorsConfiguration;
use Solido\Cors\RequestHandlerInterface;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function get_debug_type;
use function interface_exists;
use function is_array;
use function Safe\sprintf;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('solido');
        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('test')
                    ->treatNullLike(true)
                    ->defaultFalse()
                    ->info('Whether to enable test features')
                ->end()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('register_data_mapper')
                            ->info('Register form data mapper for all forms in the project')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('auto_submit')
                            ->info('Register the auto-submit extension for all forms')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('request')
                    ->info('Enables the request processing features (Accept header parsing, versioning guessing)')
                    ->canBeDisabled()
                    ->fixXmlConfig('priority', 'priorities')
                    ->children()
                        ->arrayNode('versioning')
                            ->addDefaultsIfNotSet()
                            ->{interface_exists(VersionGuesserInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                            ->children()
                                ->scalarNode('guesser')
                                    ->info('Can be "accept", "custom_header" or a service id')
                                    ->defaultValue('accept')
                                ->end()
                                ->scalarNode('custom_header_name')
                                    ->info('Required for custom header version guesser, specify the header to check')
                                    ->defaultValue('X-Version')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('default_mime_type')
                            ->info('Set the default mime type if no Accept header is present on the request')
                            ->defaultValue('application/json')
                        ->end()
                        ->arrayNode('priorities')
                            ->info('Sets the acceptable MIME types for Accept header')
                            ->cannotBeEmpty()
                            ->requiresAtLeastOneElement()
                            ->defaultValue([
                                'application/json',
                                'application/x-json',
                                'text/xml',
                                'application/xml',
                                'application/x-xml',
                            ])
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('body_converter')
                    ->info('Whether to enable or not the body converter component')
                    ->{interface_exists(BodyConverterInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
                ->arrayNode('serializer')
                    ->info('Enables the automatic serialization of views and data returned from controllers')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('id')
                            ->info('The serializer id (must implement Solido\Serialization\SerializerInterface)')
                        ->end()
                        ->scalarNode('charset')->defaultValue('UTF-8')->end()
                        ->variableNode('groups')
                            ->defaultValue(null)
                            ->validate()
                                ->ifTrue(static fn ($data): bool => $data !== null && ! is_array($data))
                                ->then(static function ($data): void {
                                    throw new InvalidArgumentException(sprintf('expected array, %s given', get_debug_type($data)));
                                })
                            ->end()
                        ->end()
                        ->booleanNode('catch_exceptions')
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('data_transformers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('date_time')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('timezone')
                                    ->info('Default output timezone for date time data transformer')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dto')
                    ->info('Configure DTO management component and automatically register dto service locator registry')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('namespace')
                    ->children()
                        ->arrayNode('routing')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('loader')
                                    ->info('Whether to register the dto routing loader')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('namespaces')
                            ->info('List of enhanced DTO namespaces')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('exclude')
                            ->info('List of interfaces excluded by dto resolvers')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('action_listener')
                            ->canBeEnabled()
                            ->children()
                                ->variableNode('prefix')->end()
                            ->end()
                        ->end()
                        ->arrayNode('policy_checker')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('service')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('urn')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_domain')
                            ->info('The URN default domain')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // @phpstan-ignore-next-line
        $node = $rootNode->children()->arrayNode('cors');
        if (interface_exists(RequestHandlerInterface::class)) {
            $node->canBeDisabled();
            CorsConfiguration::buildTree($node->children());
        } else {
            $node->canBeEnabled();
        }

        return $treeBuilder;
    }
}
