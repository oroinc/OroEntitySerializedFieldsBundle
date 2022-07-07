<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides EntitySerializedFields Bundle configuration.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('oro_entity_serialized_fields');

        $builder->getRootNode()
            ->children()
                ->arrayNode('dbal_types')
                    ->prototype('scalar')
                    ->info('The map of serialized field types to Doctrine DBAL types')
                ->end()
            ->end();

        return $builder;
    }
}
