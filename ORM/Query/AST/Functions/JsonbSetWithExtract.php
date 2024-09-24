<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of aggregated function 'jsonb_set(%s, %s, (%s->>%s - %s)' to update jsonb array options.
 */
class JsonbSetWithExtract extends BaseFunction
{
    #[\Override]
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat('jsonb_set(%s::jsonb, %s, (%s->%s)::jsonb - %s)');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('InputParameter');
    }
}
