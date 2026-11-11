<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

/**
 * Implementation of `jsonb_enum_internal_ids(%s, %s)` function for Doctrine ORM.
 * Returns internal IDs of serialized multi-enum options separated by a space: ["code.a", "code.b"] -> "a b".
 */
class JsonbEnumInternalIds extends BaseFunction
{
    #[\Override]
    protected function initFunctionMapping(): void
    {
        $this->setFunctionFormat(
            "(SELECT string_agg(substr(enum_option.id, strpos(enum_option.id, '.') + 1), ' ' ORDER BY enum_option.pos)"
            . " FROM jsonb_array_elements_text(CASE WHEN jsonb_typeof(%1\$s->%2\$s) = 'array' THEN %1\$s->%2\$s END)"
            . ' WITH ORDINALITY AS enum_option(id, pos))'
        );
        $this->addArgMapping('StringPrimary');
        $this->addArgMapping('StringPrimary');
    }
}
