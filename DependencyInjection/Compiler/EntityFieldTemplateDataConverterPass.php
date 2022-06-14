<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntitySerializedFieldsBundle\ImportExport\DataConverter\EntityFieldTemplateDataConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Changes the class name of the oro_entity_config.importexport.template_data_converter.entity_field service
 * to {@see \Oro\Bundle\EntitySerializedFieldsBundle\ImportExport\DataConverter\EntityFieldTemplateDataConverter}.
 */
class EntityFieldTemplateDataConverterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_entity_config.importexport.template_data_converter.entity_field')
            ->setClass(EntityFieldTemplateDataConverter::class);
    }
}
