<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpProperty;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;

class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    const SERIALIZED_DATA_FIELD = 'serialized_data';

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        $entityClassName = $schema['class'];

        /**
         * Entity processing
         */
        $class->setProperty(PhpProperty::create(self::SERIALIZED_DATA_FIELD)->setVisibility('protected'));
        $class
            ->setMethod(
                $this->generateClassMethod(
                    'get' . ucfirst(Inflector::camelize(self::SERIALIZED_DATA_FIELD)),
                    'return $this->' . self::SERIALIZED_DATA_FIELD .';'
                )
            )
            ->setMethod(
                $this->generateClassMethod(
                    'set' . ucfirst(Inflector::camelize(self::SERIALIZED_DATA_FIELD)),
                    '$this->' . self::SERIALIZED_DATA_FIELD .' = $value; return $this;',
                    ['value']
                )
            );

        /**
         * Entity fields processing
         */
        /** @var FieldConfigId[] $config */
        $fieldConfigs = $this->extendConfigProvider->getConfigs($entityClassName);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->is('is_serialized')) {
                $fieldName = $fieldConfig->getId()->getFieldName();

                if ($class->hasMethod('get' . ucfirst(Inflector::camelize($fieldName)))) {
                    $class->removeMethod('get' . ucfirst(Inflector::camelize($fieldName)));
                }
                if ($class->hasMethod('set' . ucfirst(Inflector::camelize($fieldName)))) {
                    $class->removeMethod('set' . ucfirst(Inflector::camelize($fieldName)));
                }

                $class
                    ->setMethod(
                        $this->generateClassMethod(
                            'get' . ucfirst(Inflector::camelize($fieldName)),
                            'return isset($this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\']) ' .
                            '   ? $this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'] ' .
                            '   : null;'
                        )
                    )
                    ->setMethod(
                        $this->generateClassMethod(
                            'set' . ucfirst(Inflector::camelize($fieldName)),
                            '$this->' . self::SERIALIZED_DATA_FIELD .'[\'' . $fieldName . '\'] = $value; return $this;',
                            ['value']
                        )
                    );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        /** @var ConfigInterface $config */
        $config = $this->extendConfigProvider->getConfig($schema['class']);

        return $config->is('is_extend');
    }
}
