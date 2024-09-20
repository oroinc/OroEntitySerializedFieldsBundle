<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DataAudit;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\ChangeSetToAuditFieldsConverterInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Decoration of ChangeSetToAuditFieldsConverter to apply convertion to enumerable fields.
 */
class EnumerableChangeSetToAuditFieldsConverter implements ChangeSetToAuditFieldsConverterInterface
{
    public function __construct(
        readonly private ChangeSetToAuditFieldsConverterInterface $innerConverter,
        readonly private ConfigManager $configManager,
        readonly private AuditConfigProvider $auditConfigProvider,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function convert(
        string $auditEntryClass,
        string $auditFieldClass,
        ClassMetadata $entityMetadata,
        array $changeSet
    ): array {
        $fields = $this->innerConverter->convert($auditEntryClass, $auditFieldClass, $entityMetadata, $changeSet);
        foreach ($changeSet as $fieldName => $change) {
            if ($fieldName === 'serialized_data') {
                $this->convertSerializedEnumChangeSet(
                    $auditFieldClass,
                    $entityMetadata,
                    $change,
                    $fields
                );
            }
        }

        return $fields;
    }

    private function convertSerializedEnumChangeSet(
        string $auditFieldClass,
        ClassMetadata $entityMetadata,
        array $change,
        array &$fields
    ): void {
        if (empty($change[0]) && empty($change[1])) {
            return;
        }
        if (is_iterable($change[0])) {
            // change-set updates
            foreach ($change[0] as $fieldName => $oldChanges) {
                $this->processEnumerableChangeSet($entityMetadata, $fields, $change, $fieldName, $auditFieldClass);
            }
        }
        if (is_iterable($change[1])) {
            // check if new items is added
            foreach ($change[1] as $fieldName => $oldChanges) {
                if (isset($fields[$fieldName])) {
                    continue;
                }
                $this->processEnumerableChangeSet($entityMetadata, $fields, $change, $fieldName, $auditFieldClass);
            }
        }
    }

    private function processEnumerableChangeSet(
        ClassMetadata $entityMetadata,
        array &$fields,
        array $change,
        string $fieldName,
        string $auditFieldClass
    ): void {
        if (!$this->configManager->hasConfigFieldModel($entityMetadata->name, $fieldName)) {
            return;
        }
        $fieldConfigId = $this->configManager->getId('enum', $entityMetadata->name, $fieldName);
        if (!ExtendHelper::isEnumerableType($fieldConfigId->getFieldType())) {
            return;
        }
        if (!$this->auditConfigProvider->isAuditableField($entityMetadata->name, $fieldName)) {
            return;
        }
        $old = $change[0][$fieldName] ?? null;
        $new = $change[1][$fieldName];
        if ($old == $new) {
            return;
        }
        $isMultiple = ExtendHelper::isMultiEnumType($fieldConfigId->getFieldType());

        $fields[$fieldName] = $this->createAuditFieldEntity(
            $auditFieldClass,
            $fieldName,
            AuditFieldTypeRegistry::TYPE_STRING,
            $this->prepareChangeValue($isMultiple, $new),
            $this->prepareChangeValue($isMultiple, $old),
        );
    }

    private function prepareChangeValue(bool $isMultiple, mixed $value): ?string
    {
        if ($isMultiple && is_array($value)) {
            $value = array_map(fn ($item) => ExtendHelper::getEnumInternalId($item), $value);

            return implode(',', $value);
        }

        return null !== $value ? ExtendHelper::getEnumInternalId($value) : null;
    }

    private function createAuditFieldEntity(
        $auditFieldClass,
        $field,
        $dataType,
        $newValue = null,
        $oldValue = null
    ) {
        return new $auditFieldClass($field, $dataType, $newValue, $oldValue);
    }
}
