<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\ExclusionProviderCompilerPass;

class OroEntitySerializedFieldsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExclusionProviderCompilerPass());
    }
}
