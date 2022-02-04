<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CoreBusConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('corebus');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('adapter')
                    ->values(['goat'])
                    ->defaultValue('goat')
                ->end()
                ->variableNode('adapter_options')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
