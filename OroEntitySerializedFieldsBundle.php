<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The EntitySerializedFieldsBundle bundle class.
 */
class OroEntitySerializedFieldsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExtendFieldValidationLoaderPass());
    }
}
