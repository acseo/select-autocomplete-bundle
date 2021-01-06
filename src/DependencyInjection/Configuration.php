<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        /** @var mixed $treeBuilder */
        $treeBuilder = new TreeBuilder('acseo_select_autocomplete');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('registries')
                    ->scalarPrototype()->end()
                    ->defaultValue(['doctrine', 'doctrine_mongodb'])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
