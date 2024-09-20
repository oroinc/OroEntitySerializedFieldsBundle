<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of `jsonb_set(%s, %s, %s)` function for Doctrine ORM.
 */
class JsonbSet extends BaseFunction
{
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat('jsonb_set(%s, %s, %s)');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('InputParameter');
    }
}
