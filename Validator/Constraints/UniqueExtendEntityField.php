<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityField as BaseUniqueExtendEntityField;

class UniqueExtendEntityField extends BaseUniqueExtendEntityField
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_serialized_fields.validator.unique_extend_entity_field';
    }
}
