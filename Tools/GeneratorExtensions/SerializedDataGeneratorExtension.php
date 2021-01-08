<?php
declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\GeneratorExtensions;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Generates getters and setters for serialized fields.
 */
class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    public const SERIALIZED_DATA_FIELD = 'serialized_data';

    private Inflector $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
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
        if (empty($schema['serialized_property'])) {
            return;
        }

        foreach ($schema['serialized_property'] as $fieldName => $config) {
            $getMethodName = $this->generateGetMethodName($fieldName);
            $setMethodName = $this->generateSetMethodName($fieldName);
            if ($class->hasMethod($getMethodName)) {
                $class->removeMethod($getMethodName);
            }
            if ($class->hasMethod($setMethodName)) {
                $class->removeMethod($setMethodName);
            }

            $isPrivate = is_array($config) && isset($config['private']) && $config['private'];
            if (!$isPrivate) {
                $class->addMethod($getMethodName)
                    ->addBody(
                        'return isset($this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'])' .
                        ' ? $this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\']' .
                        ' : null;'
                    );
                $class->addMethod($setMethodName)
                    ->addBody(
                        '$this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'] = $value; return $this;'
                    )
                    ->addParameter('value');
            }
        }
    }

    protected function generateGetMethodName(string $fieldName): string
    {
        return 'get' . \ucfirst($this->inflector->camelize($fieldName));
    }

    protected function generateSetMethodName(string $fieldName): string
    {
        return 'set' . \ucfirst($this->inflector->camelize($fieldName));
    }
}
