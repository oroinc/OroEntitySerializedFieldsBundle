<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of `jsonb_array_elements_text(%s)` function for Doctrine ORM.
 */
class JsonbArrayElementsText extends BaseFunction
{
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat('jsonb_array_elements_text(%s)');
        $this->addArgMapping('StringPrimary');
    }
}
