<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler\EntityFieldTemplateDataConverterPass;
use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroEntitySerializedFieldsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        parent::boot();

        EntitySerializedFieldsHolder::initialize($this->container);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ExtendFieldValidationLoaderPass());
        $container->addCompilerPass(new EntityFieldTemplateDataConverterPass());
    }
}
