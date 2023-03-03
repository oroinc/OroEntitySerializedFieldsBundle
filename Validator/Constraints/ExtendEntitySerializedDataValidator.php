<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the values of the serialized fields with Type constraints.
 */
class ExtendEntitySerializedDataValidator extends ConstraintValidator
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var array ["fieldType" => [<contraint1>, ...]] */
    private $constraintsMapping = [];

    /** @var array */
    private $constraintsByType;

    /** @var array */
    private $fieldConstraints = [];

    private FieldConfigConstraintsFactory $fieldConfigConstraintsFactory;

    public function __construct(
        ConfigProvider $configProvider,
        FieldHelper $fieldHelper,
        FieldConfigConstraintsFactory $fieldConfigConstraintsFactory
    ) {
        $this->configProvider = $configProvider;
        $this->fieldHelper = $fieldHelper;
        $this->fieldConfigConstraintsFactory = $fieldConfigConstraintsFactory;
    }

    /**
     * @param string $fieldType
     * @param array  $constraintData
     */
    public function addConstraints($fieldType, $constraintData): void
    {
        $this->constraintsMapping[$fieldType] = $constraintData;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ExtendEntityInterface ||
            !EntityPropertyInfo::methodExists($value, 'getSerializedData')) {
            return;
        }

        foreach ($this->getFieldConstraints(ClassUtils::getClass($value)) as $fieldName => $constraints) {
            if (isset($value->{$fieldName})) {
                $this->context->getValidator()
                    ->inContext($this->context)
                    ->atPath($fieldName)
                    ->validate($value->{$fieldName}, $constraints);
            }
        }
    }

    private function getFieldConstraints(string $className): array
    {
        if (!array_key_exists($className, $this->fieldConstraints)) {
            $this->buildFieldConstraints($className);
        }

        return $this->fieldConstraints[$className];
    }

    private function buildFieldConstraints(string $className): void
    {
        $constraints = [];

        foreach ($this->getFieldsByClassName($className) as $fieldName) {
            if (!$this->configProvider->hasConfig($className, $fieldName)) {
                continue;
            }

            $config = $this->configProvider->getConfig($className, $fieldName);
            if (!$this->isFieldValidatable($config)) {
                continue;
            }

            $fieldConstraints = \array_merge(
                $this->getConstraintsByFieldType($config->getId()->getFieldType()),
                $this->fieldConfigConstraintsFactory->create($config)
            );

            if ($fieldConstraints) {
                $constraints[$fieldName] = $fieldConstraints;
            }
        }

        $this->fieldConstraints[$className] = $constraints;
    }

    private function isFieldValidatable(ConfigInterface $fieldConfig): bool
    {
        return $fieldConfig->is('is_extend') &&
            $fieldConfig->is('is_serialized') &&
            !$fieldConfig->is('is_deleted') &&
            !$fieldConfig->is('state', ExtendScope::STATE_NEW) &&
            !$fieldConfig->is('state', ExtendScope::STATE_DELETE);
    }

    /**
     * @param string $entityClassName
     *
     * @return array
     */
    private function getFieldsByClassName($entityClassName): array
    {
        $properties = $this->fieldHelper->getEntityFields(
            $entityClassName,
            EntityFieldProvider::OPTION_WITH_HIDDEN_FIELDS
        );

        return array_column($properties, 'name');
    }

    /**
     * @param string $type
     * @return array
     */
    private function getConstraintsByFieldType($type): array
    {
        if ($this->constraintsByType === null) {
            foreach ($this->constraintsMapping as $fieldType => $constraints) {
                $this->constraintsByType[$fieldType] = $this->parseNodes($constraints);
            }
        }

        return $this->constraintsByType[$type] ?? [];
    }

    /**
     * Parses a collection of YAML nodes
     *
     * @param array $nodes The YAML nodes
     *
     * @return array An array of values or Constraint instances
     */
    private function parseNodes(array $nodes): array
    {
        $constraints = [];

        foreach ($nodes as $childNodes) {
            foreach ($childNodes as $name => $options) {
                $constraints[] = $this->createConstraint($name, $options);
            }
        }

        return $constraints;
    }

    private function createConstraint(string $className, array $options = null): Constraint
    {
        $fullClassName = '\\Symfony\\Component\\Validator\\Constraints\\' . $className;

        if (class_exists($fullClassName)) {
            $className = $fullClassName;
        }

        return new $className($options);
    }
}
