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
                // Command bus adapter configuration.
                ->arrayNode('command_bus')
                    ->children()
                        ->enumNode('adapter')
                            ->values(['message-broker', 'memory', 'auto'])
                            ->defaultValue('auto')
                        ->end()
                    ->end()
                ->end()

                // Transaction adapter configuration.
                ->arrayNode('transaction')
                    ->children()
                        ->enumNode('adapter')
                            ->values(['goat-query', 'none', 'auto'])
                            ->defaultValue('auto')
                        ->end()
                    ->end()
                ->end()

                // Event store decorator configuration.
                ->arrayNode('event_store')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
