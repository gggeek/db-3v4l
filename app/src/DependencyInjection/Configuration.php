<?php

namespace Db3v4l\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('db3v4l');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('database_instances')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            /// @todo check which ones of these are mandatory; which ones can have requirements added
                            ->scalarNode('charset')->end()
                            ->scalarNode('driver')->end()
                            ->scalarNode('host')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('path')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('servicename')->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('vendor')->end()
                            ->scalarNode('version')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
