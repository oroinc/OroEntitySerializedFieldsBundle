<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;

class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    const SERIALIZED_DATA_FIELD = 'serialized_data';

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
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
                $class
                    ->setMethod(
                        $this->generateClassMethod(
                            $getMethodName,
                            'return isset($this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'])' .
                            ' ? $this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\']' .
                            ' : null;'
                        )
                    )
                    ->setMethod(
                        $this->generateClassMethod(
                            $setMethodName,
                            '$this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'] = $value; return $this;',
                            ['value']
                        )
                    );
            }
        }
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateGetMethodName($fieldName)
    {
        return 'get' . ucfirst(Inflector::camelize($fieldName));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function generateSetMethodName($fieldName)
    {
        return 'set' . ucfirst(Inflector::camelize($fieldName));
    }
}
