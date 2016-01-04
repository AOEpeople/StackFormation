<?php

namespace StackFormation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigTreeBuilder implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('root');
        $rootNode
            ->children()
                ->arrayNode('stacks')
                ->useAttributeAsKey('stackname')
                ->isRequired()
                ->cannotBeEmpty()
                ->prototype('array')
                    ->children()
                        ->variableNode('parameters')->end()
                        ->variableNode('tags')->end()
                        ->variableNode('vars')->end()
                        ->scalarNode('stackname')->end()
                        ->scalarNode('template')->end()
                        ->scalarNode('profile')->end()
                        ->scalarNode('OnFailure')->end()
                        ->scalarNode('Capabilities')->end()
                    ->end()
                ->end()
            ->end();
        $rootNode
            ->children()
                ->variableNode('vars')->end()
            ->end();
        return $treeBuilder;
    }
}
