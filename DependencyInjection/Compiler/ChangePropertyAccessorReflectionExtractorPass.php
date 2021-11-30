<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides 'property_accessor' service with extended read/write reflection extractor.
 */
class ChangePropertyAccessorReflectionExtractorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('property_accessor')
            ->setArgument(3, new Reference('oro_serialized_fields.property_accessor.read_reflection_extractor'))
            ->setArgument(4, new Reference('oro_serialized_fields.property_accessor.write_reflection_extractor'));
    }
}
