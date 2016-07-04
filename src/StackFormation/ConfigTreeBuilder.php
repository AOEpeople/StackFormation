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
                ->arrayNode('blueprints')
                ->useAttributeAsKey('stackname')
                ->isRequired()
                ->cannotBeEmpty()
                ->prototype('array')
                    ->children()
                        ->variableNode('parameters')->end()
                        ->variableNode('tags')->end()
                        ->variableNode('vars')->end()
                        ->variableNode('before')->end()
                        ->variableNode('after_success')->end()
                        ->variableNode('after_failure')->end()
                        ->variableNode('after_always')->end()
                        ->variableNode('template')->end()
                        ->scalarNode('stackname')->end()
                        ->variableNode('profile')->end()
                        ->variableNode('account')->end()
                        ->scalarNode('description')->end()
                        ->scalarNode('OnFailure')->end()
                        ->scalarNode('Capabilities')->end()
                        ->scalarNode('basepath')->end() // will be automatically set to the current blueprints.yml file's dir
                        ->scalarNode('stackPolicy')->end()
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
