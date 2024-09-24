<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of json contains `(%s->%s @> %s)` operator for Doctrine ORM.
 */
class JsonbArrayContainJson extends BaseFunction
{
    #[\Override]
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat('(%s->%s @> (%s)::jsonb)');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
    }
}
