<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\GeneratorExtensions;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\SerializedFieldsTrait;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Generates getters and setters for serialized fields.
 */
class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    public const SERIALIZED_DATA_FIELD = 'serialized_data';

    private bool $isDebug;

    public function __construct(bool $isDebug = false)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supports(array $schema): bool
    {
        return true;
    }

    public function generate(array $schema, ClassGenerator $class): void
    {
        if (!$class->hasProperty(self::SERIALIZED_DATA_FIELD)) {
            return;
        }

        if ($class->hasMethod('getSerializedData')) {
            $class->getMethod('getSerializedData')->addComment('@internal');
            $class->getMethod('setSerializedData')->addComment('@internal');
        }

        $class->addTrait(SerializedFieldsTrait::class);

        if (!empty($schema['serialized_property']) && $this->isDebug) {
            foreach (array_keys($schema['serialized_property']) as $fieldName) {
                $class->addComment(sprintf('@property $%s', $fieldName));
            }
        }
    }
}
