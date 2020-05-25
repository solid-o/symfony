<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection;

use Solido\BodyConverter\BodyConverterInterface;
use Solido\Cors\Configuration as CorsConfiguration;
use Solido\Cors\RequestHandlerInterface;
use Solido\Versioning\VersionGuesserInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function interface_exists;

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
                ->arrayNode('request')
                    ->canBeDisabled()
                    ->children()
                        ->arrayNode('versioning')
                            ->addDefaultsIfNotSet()
                            ->{interface_exists(VersionGuesserInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                            ->children()
                                ->scalarNode('guesser')->defaultValue('accept')->end()
                                ->scalarNode('custom_header_name')->defaultValue('X-Version')->end()
                            ->end()
                        ->end()
                        ->scalarNode('default_mime_type')->defaultValue('application/json')->end()
                        ->arrayNode('priorities')
                            ->cannotBeEmpty()
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
                    ->{interface_exists(BodyConverterInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
                ->arrayNode('serializer')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('id')->end()
                        ->scalarNode('charset')->defaultValue('UTF-8')->end()
                        ->arrayNode('groups')
                            ->defaultValue(['Default'])
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('catch_exceptions')
                    ->defaultValue(true)
                ->end()
            ->end();

        if (interface_exists(RequestHandlerInterface::class)) {
            // @phpstan-ignore-next-line
            $node = $rootNode->children()->arrayNode('cors');
            $node->canBeDisabled();

            CorsConfiguration::buildTree($node->children());
        }

        return $treeBuilder;
    }
}
