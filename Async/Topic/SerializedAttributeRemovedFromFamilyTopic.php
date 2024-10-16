<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Async\Topic;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributeRemovedFromFamilyTopic;

/**
 * Topic for removing attribute from family.
 */
class SerializedAttributeRemovedFromFamilyTopic extends AttributeRemovedFromFamilyTopic
{
    public const NAME = 'oro_serialized_fields.serialized_attribute_was_removed_from_family';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Removes an serialized attribute from family';
    }
}
