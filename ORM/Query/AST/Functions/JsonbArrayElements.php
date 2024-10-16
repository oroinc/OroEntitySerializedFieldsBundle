<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of `jsonb_array_elements(%s)` function for Doctrine ORM.
 */
class JsonbArrayElements extends BaseFunction
{
    #[\Override]
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat('jsonb_array_elements(%s)');
        $this->addArgMapping('StringPrimary');
    }
}
