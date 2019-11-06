<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntitySerializedFieldsBundle\Validator\ExtendFieldValidationLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Overrides the class for "oro_entity_extend.validation_loader" service
 * to turn off guessers for serialized fields.
 */
class ExtendFieldValidationLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_entity_extend.validation_loader')
            ->setClass(ExtendFieldValidationLoader::class);
    }
}
