<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ExclusionProviderCompilerPass implements CompilerPassInterface
{
    const SERVICE_KEY    = 'oro_query_designer.exclusion_provider';
    const ASSIGNMENT_TAG = 'oro_serialized_fields.exclusion_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }
        $definition     = $container->getDefinition(self::SERVICE_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::ASSIGNMENT_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall(
                'addProvider',
                array(new Reference($id))
            );
        }
    }
}
