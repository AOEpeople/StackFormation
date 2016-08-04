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
                ->variableNode('vars')
                    ->treatTrueLike(array())
                    ->treatFalseLike(array())
                    ->treatNullLike(array())
                    ->defaultValue(array())
                ->end()
                ->arrayNode('blueprints')
                    ->useAttributeAsKey('stackname')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('stackname')->end()
                            ->scalarNode('description')->end()
                            ->variableNode('parameters')
                                ->treatTrueLike(array())
                                ->treatFalseLike(array())
                                ->treatNullLike(array())
                                ->defaultValue(array())
                            ->end()
                            ->variableNode('tags')
                                ->treatTrueLike(array())
                                ->treatFalseLike(array())
                                ->treatNullLike(array())
                                ->defaultValue(array())
                            ->end()
                            ->variableNode('vars')
                                ->treatTrueLike(array())
                                ->treatFalseLike(array())
                                ->treatNullLike(array())
                                ->defaultValue(array())
                            ->end()
                            ->variableNode('before')->end()
                            ->variableNode('after')->end()
                            ->scalarNode('basepath')->isRequired()->end() // will be automatically set to the current blueprints.yml file's dir
                            ->variableNode('template')
                                ->treatTrueLike(array())
                                ->treatFalseLike(array())
                                ->treatNullLike(array())
                                ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function($value){ return array($value); })->end()
                            ->end()
                            ->variableNode('optionalTemplates')
                                ->treatTrueLike(array())
                                ->treatFalseLike(array())
                                ->treatNullLike(array())
                                ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function ($value) { return array($value); })->end()
                            ->end()
                            ->scalarNode('stackPolicy')->end()
                            ->variableNode('profile')->end()
                            ->variableNode('account')->end()
                            ->scalarNode('OnFailure')->end()
                            ->scalarNode('Capabilities')->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
