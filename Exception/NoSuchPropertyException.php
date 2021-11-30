<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Exception;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException as BaseException;

/**
 * Thrown when a property cannot be found.
 */
class NoSuchPropertyException extends BaseException
{
}
